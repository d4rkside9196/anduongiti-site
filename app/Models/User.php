<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;

#[Fillable(['name', 'email', 'email_verified_at', 'password', 'role', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_CATALOG_MANAGER = 'catalog_manager';

    public const ROLE_SUPPORT = 'support';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_CATALOG_MANAGER,
        self::ROLE_SUPPORT,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'role' && ! in_array($value, self::ROLES, true)) {
            throw new InvalidArgumentException('Unsupported admin role.');
        }

        return parent::setAttribute($key, $value);
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasVerifiedEmail();
    }
}
