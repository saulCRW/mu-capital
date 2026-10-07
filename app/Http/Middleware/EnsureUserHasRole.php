<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "role": uso en rutas como ->middleware('role:admin') o
 * ->middleware('role:admin,asesor'). Responde 403 si el rol no está permitido.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user() !== null && in_array($request->user()->rol, $roles, true), 403);

        return $next($request);
    }
}
