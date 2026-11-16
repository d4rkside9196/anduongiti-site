<?php

namespace App\Filament\Resources\Concerns;

use App\Authorization\AdminPermissions;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesCatalogResource
{
    public static function canViewAny(): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_READ);
    }

    public static function canView(Model $record): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_READ);
    }

    public static function canCreate(): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canEdit(Model $record): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canDelete(Model $record): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canDeleteAny(): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canForceDelete(Model $record): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canForceDeleteAny(): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canRestore(Model $record): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canRestoreAny(): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canReplicate(Model $record): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    public static function canReorder(): bool
    {
        return static::authorizedFor(AdminPermissions::CATALOG_WRITE);
    }

    private static function authorizedFor(string $capability): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && AdminPermissions::allows($user, $capability);
    }
}
