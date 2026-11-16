<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->disabledOn('edit'),
            Select::make('role')
                ->options([
                    User::ROLE_SUPER_ADMIN => 'Super admin',
                    User::ROLE_CATALOG_MANAGER => 'Catalog manager',
                    User::ROLE_SUPPORT => 'Support',
                ])
                ->required()
                ->default(User::ROLE_CATALOG_MANAGER),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }
}
