<?php

namespace App\Modules\Assets\Policies;

use App\Modules\Assets\Models\Asset;
use App\Modules\Users\Models\User;

/**
 * El catálogo de activos es compartido por todos los despachos:
 * - admin, asesor y superadmin pueden consultarlo y dar de alta activos nuevos;
 * - solo el superadmin edita o borra (un cambio afecta a todos los despachos).
 */
class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [User::ROL_SUPERADMIN, User::ROL_ADMIN, User::ROL_ASESOR], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->isSuperadmin();
    }
}
