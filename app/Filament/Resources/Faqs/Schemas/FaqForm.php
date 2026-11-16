<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('locale')
                    ->options(['en' => 'English', 'vi' => 'Vietnamese'])
                    ->required()
                    ->default('en'),
                // Free-text grouping key rendered as an accordion section on
                // /support. Existing seeded keys: orders / shipping /
                // products / payment / samples / oem.
                TextInput::make('category')
                    ->required()
                    ->maxLength(100)
                    ->helperText('Grouping key, e.g. orders, shipping, products, payment'),
                TextInput::make('question')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('answer')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(32767),
                Toggle::make('visible')
                    ->default(true),
            ]);
    }
}
