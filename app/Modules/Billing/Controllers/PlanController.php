<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Services\PlanLimits;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Plan del despacho y cuánto se usa de cada límite (solo admin). */
class PlanController extends Controller
{
    public function __invoke(Request $request, PlanLimits $limites, TransactionService $transacciones): View
    {
        $organization = $request->user()->organization->load('plan');
        $posiciones = Portfolio::all()->mapWithKeys(fn (Portfolio $p) => [$p->id => $transacciones->posicionesAbiertas($p)])->all();

        return view('admin.plan', [
            'plan' => $organization->plan,
            'uso' => $limites->uso($organization, $posiciones),
        ]);
    }
}
