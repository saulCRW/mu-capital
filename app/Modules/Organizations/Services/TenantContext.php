<?php

namespace App\Modules\Organizations\Services;

use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Dice a qué organización (despacho) pertenece la petición actual.
 *
 * - Sin usuario autenticado (login, consola, seeders): no hay filtro.
 * - Superadmin: no hay filtro, ve todos los despachos.
 * - Cualquier otro rol: solo su organization_id.
 */
final class TenantContext
{
    /**
     * hasUser() solo pregunta si el usuario YA está cargado; no consulta la base.
     * Así evitamos un ciclo infinito: el guard carga al User, y User tiene el scope.
     */
    public static function user(): ?User
    {
        $guard = Auth::guard();

        return $guard->hasUser() ? $guard->user() : null;
    }

    /** true cuando la petición NO debe filtrarse por organización. */
    public static function isGlobal(): bool
    {
        $user = self::user();

        return $user === null || $user->rol === User::ROL_SUPERADMIN;
    }

    /** organization_id del usuario actual, o null si la petición es global. */
    public static function organizationId(): ?int
    {
        return self::isGlobal() ? null : self::user()->organization_id;
    }
}
