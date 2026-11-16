<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\ActivityLogger;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Notifications\VerifyEmail;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class InviteUser
{
    /**
     * @param  array{name: string, email: string, role: string}  $data
     */
    public function handle(array $data, User $actor): User
    {
        Gate::forUser($actor)->authorize('create', User::class);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => true,
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(64)),
        ]);

        app(ActivityLogger::class)->record('user.invited', $user, [
            'role' => $user->role,
            'is_active' => $user->is_active,
        ]);

        $verification = app(VerifyEmail::class);
        $verification->url = Filament::getVerifyEmailUrl($user);
        $user->notify($verification);

        Password::broker()->sendResetLink(
            ['email' => $user->email],
            function (CanResetPassword $user, string $token): void {
                $notification = app(ResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);
                $user->notify($notification);
            },
        );

        return $user;
    }
}
