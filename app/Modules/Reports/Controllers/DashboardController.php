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
    /** Superadmin → despachos. Admin y asesor → dashboard de portafolios. Cliente → pantalla provisional (aún no hay portal). */
    public function __invoke(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.organizations.index');
        }

        return view('dashboard', [
            'dashboard' => $user->rol === User::ROL_CLIENTE ? null : $dashboard->resumen($user, today()->toDateString()),
        ]);
    }
}
