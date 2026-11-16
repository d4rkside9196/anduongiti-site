<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->readOnly()
                    ->required(),
                TextInput::make('company')->readOnly(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->readOnly()
                    ->required(),
                TextInput::make('country')->readOnly(),
                TextInput::make('product')->readOnly(),
                TextInput::make('source_slug')
                    ->readOnly(),
                Textarea::make('message')
                    ->readOnly()
                    ->required()
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['new' => 'New', 'in_progress' => 'In progress', 'replied' => 'Replied', 'archived' => 'Archived'])
                    ->disabled()
                    ->dehydrated(false)
                    ->default('new'),
            ]);
    }
}
