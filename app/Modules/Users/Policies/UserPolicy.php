<?php

namespace App\Modules\Users\Policies;

use App\Modules\Users\Models\User;

/**
 * El admin gestiona los usuarios de SU despacho. Las reglas de negocio
 * (no dejar al despacho sin admin, etc.) viven en UserService.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->organization_id === $target->organization_id;
    }

    public function delete(User $user, User $target): bool
    {
        return $this->update($user, $target) && $user->id !== $target->id;
    }
}
