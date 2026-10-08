<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\DesactivarDosFactoresRequest;
use App\Modules\Auth\Requests\DosFactoresCodigoRequest;
use App\Modules\Auth\Services\DosFactores;
use App\Modules\Auth\Services\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Doble factor de la propia cuenta (sección de Mi cuenta): generar la clave,
 * confirmarla con un código de la app y desactivarlo.
 */
class DosFactoresController extends Controller
{
    /** Genera una clave nueva; queda en la sesión hasta que se confirme con un código. */
    public function iniciar(Request $request): RedirectResponse
    {
        if (! $request->user()->tieneDosFactores()) {
            $request->session()->put(DosFactores::SESION_PENDIENTE, Totp::nuevaClave());
        }

        return redirect()->to(route('cuenta.edit').'#dos-factores');
    }

    public function confirmar(DosFactoresCodigoRequest $request, DosFactores $dosFactores): RedirectResponse
    {
        $clave = $request->session()->get(DosFactores::SESION_PENDIENTE);
        if ($clave === null || $request->user()->tieneDosFactores()) {
            return redirect()->route('cuenta.edit');
        }

        $codigos = $dosFactores->activar($request->user(), $clave, $request->validated('codigo'));
        if ($codigos === null) {
            throw ValidationException::withMessages(['codigo' => 'El código no es correcto. Revisa que la hora de tu teléfono sea la automática.'])
                ->redirectTo(route('cuenta.edit').'#dos-factores');
        }

        $request->session()->forget(DosFactores::SESION_PENDIENTE);

        return redirect()->to(route('cuenta.edit').'#dos-factores')
            ->with('status', 'Doble factor activado.')
            ->with('codigos_recuperacion', $codigos);
    }

    public function desactivar(DesactivarDosFactoresRequest $request, DosFactores $dosFactores): RedirectResponse
    {
        $user = $request->user();
        if (! $dosFactores->verificar($user, $request->validated('codigo'))) {
            throw ValidationException::withMessages(['codigo' => 'El código no es correcto o ya se usó.'])
                ->errorBag('dosFactores')
                ->redirectTo(route('cuenta.edit').'#dos-factores');
        }

        $dosFactores->desactivar($user);

        return redirect()->route('cuenta.edit')->with('status', 'Doble factor desactivado.');
    }
}
