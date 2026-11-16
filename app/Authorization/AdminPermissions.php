<?php

namespace App\Authorization;

use App\Models\User;

class AdminPermissions
{
    public const CATALOG_READ = 'catalog_read';

    public const CATALOG_WRITE = 'catalog_write';

    public const SETTINGS_WRITE = 'settings_write';

    public const USERS_READ = 'users_read';

    public const USERS_WRITE = 'users_write';

    public const ACTIVITY_READ = 'activity_read';

    public const LEADS_READ = 'leads_read';

    public const LEADS_WRITE = 'leads_write';

    public const LEADS_DELETE = 'leads_delete';

    private const MATRIX = [
        User::ROLE_SUPER_ADMIN => [
            self::CATALOG_READ,
            self::CATALOG_WRITE,
            self::SETTINGS_WRITE,
            self::USERS_READ,
            self::USERS_WRITE,
            self::ACTIVITY_READ,
            self::LEADS_READ,
            self::LEADS_WRITE,
            self::LEADS_DELETE,
        ],
        User::ROLE_CATALOG_MANAGER => [
            self::CATALOG_READ,
            self::CATALOG_WRITE,
        ],
        User::ROLE_SUPPORT => [
            self::LEADS_READ,
            self::LEADS_WRITE,
        ],
    ];

    public static function allows(User $user, string $capability): bool
    {
        if (! $user->is_active || ! $user->hasVerifiedEmail()) {
            return false;
        }

        return in_array($capability, self::MATRIX[$user->role] ?? [], true);
    }
}
