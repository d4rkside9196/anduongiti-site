<?php

namespace App\Filament\Resources\CoffeeProducts\Pages;

use App\Filament\Resources\CoffeeProducts\CoffeeProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCoffeeProduct extends EditRecord
{
    protected static string $resource = CoffeeProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
