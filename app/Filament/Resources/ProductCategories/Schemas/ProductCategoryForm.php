<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use App\Models\ProductCategory;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('locale')
                ->options(['en' => 'English', 'vi' => 'Vietnamese'])
                ->required()
                ->default('en')
                ->live()
                ->rule(function (?ProductCategory $record): Closure {
                    return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        if (! $record || $value === $record->getOriginal('locale')) {
                            return;
                        }

                        if ($record->coffeeProducts()->exists()) {
                            $fail('The '.$attribute.' cannot be changed while products use this category.');
                        }
                    };
                }),
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->rule(function (?ProductCategory $record): Closure {
                    return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        $isUnchangedLegacySlug = $record
                            && $value === 'freeze_dried'
                            && $value === $record->getOriginal('slug');

                        if ($isUnchangedLegacySlug) {
                            return;
                        }

                        if (! is_string($value) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
                            $fail('The '.$attribute.' must be a kebab-case slug.');
                        }
                    };
                })
                ->scopedUnique(
                    ignoreRecord: true,
                    modifyQueryUsing: fn ($query, Get $get) => $query->where('locale', $get('locale')),
                ),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->nullable()
                ->columnSpanFull(),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->maxValue(32767),
            Toggle::make('visible')
                ->default(false),
        ]);
    }
}
