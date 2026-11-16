<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ResetPassword extends BaseResetPassword
{
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->rule(PasswordRule::min(12)->mixedCase()->numbers()->symbols());
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        if ($this->isResetPasswordRateLimited($this->email)) {
            return null;
        }

        $data = $this->form->getState();
        $data['email'] = $this->email;
        $data['token'] = $this->token;
        $hasPanelAccess = true;

        $status = Password::broker(Filament::getAuthPasswordBroker())->reset(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword|Model|Authenticatable $user) use ($data, &$hasPanelAccess): void {
                $canSetPassword = $user instanceof User
                    ? $user->is_active
                    : (! $user instanceof FilamentUser || $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()));

                if (! $canSetPassword) {
                    $hasPanelAccess = false;

                    return;
                }

                $user->forceFill([
                    $user->getAuthPasswordName() => Hash::make($data['password']),
                    $user->getRememberTokenName() => Str::random(60),
                ]);

                // A valid setup/reset link proves possession of the email address.
                if ($user instanceof User && ! $user->hasVerifiedEmail()) {
                    $user->forceFill(['email_verified_at' => now()]);
                }

                $user->save();
                event(new PasswordReset($user));
            },
        );

        if (! $hasPanelAccess) {
            $status = Password::INVALID_USER;
        }

        if ($status === Password::PASSWORD_RESET) {
            Notification::make()->title(__($status))->success()->send();

            return app(PasswordResetResponse::class);
        }

        // Do not distinguish unknown accounts, inactive accounts, or bad tokens.
        Notification::make()->title(__(Password::INVALID_TOKEN))->danger()->send();

        return null;
    }
}
