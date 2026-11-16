<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\Rules\Password;

class EditProfile extends BaseEditProfile
{
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->rule(Password::min(12)->mixedCase()->numbers()->symbols());
    }
}
