<?php

namespace Tests\Feature;

use App\Actions\Auth\InviteUser;
use App\Filament\Pages\Auth\ResetPassword;
use App\Filament\Resources\Users\Pages\CreateUsers;
use App\Filament\Resources\Users\Pages\EditUsers;
use App\Models\User;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG_PASSWORD = 'Invited!Str0ng#2026';

    public function test_only_super_admins_can_open_user_administration(): void
    {
        $catalogManager = User::factory()->catalogManager()->create();
        $support = User::factory()->support()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($catalogManager)->get('/admin/users')->assertForbidden();
        $this->actingAs($support)->get('/admin/users')->assertForbidden();
        $this->actingAs($superAdmin)->get('/admin/users')->assertOk();
    }

    public function test_non_super_admin_cannot_invite_users_through_the_action(): void
    {
        $actor = User::factory()->catalogManager()->create();

        $this->expectException(AuthorizationException::class);

        app(InviteUser::class)->handle([
            'name' => 'Unauthorized Invite',
            'email' => 'unauthorized@example.com',
            'role' => User::ROLE_SUPPORT,
        ], $actor);
    }

    public function test_invitation_creates_unverified_active_user_with_random_password_and_setup_notifications(): void
    {
        Notification::fake();
        $actor = User::factory()->superAdmin()->create();

        $invited = app(InviteUser::class)->handle([
            'name' => 'Support User',
            'email' => 'support@example.com',
            'role' => User::ROLE_SUPPORT,
        ], $actor);

        $this->assertSame(User::ROLE_SUPPORT, $invited->role);
        $this->assertTrue($invited->is_active);
        $this->assertNull($invited->email_verified_at);
        $this->assertFalse(Hash::check('password', $invited->password));
        Notification::assertSentTo($invited, VerifyEmail::class);
        Notification::assertSentTo($invited, ResetPasswordNotification::class);
    }

    public function test_invited_user_can_set_up_a_strong_password_and_verify_by_email_possession(): void
    {
        Notification::fake();
        $actor = User::factory()->superAdmin()->create();
        $invited = app(InviteUser::class)->handle([
            'name' => 'Invited User',
            'email' => 'invited@example.com',
            'role' => User::ROLE_CATALOG_MANAGER,
        ], $actor);

        $token = null;
        Notification::assertSentTo($invited, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        Livewire::test(ResetPassword::class, ['email' => $invited->email, 'token' => $token])
            ->fillForm([
                'password' => self::STRONG_PASSWORD,
                'passwordConfirmation' => self::STRONG_PASSWORD,
            ])
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertNotNull($invited->fresh()->email_verified_at);
        $this->assertTrue(Hash::check(self::STRONG_PASSWORD, $invited->fresh()->password));
    }

    public function test_create_page_invites_without_a_password_field(): void
    {
        Notification::fake();
        $actor = User::factory()->superAdmin()->create();

        Livewire::actingAs($actor)
            ->test(CreateUsers::class)
            ->assertDontSee('Password')
            ->fillForm([
                'name' => 'New Catalog Manager',
                'email' => 'catalog@example.com',
                'role' => User::ROLE_CATALOG_MANAGER,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $invited = User::where('email', 'catalog@example.com')->firstOrFail();
        $this->assertNull($invited->email_verified_at);
        Notification::assertSentTo($invited, ResetPasswordNotification::class);
    }

    public function test_last_active_super_admin_cannot_be_demoted_deactivated_or_deleted(): void
    {
        $lastSuperAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse(Gate::forUser($lastSuperAdmin)->allows('update', [$lastSuperAdmin, ['role' => User::ROLE_SUPPORT]]));
        $this->assertFalse(Gate::forUser($lastSuperAdmin)->allows('update', [$lastSuperAdmin, ['is_active' => false]]));
        $this->assertFalse(Gate::forUser($lastSuperAdmin)->allows('delete', $lastSuperAdmin));
    }

    public function test_unverified_super_admin_does_not_count_as_remaining_panel_access(): void
    {
        $verifiedSuperAdmin = User::factory()->superAdmin()->create();
        User::factory()->superAdmin()->unverified()->create();

        $this->assertFalse(Gate::forUser($verifiedSuperAdmin)->allows('update', [$verifiedSuperAdmin, ['role' => User::ROLE_SUPPORT]]));
        $this->assertFalse(Gate::forUser($verifiedSuperAdmin)->allows('update', [$verifiedSuperAdmin, ['is_active' => false]]));
        $this->assertFalse(Gate::forUser($verifiedSuperAdmin)->allows('delete', $verifiedSuperAdmin));
    }

    public function test_last_super_admin_guard_is_enforced_by_livewire_edit(): void
    {
        $lastSuperAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($lastSuperAdmin)
            ->test(EditUsers::class, ['record' => $lastSuperAdmin->getKey()])
            ->fillForm([
                'name' => $lastSuperAdmin->name,
                'email' => $lastSuperAdmin->email,
                'role' => User::ROLE_CATALOG_MANAGER,
                'is_active' => true,
            ])
            ->call('save')
            ->assertForbidden();

        $this->assertSame(User::ROLE_SUPER_ADMIN, $lastSuperAdmin->fresh()->role);
    }

    public function test_super_admin_can_change_another_super_admin_when_one_active_remains(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->create();

        $this->assertTrue(Gate::forUser($actor)->allows('update', [$other, ['role' => User::ROLE_CATALOG_MANAGER]]));
        $this->assertTrue(Gate::forUser($actor)->allows('update', [$other, ['is_active' => false]]));
        $this->assertTrue(Gate::forUser($actor)->allows('delete', $other));
    }

    public function test_edit_email_is_read_only_and_does_not_expose_password(): void
    {
        $actor = User::factory()->superAdmin()->create();
        $target = User::factory()->support()->create(['email' => 'readonly@example.com']);

        Livewire::actingAs($actor)
            ->test(EditUsers::class, ['record' => $target->getKey()])
            ->assertDontSee('Password')
            ->assertFormFieldIsDisabled('email');
    }
}
