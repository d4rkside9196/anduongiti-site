<?php

namespace App\Filament\Resources\CoffeeProducts\Pages;

use App\Filament\Resources\CoffeeProducts\CoffeeProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoffeeProducts extends ListRecords
{
    protected static string $resource = CoffeeProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
