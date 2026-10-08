<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\DosFactoresCodigoRequest;
use App\Modules\Auth\Services\DosFactores;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Segundo paso del inicio de sesión: la contraseña ya fue correcta y falta el código del doble factor. */
class DosFactoresLoginController extends Controller
{
    /** Intentos fallidos permitidos por minuto para el mismo usuario. */
    public const MAX_INTENTOS = 5;

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->pendiente($request) === null) {
            return redirect()->route('login');
        }

        return view('auth.dos-factores');
    }

    public function store(DosFactoresCodigoRequest $request, DosFactores $dosFactores): RedirectResponse
    {
        $user = $this->pendiente($request);
        if ($user === null) {
            return redirect()->route('login')->withErrors(['email' => 'Pasó demasiado tiempo. Vuelve a escribir tu contraseña.']);
        }

        $clave = 'dos-factores|'.$user->id;
        if (RateLimiter::tooManyAttempts($clave, self::MAX_INTENTOS)) {
            throw ValidationException::withMessages(['codigo' => 'Demasiados intentos. Espera '.RateLimiter::availableIn($clave).' segundos.']);
        }

        if (! $dosFactores->verificar($user, $request->validated('codigo'))) {
            RateLimiter::hit($clave, 60);

            throw ValidationException::withMessages(['codigo' => 'El código no es correcto o ya se usó.']);
        }

        RateLimiter::clear($clave);
        $request->session()->forget(DosFactores::SESION_LOGIN);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Usuario que dio su contraseña hace menos de DosFactores::MINUTOS_LOGIN minutos. */
    private function pendiente(Request $request): ?User
    {
        $pendiente = $request->session()->get(DosFactores::SESION_LOGIN);
        if (! is_array($pendiente) || $pendiente['hasta'] < now()->getTimestamp()) {
            return null;
        }

        $user = User::find($pendiente['id']);

        return $user?->tieneDosFactores() ? $user : null;
    }
}
