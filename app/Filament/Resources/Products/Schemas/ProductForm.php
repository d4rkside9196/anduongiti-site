<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_key')
                    ->options(['coffee' => 'Coffee', 'rubber' => 'Rubber'])
                    ->required()
                    ->in(['coffee', 'rubber'])
                    ->scopedUnique(ignoreRecord: true, modifyQueryUsing: fn ($query, Get $get) => $query->where('locale', $get('locale'))),
                Select::make('locale')
                    ->options(['en' => 'English', 'vi' => 'Vietnamese'])
                    ->required()
                    ->default('en'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('eyebrow')
                    ->required(),
                Textarea::make('lede')
                    ->required()
                    ->columnSpanFull(),
                // DB stores a flat list of positional [key, value] tuples and
                // the Blade views iterate `$product->specs as [$k, $v]`. The
                // old implementation was a plain Textarea, which round-tripped
                // raw JSON as a string and destroyed the array shape on every
                // save. KeyValue edits an assoc array and its state cast
                // handles ['key' => ..., 'value' => ...] pairs — the bridging
                // between the two shapes lives in the pages
                // (EditProduct::mutateFormDataBeforeFill /
                // mutateFormDataBeforeSave, CreateProduct::mutateFormDataBeforeCreate).
                KeyValue::make('specs')
                    ->keyLabel('Specification')
                    ->valueLabel('Value')
                    ->columnSpanFull(),
                TagsInput::make('origins')
                    ->placeholder('e.g. Đắk Lắk · Buôn Ma Thuột')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Model shape (positional tuples) → shape the KeyValue state cast
     * understands ([['key' => ..., 'value' => ...], ...]).
     *
     * @param  array<mixed>  $specs
     * @return array<mixed>
     */
    public static function specsToKeyValueState(array $specs): array
    {
        return collect($specs)
            ->values()
            ->map(fn ($pair, $i): array => [
                'key' => is_array($pair) ? ($pair[0] ?? '') : (string) $i,
                'value' => is_array($pair) ? ($pair[1] ?? '') : (string) $pair,
            ])
            ->all();
    }

    /**
     * KeyValue state output → model shape (positional [key, value] tuples).
     * Depending on the dehydrate path the cast hands us either
     * [['key' => ..., 'value' => ...], ...] pairs or a flat {key: value}
     * map — accept both, drop empty keys.
     *
     * @param  array<mixed>  $specs
     * @return array<mixed>
     */
    public static function keyValueStateToSpecs(array $specs): array
    {
        $items = collect($specs);

        $isPairList = $items->isNotEmpty() && $items->every(
            fn ($pair): bool => is_array($pair) && array_key_exists('key', $pair),
        );

        if ($isPairList) {
            return $items
                ->filter(fn ($pair): bool => filled($pair['key']))
                ->map(fn ($pair): array => [$pair['key'], $pair['value'] ?? ''])
                ->values()
                ->all();
        }

        return $items
            ->reject(fn ($value, $key): bool => in_array($key, ['', null], true))
            ->map(fn ($value, $key): array => [$key, $value])
            ->values()
            ->all();
    }
}
