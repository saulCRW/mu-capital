<?php

namespace App\Modules\Portfolios\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Services\AssetDetail;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PortfolioAssetController extends Controller
{
    /** Detalle de un activo del portafolio: KPI de la posición, gráfica de cierres con operaciones, transacciones y dividendos. */
    public function __invoke(Portfolio $portfolio, Asset $asset, AnalyticsService $analytics, AssetDetail $detalle): View
    {
        Gate::authorize('view', $portfolio);

        $hoy = today()->toDateString();
        $saldo = $analytics->saldo($portfolio, $hoy);
        $datos = $saldo['vacio'] ? null : $detalle->calcular($portfolio, $asset, $saldo['estado'], $hoy);

        abort_if($datos === null, 404);

        return view('portfolios.activo', ['portfolio' => $portfolio, 'asset' => $asset, 'hasta' => $hoy] + $datos);
    }
}
