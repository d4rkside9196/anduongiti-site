<?php

namespace App\Actions\Catalog;

use Illuminate\Database\Eloquent\Model;

final class SetCatalogVisibility
{
    public function handle(Model $record, bool $visible): void
    {
        $record->forceFill(['visible' => $visible])->save();
    }
}
