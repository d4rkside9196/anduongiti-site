<?php

namespace App\Filament\Resources\CoffeeProducts\Pages;

use App\Filament\Resources\CoffeeProducts\CoffeeProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCoffeeProduct extends CreateRecord
{
    protected static string $resource = CoffeeProductResource::class;
}
