<?php

namespace App\Filament\Resources\ProductCategories;

use App\Filament\Resources\Concerns\AuthorizesCatalogResource;
use App\Filament\Resources\ProductCategories\Pages\CreateProductCategories;
use App\Filament\Resources\ProductCategories\Pages\EditProductCategories;
use App\Filament\Resources\ProductCategories\Pages\ListProductCategories;
use App\Filament\Resources\ProductCategories\Schemas\ProductCategoryForm;
use App\Filament\Resources\ProductCategories\Tables\ProductCategoriesTable;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductCategoryResource extends Resource
{
    use AuthorizesCatalogResource;

    protected static ?string $model = ProductCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return ProductCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductCategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductCategories::route('/'),
            'create' => CreateProductCategories::route('/create'),
            'edit' => EditProductCategories::route('/{record}/edit'),
        ];
    }
}
