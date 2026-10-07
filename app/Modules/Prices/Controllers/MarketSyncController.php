<?php

namespace App\Modules\Prices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Prices\Services\MarketSyncService;
use Illuminate\Http\RedirectResponse;

/** Sincronización manual de mercado (solo superadmin: recalcula los portafolios de todos los usuarios). */
class MarketSyncController extends Controller
{
    public function __invoke(MarketSyncService $mercado): RedirectResponse
    {
        $r = $mercado->sincronizar();

        $estado = "Mercado actualizado: {$r['precios']} precios, {$r['indices']} valores de índices y {$r['tasas']} tipos de cambio; {$r['portafolios']} portafolios recalculados.";

        return back()
            ->with('status', $estado)
            ->withErrors(array_filter(['general' => implode(' ', [...$r['avisos'], ...$r['errores']])]));
    }
}
