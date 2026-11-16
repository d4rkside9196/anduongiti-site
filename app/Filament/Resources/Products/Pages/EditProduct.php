<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Schemas\ProductForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // specs is stored as positional [key, value] tuples; the KeyValue
        // field's state cast expects ['key' => ..., 'value' => ...] pairs.
        $data['specs'] = ProductForm::specsToKeyValueState($data['specs'] ?? []);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Collapse the KeyValue state back to the positional tuples
        // the Blade views iterate (`$product->specs as [$k, $v]`).
        $data['specs'] = ProductForm::keyValueStateToSpecs($data['specs'] ?? []);

        return $data;
    }
}
