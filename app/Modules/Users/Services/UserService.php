<?php

namespace App\Modules\Users\Services;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Clients\Models\Client;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio sobre usuarios del despacho.
 * El organization_id lo llena el trait BelongsToOrganization con el despacho
 * del admin que está trabajando.
 */
class UserService
{
    /** @param  array{nombre:string, email:string, rol:string, password:string}  $data */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /** @param  array{nombre:string, email:string, rol:string, password?:string|null}  $data */
    public function update(User $user, array $data): User
    {
        if ($user->isAdmin() && $data['rol'] !== User::ROL_ADMIN) {
            $this->ensureNotLastAdmin($user, 'rol', 'No puedes quitar el rol de admin al único administrador del despacho.');
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        if ($user->isAdmin()) {
            $this->ensureNotLastAdmin($user, 'general', 'No puedes eliminar al único administrador del despacho.');
        }

        if (Client::where('asesor_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'general' => 'Este usuario tiene clientes asignados. Reasígnalos a otro asesor antes de eliminarlo.',
            ]);
        }

        if (Transaction::where('created_by', $user->id)->exists() || AuditLog::where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'general' => 'Este usuario ya tiene actividad registrada (transacciones o auditoría); se conserva para mantener el historial.',
            ]);
        }

        $user->delete();
    }

    private function ensureNotLastAdmin(User $user, string $campo, string $mensaje): void
    {
        $admins = User::where('organization_id', $user->organization_id)
            ->where('rol', User::ROL_ADMIN)
            ->count();

        if ($admins <= 1) {
            throw ValidationException::withMessages([$campo => $mensaje]);
        }
    }
}
