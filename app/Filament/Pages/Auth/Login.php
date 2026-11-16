<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * Keep Filament's default five attempts per minute in production, while
     * allowing the full browser matrix to run sequentially in test.
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
    {
        return parent::rateLimit(
            (int) config('site.login_throttle_limit', $maxAttempts),
            $decaySeconds,
            $method,
            $component,
        );
    }
}
