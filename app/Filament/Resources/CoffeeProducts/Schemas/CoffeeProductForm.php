<?php

namespace App\Filament\Resources\CoffeeProducts\Schemas;

use App\Models\CoffeeProduct;
use App\Models\ProductCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CoffeeProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('locale')
                    ->options(['en' => 'English', 'vi' => 'Vietnamese'])
                    ->required()
                    ->default('en')
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('category_id', null)),
                Select::make('category_id')
                    ->label('Category')
                    ->options(fn (Get $get): array => ProductCategory::forLocale($get('locale') ?: 'en')
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->required()
                    ->live()
                    ->rules([
                        fn (Get $get) => Rule::exists('product_categories', 'id')
                            ->where(fn ($query) => $query->where('locale', $get('locale'))),
                    ]),
                Select::make('bean_variety')
                    ->options(CoffeeProduct::BEAN_VARIETY_LABELS)
                    ->visible(fn (Get $get): bool => self::isBeansCategory($get))
                    ->required(fn (Get $get): bool => self::isBeansCategory($get))
                    ->in(array_keys(CoffeeProduct::BEAN_VARIETY_LABELS))
                    ->dehydratedWhenHidden()
                    ->dehydrateStateUsing(fn (?string $state, Get $get): ?string => self::isBeansCategory($get) ? $state : null),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn ($query, Get $get) => $query->where('locale', $get('locale'))),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('eyebrow')
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TagsInput::make('specs')
                    ->placeholder('e.g. Moisture ≤ 12.5%')
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->disk('public')
                    ->directory('coffee')
                    ->imageEditor()
                    // Raster content types only (content-sniffed). Deliberately
                    // NOT ->image(): that allows image/svg+xml, whose embedded
                    // <script> executes in visitors' browsers when the file is
                    // served from the public disk.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    // The stored path must come from this upload flow — a
                    // crafted Livewire submit can otherwise leave an arbitrary
                    // string in the state, which CoffeeProductObserver feeds to
                    // Storage::delete() and image_url echoes publicly.
                    ->preventFilePathTampering()
                    // The stored filename keeps NO part of the client-supplied
                    // name: derive the extension from the sniffed MIME type so
                    // a GIF89a polyglot named shell.php cannot land as
                    // coffee/{ulid}.php on the web-served public disk.
                    ->getUploadedFileNameForStorageUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {
                        $extension = match ($file->getMimeType()) {
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp',
                            default => 'bin',
                        };

                        return Str::ulid().'.'.$extension;
                    }),
                TextInput::make('image_alt')
                    ->maxLength(255)
                    ->helperText('Optional alternative text. If empty, the product title is used.'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(32767),
                Toggle::make('visible')
                    ->default(true),
            ]);
    }

    private static function isBeansCategory(Get $get): bool
    {
        return filled($get('category_id')) && ProductCategory::query()
            ->whereKey($get('category_id'))
            ->where('slug', 'beans')
            ->exists();
    }
}
