<?php

namespace App\Modules\Clients\Policies;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;

/**
 * Admin: todos los clientes de su despacho.
 * Asesor: solo los clientes que tiene asignados.
 * Superadmin y cliente: sin acceso (por ahora).
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->rol, [User::ROL_ADMIN, User::ROL_ASESOR], true);
    }

    public function view(User $user, Client $client): bool
    {
        if ($user->organization_id !== $client->organization_id) {
            return false;
        }

        return $this->gestiona($user, $client)
            || ($user->rol === User::ROL_CLIENTE && $user->client_id === $client->id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Client $client): bool
    {
        return $this->gestiona($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->gestiona($user, $client);
    }

    /** El admin gestiona todos los clientes del despacho; el asesor, los suyos. El usuario cliente solo consulta. */
    private function gestiona(User $user, Client $client): bool
    {
        return $user->organization_id === $client->organization_id
            && ($user->isAdmin() || ($user->rol === User::ROL_ASESOR && $client->asesor_id === $user->id));
    }
}
