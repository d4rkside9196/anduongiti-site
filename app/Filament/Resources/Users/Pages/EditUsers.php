<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditUsers extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        Gate::forUser(Filament::auth()->user())
            ->authorize('update', [$this->record, $this->data]);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
