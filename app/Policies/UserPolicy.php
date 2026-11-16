<?php

namespace App\Policies;

use App\Authorization\AdminPermissions;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::USERS_READ);
    }

    public function view(User $user, User $target): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::USERS_READ);
    }

    public function create(User $user): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::USERS_WRITE);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, User $target, array $attributes = []): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::USERS_WRITE)
            && ! $this->removesLastActiveSuperAdmin($target, $attributes);
    }

    public function delete(User $user, User $target): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::USERS_WRITE)
            && ! $this->removesLastActiveSuperAdmin($target, ['is_active' => false]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function removesLastActiveSuperAdmin(User $target, array $attributes): bool
    {
        if (! $target->is_active || ! $target->isSuperAdmin()) {
            return false;
        }

        $resultingRole = $attributes['role'] ?? $target->role;
        $resultingActive = (bool) ($attributes['is_active'] ?? $target->is_active);

        if ($resultingRole === User::ROLE_SUPER_ADMIN && $resultingActive) {
            return false;
        }

        return User::query()
            ->where('role', User::ROLE_SUPER_ADMIN)
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->count() <= 1;
    }
}
