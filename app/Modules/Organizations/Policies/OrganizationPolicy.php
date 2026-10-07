<?php

namespace App\Modules\Organizations\Policies;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Users\Models\User;

/** Solo el superadmin administra despachos. */
class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }
}
