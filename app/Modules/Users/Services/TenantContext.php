<?php

namespace App\Modules\Users\Services;

use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Dice de quién son los datos que ve la petición actual.
 *
 * - Sin usuario autenticado (login, registro, consola, tareas programadas): no hay filtro.
 * - Superadmin: no hay filtro (administra la plataforma).
 * - Usuario: solo sus propios portafolios, transacciones y valuaciones.
 */
final class TenantContext
{
    /**
     * hasUser() solo pregunta si el usuario YA está cargado; no consulta la base.
     * Así evitamos un ciclo infinito al cargar al usuario.
     */
    public static function user(): ?User
    {
        $guard = Auth::guard();

        return $guard->hasUser() ? $guard->user() : null;
    }

    /** true cuando la petición NO debe filtrarse por dueño. */
    public static function isGlobal(): bool
    {
        $user = self::user();

        return $user === null || $user->rol === User::ROL_SUPERADMIN;
    }

    /** id del usuario dueño de los datos, o null si la petición es global. */
    public static function userId(): ?int
    {
        return self::isGlobal() ? null : self::user()->id;
    }
}
