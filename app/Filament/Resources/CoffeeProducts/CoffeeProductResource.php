<?php

namespace App\Filament\Resources\CoffeeProducts;

use App\Filament\Resources\CoffeeProducts\Pages\CreateCoffeeProduct;
use App\Filament\Resources\Concerns\AuthorizesCatalogResource;
use App\Filament\Resources\CoffeeProducts\Pages\EditCoffeeProduct;
use App\Filament\Resources\CoffeeProducts\Pages\ListCoffeeProducts;
use App\Filament\Resources\CoffeeProducts\Schemas\CoffeeProductForm;
use App\Filament\Resources\CoffeeProducts\Tables\CoffeeProductsTable;
use App\Models\CoffeeProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CoffeeProductResource extends Resource
{
    use AuthorizesCatalogResource;

    protected static ?string $model = CoffeeProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return CoffeeProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoffeeProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoffeeProducts::route('/'),
            'create' => CreateCoffeeProduct::route('/create'),
            'edit' => EditCoffeeProduct::route('/{record}/edit'),
        ];
    }
}
