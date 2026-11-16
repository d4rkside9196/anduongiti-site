<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Filament\Pages\Auth\ResetPassword;
use App\Filament\Pages\EditProfile;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD = 'Str0ng!Passw0rd#2026';

    public function test_verified_active_user_can_login_and_last_login_is_recorded(): void
    {
        $user = User::factory()->superAdmin()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'last_login_at' => null,
        ]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password', 'remember' => false])
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_repeated_wrong_password_attempts_throttle_even_a_valid_login(): void
    {
        $user = User::factory()->create(['email' => 'throttle@example.com']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Livewire::test(Login::class)
                ->fillForm(['email' => $user->email, 'password' => 'wrong-password', 'remember' => false])
                ->call('authenticate')
                ->assertHasErrors(['data.email']);
        }

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'password', 'remember' => false])
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_inactive_and_unverified_users_cannot_login_or_open_the_panel(): void
    {
        $users = [
            User::factory()->inactive()->create(['password' => 'password']),
            User::factory()->unverified()->create(['password' => 'password']),
        ];

        foreach ($users as $user) {
            Livewire::test(Login::class)
                ->fillForm(['email' => $user->email, 'password' => 'password', 'remember' => false])
                ->call('authenticate')
                ->assertHasErrors(['data.email']);

            $this->assertGuest();
            $this->actingAs($user)->get('/admin')->assertForbidden();
            auth()->logout();
        }
    }

    public function test_reset_requests_have_the_same_response_for_known_and_unknown_accounts(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])
            ->call('request')
            ->assertNotified(__('passwords.sent'));

        Notification::assertSentTo($user, ResetPasswordNotification::class);
        session()->flush();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'missing@example.com'])
            ->call('request')
            ->assertNotified(__('passwords.sent'));
    }

    public function test_recent_reset_token_throttle_does_not_reveal_that_an_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])
            ->call('request')
            ->assertNotified(__('passwords.sent'));

        session()->flush();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])
            ->call('request')
            ->assertNotified(__('passwords.sent'));
    }

    public function test_valid_reset_token_sets_a_strong_password(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm([
                'password' => self::STRONG_PASSWORD,
                'passwordConfirmation' => self::STRONG_PASSWORD,
            ])
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect('/admin/login');

        $this->assertTrue(Hash::check(self::STRONG_PASSWORD, $user->fresh()->password));
    }

    public function test_invalid_reset_token_cannot_change_the_password(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => 'invalid-token'])
            ->fillForm([
                'password' => self::STRONG_PASSWORD,
                'passwordConfirmation' => self::STRONG_PASSWORD,
            ])
            ->call('resetPassword')
            ->assertNotified(__('passwords.token'));

        $this->assertSame($oldHash, $user->fresh()->password);
    }

    public function test_invalid_token_response_is_identical_for_an_unknown_email(): void
    {
        Livewire::test(ResetPassword::class, ['email' => 'missing@example.com', 'token' => 'invalid-token'])
            ->fillForm([
                'password' => self::STRONG_PASSWORD,
                'passwordConfirmation' => self::STRONG_PASSWORD,
            ])
            ->call('resetPassword')
            ->assertNotified(__('passwords.token'));
    }

    #[DataProvider('weakPasswords')]
    public function test_reset_password_requires_length_mixed_case_numbers_and_symbols(string $weakPassword): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->fillForm(['password' => $weakPassword, 'passwordConfirmation' => $weakPassword])
            ->call('resetPassword')
            ->assertHasErrors(['password']);
    }

    public function test_profile_requires_the_current_password_when_changing_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => self::STRONG_PASSWORD,
                'passwordConfirmation' => self::STRONG_PASSWORD,
                'currentPassword' => '',
            ])
            ->call('save')
            ->assertHasErrors(['data.currentPassword' => 'required']);
    }

    public function test_profile_rejects_weak_passwords(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'weakpassword1!',
                'passwordConfirmation' => 'weakpassword1!',
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasErrors(['data.password']);
    }

    public function test_profile_accepts_a_strong_password_with_current_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Livewire::actingAs($user)
            ->test(EditProfile::class)
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => self::STRONG_PASSWORD,
                'passwordConfirmation' => self::STRONG_PASSWORD,
                'currentPassword' => 'password',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check(self::STRONG_PASSWORD, $user->fresh()->password));
    }

    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Sh0rt!'],
            'missing uppercase' => ['alllowercase1!'],
            'missing lowercase' => ['ALLUPPERCASE1!'],
            'missing number' => ['NoNumberHere!'],
            'missing symbol' => ['NoSymbolHere1'],
        ];
    }
}
