<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Superadmin → administración de usuarios. Usuario → dashboard de sus portafolios. */
    public function __invoke(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuperadmin()) {
            return redirect()->route('superadmin.users.index');
        }

        return view('dashboard', [
            'dashboard' => $dashboard->resumen($user, today()->toDateString()),
        ]);
    }
}
