<?php

namespace App\Modules\Prices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Services\QuoteService;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Mercado en vivo: la página se actualiza sola consultando cotizaciones() cada pocos segundos. */
class MarketController extends Controller
{
    public function index(Request $request): View
    {
        // Primero los activos que el usuario tiene en sus portafolios.
        $mios = Transaction::whereNotNull('asset_id')->distinct()->pluck('asset_id')->all();

        return view('mercado', [
            'activos' => Asset::where('tipo', '!=', 'efectivo')->orderBy('simbolo')->get()
                ->sortByDesc(fn (Asset $a) => in_array($a->id, $mios, true))->values(),
            'mios' => $mios,
        ]);
    }

    /** Cotizaciones de todo el catálogo (página Mercado) o de los activos de un portafolio (?portfolio=ID). */
    public function cotizaciones(Request $request, QuoteService $cotizaciones): JsonResponse
    {
        if ($request->filled('portfolio')) {
            $portfolio = Portfolio::findOrFail($request->integer('portfolio'));
            Gate::authorize('view', $portfolio);
            $activos = Asset::whereIn('id', $portfolio->transactions()->whereNotNull('asset_id')->select('asset_id'))->get();

            return response()->json($cotizaciones->cotizar($activos));
        }

        return response()->json($cotizaciones->cotizar(Asset::where('tipo', '!=', 'efectivo')->get(), conIndices: true));
    }
}
