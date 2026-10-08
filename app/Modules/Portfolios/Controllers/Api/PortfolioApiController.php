<?php

namespace App\Modules\Portfolios\Controllers\Api;

use App\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Resources\PortfolioResource;
use App\Modules\Portfolios\Services\HoldingsTable;
use App\Modules\Transactions\Requests\TransactionFilterRequest;
use App\Modules\Transactions\Resources\TransactionResource;
use App\Modules\Transactions\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * API v1 de portafolios (solo lectura). Usa la sesión del navegador y el mismo
 * Global Scope que las pantallas: cada usuario solo ve lo suyo.
 */
class PortfolioApiController extends Controller
{
    public function __construct(private AnalyticsService $analytics) {}

    /** GET /api/v1/portfolios */
    public function index(): JsonResponse
    {
        Gate::authorize('create', Portfolio::class);

        return ApiResponse::ok(PortfolioResource::collection(Portfolio::with('benchmark:id,nombre')->orderBy('nombre')->get()));
    }

    /** GET /api/v1/portfolios/{id}: datos y KPI valuados a hoy. */
    public function show(Portfolio $portfolio): JsonResponse
    {
        Gate::authorize('view', $portfolio);

        return ApiResponse::ok((new PortfolioResource($portfolio->load('benchmark:id,nombre')))->conSaldo($this->analytics->saldo($portfolio, today()->toDateString())));
    }

    /** GET /api/v1/portfolios/{id}/holdings?vendidas=1: posiciones valuadas a hoy, de mayor a menor valor. */
    public function holdings(Request $request, Portfolio $portfolio): JsonResponse
    {
        Gate::authorize('view', $portfolio);
        $vendidas = $request->validate(['vendidas' => ['nullable', 'boolean']])['vendidas'] ?? false;

        $saldo = $this->analytics->saldo($portfolio, today()->toDateString());
        if ($saldo['vacio']) {
            return ApiResponse::ok([]);
        }

        $tabla = HoldingsTable::armar($saldo['estado']['posiciones'], $saldo['estado']['valor_total'], null, 'valor', 'desc', (bool) $vendidas);
        $campos = ['asset_id', 'simbolo', 'nombre', 'tipo', 'sector', 'pais', 'exchange', 'moneda', 'cantidad', 'costo_promedio_local', 'precio',
            'valor_base', 'peso', 'gp_no_realizada', 'gp_realizada', 'ingresos', 'rendimiento_pct', 'abierta'];

        return ApiResponse::ok(array_map(fn ($p) => array_intersect_key($p, array_flip($campos)), $tabla['grupos'][0]['filas'] ?? []));
    }

    /** GET /api/v1/portfolios/{id}/transactions?tipo=&asset_id=&desde=&hasta=&page= (50 por página). */
    public function transactions(TransactionFilterRequest $request, Portfolio $portfolio, TransactionService $transacciones): JsonResponse
    {
        $pagina = $transacciones->filtrar($portfolio, $request->validated())->paginate(50)->withQueryString();

        return ApiResponse::ok(TransactionResource::collection($pagina->getCollection()), extra: ['paginacion' => [
            'pagina' => $pagina->currentPage(),
            'por_pagina' => $pagina->perPage(),
            'total' => $pagina->total(),
            'ultima_pagina' => $pagina->lastPage(),
        ]]);
    }
}
