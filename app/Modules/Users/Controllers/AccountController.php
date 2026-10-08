<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\DosFactores;
use App\Modules\Users\Requests\AccountRequest;
use App\Modules\Users\Services\UserDataExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** "Mi cuenta": cada usuario edita sus datos, su contraseña y su tasa libre de riesgo. */
class AccountController extends Controller
{
    public function edit(Request $request, DosFactores $dosFactores): View
    {
        $user = $request->user();
        // Clave generada con "Activar" que falta confirmar: se muestra su QR.
        $clave = $user->tieneDosFactores() ? null : $request->session()->get(DosFactores::SESION_PENDIENTE);

        return view('cuenta.edit', [
            'user' => $user,
            'clavePendiente' => $clave,
            'qr' => $clave === null ? null : $dosFactores->qrSvg($dosFactores->uri($user, $clave)),
        ]);
    }

    public function update(AccountRequest $request): RedirectResponse
    {
        // password_actual solo sirve para validar; no se guarda.
        $datos = $request->safe()->except(['password_actual']);
        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        if (array_key_exists('tasa_libre_riesgo', $datos)) {
            // Se captura en porcentaje (10.5) y se guarda como fracción (0.105).
            $datos['tasa_libre_riesgo'] = bcdiv((string) $datos['tasa_libre_riesgo'], '100', 6);
        }

        $request->user()->update($datos);

        return redirect()->route('cuenta.edit')->with('status', 'Tu cuenta se actualizó.');
    }

    /** Descarga todos los datos del usuario en un .zip (un CSV por tema). */
    public function exportar(Request $request, UserDataExport $exportacion): BinaryFileResponse
    {
        abort_if($request->user()->isSuperadmin(), 403);

        return response()->download($exportacion->zip($request->user()), 'mu-intelligence-mis-datos-'.today()->toDateString().'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
