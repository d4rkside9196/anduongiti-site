<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Collapse the KeyValue cast output to the positional [key, value]
        // tuples the model stores and the Blade views iterate.
        $data['specs'] = ProductForm::keyValueStateToSpecs($data['specs'] ?? []);

        return $data;
    }
}
