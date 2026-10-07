<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\DashboardService;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Superadmin → despachos. Admin, asesor y cliente → dashboard de los portafolios que pueden ver. */
    public function __invoke(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.organizations.index');
        }

        return view('dashboard', [
            // Un usuario cliente sin cliente ligado no tiene nada que consultar todavía.
            'dashboard' => $user->rol === User::ROL_CLIENTE && ! $user->client_id ? null : $dashboard->resumen($user, today()->toDateString()),
        ]);
    }
}
