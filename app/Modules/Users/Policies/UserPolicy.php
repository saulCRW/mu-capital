<?php

namespace App\Modules\Users\Policies;

use App\Modules\Users\Models\User;

/** El superadmin administra las cuentas (plan y estado); cada usuario edita la suya en "Mi cuenta". */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isSuperadmin() && ! $target->isSuperadmin();
    }
}
