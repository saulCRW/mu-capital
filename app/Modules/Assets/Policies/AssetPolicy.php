<?php

namespace App\Modules\Assets\Policies;

use App\Modules\Assets\Models\Asset;
use App\Modules\Users\Models\User;

/**
 * El catálogo de activos es compartido por todos los usuarios:
 * - cualquiera puede consultarlo y dar de alta activos nuevos;
 * - solo el superadmin edita o borra (un cambio afecta a todos).
 */
class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
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
