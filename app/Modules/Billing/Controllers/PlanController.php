<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Services\PlanLimits;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Plan del usuario y cuánto usa de cada límite. */
class PlanController extends Controller
{
    public function __invoke(Request $request, PlanLimits $limites, TransactionService $transacciones): View
    {
        $user = $request->user()->load('plan');
        $posiciones = Portfolio::all()->mapWithKeys(fn (Portfolio $p) => [$p->id => $transacciones->posicionesAbiertas($p)])->all();

        return view('plan', [
            'plan' => $user->plan,
            'uso' => $limites->uso($user, $posiciones),
        ]);
    }
}
