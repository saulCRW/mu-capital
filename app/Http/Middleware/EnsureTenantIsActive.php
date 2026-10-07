<?php

namespace App\Http\Middleware;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Users\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware "tenant": el usuario (salvo superadmin) debe pertenecer a un
 * despacho activo. Si el despacho se suspende, la sesión se cierra en la
 * siguiente petición.
 */
class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->rol === User::ROL_SUPERADMIN) {
            return $next($request);
        }

        if ($user->organization?->estado !== Organization::ESTADO_ACTIVA) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('auth.suspended')]);
        }

        return $next($request);
    }
}
