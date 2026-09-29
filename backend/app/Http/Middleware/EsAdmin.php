<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsAdmin
{
    /**
     * Roles con acceso al panel de administración.
     */
    private const ROLES_ADMIN = ['admin', 'administrador'];

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $rol = $usuario?->rol?->nombre;

        if (!$rol || !in_array(strtolower($rol), self::ROLES_ADMIN, true)) {
            return response()->json(['message' => 'No tienes permisos de administrador.'], 403);
        }

        return $next($request);
    }
}
