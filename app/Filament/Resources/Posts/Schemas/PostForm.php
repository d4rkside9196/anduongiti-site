<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('slug')
                    ->required()
                    ->maxLength(200)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn ($query, Get $get) => $query->where('locale', $get('locale'))),
                Select::make('locale')
                    ->options(['en' => 'English', 'vi' => 'Vietnamese'])
                    ->required()
                    ->default('en'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('excerpt')
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                // tags is json-cast to a plain string[] on the Post model —
                // a plain Textarea round-tripped it as raw JSON text and
                // corrupted the array shape on every save.
                TagsInput::make('tags')
                    ->placeholder('e.g. EUDR')
                    ->columnSpanFull(),
                TextInput::make('cover'),
                DateTimePicker::make('published_at')
                    ->required(),
                Select::make('status')
                    ->options(['draft' => 'Draft', 'published' => 'Published'])
                    ->required()
                    ->default('draft'),
            ]);
    }
}
