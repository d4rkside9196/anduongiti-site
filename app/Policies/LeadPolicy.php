<?php

namespace App\Policies;

use App\Authorization\AdminPermissions;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::LEADS_READ);
    }

    public function view(User $user, Lead $lead): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::LEADS_READ)
            && Lead::visibleTo($user)->whereKey($lead->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::LEADS_WRITE);
    }

    public function update(User $user, Lead $lead): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::LEADS_WRITE)
            && Lead::visibleTo($user)->whereKey($lead->getKey())->exists();
    }

    public function delete(User $user, Lead $lead): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::LEADS_DELETE);
    }

    public function deleteAny(User $user): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::LEADS_DELETE);
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return AdminPermissions::allows($user, AdminPermissions::LEADS_DELETE);
    }
}
