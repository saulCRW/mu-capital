<?php

namespace App\Modules\Portfolios\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Requests\PortfolioRequest;
use App\Modules\Portfolios\Requests\PortfolioShowRequest;
use App\Modules\Portfolios\Services\HoldingsTable;
use App\Modules\Portfolios\Services\PortfolioOverview;
use App\Modules\Portfolios\Services\PortfolioService;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Transactions\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __construct(private PortfolioService $service) {}

    /** Mis portafolios (el Global Scope ya limita a los del usuario). */
    public function index(): View
    {
        Gate::authorize('create', Portfolio::class);

        return view('portfolios.index', [
            'portfolios' => Portfolio::with('benchmark:id,nombre')->withCount('transactions')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Portfolio::class);

        return view('portfolios.create', ['benchmarks' => Benchmark::orderBy('nombre')->get()]);
    }

    public function store(PortfolioRequest $request): RedirectResponse
    {
        $portfolio = $this->service->create($request->user(), $request->validated());

        return redirect()->route('portfolios.show', $portfolio)->with('status', "Portafolio \"{$portfolio->nombre}\" creado.");
    }

    /**
     * Página del portafolio: KPI valuados a hoy y tres pestañas (?vista=):
     * resumen (gráfica, asignación y top), holdings (tabla ordenable y agrupable)
     * y transacciones (filtrables y paginadas). Solo se calcula la pestaña abierta.
     */
    public function show(PortfolioShowRequest $request, Portfolio $portfolio, TransactionService $transactions, AnalyticsService $analytics): View
    {
        $hoy = today()->toDateString();
        $vista = $request->vista();
        $saldo = $analytics->saldo($portfolio->load('benchmark:id,nombre'), $hoy);
        $opciones = $request->holdings();
        $filtros = $request->filtros();

        return view('portfolios.show', [
            'portfolio' => $portfolio,
            'saldo' => $saldo,
            'vista' => $vista,
            'resumen' => $vista === 'resumen' && ! $saldo['vacio'] ? PortfolioOverview::calcular($portfolio, $saldo['estado'], $hoy) : null,
            'holdings' => $vista === 'holdings' && ! $saldo['vacio']
                ? HoldingsTable::armar($saldo['estado']['posiciones'], $saldo['estado']['valor_total'], $opciones['agrupar'], $opciones['orden'], $opciones['dir'], $opciones['vendidas'])
                : null,
            'opciones' => $opciones,
            'transactions' => $vista === 'transacciones' ? $transactions->filtrar($portfolio, $filtros)->paginate(50)->withQueryString() : null,
            'filtros' => $filtros,
            'activos' => $vista === 'transacciones' ? Asset::whereIn('id', $portfolio->transactions()->select('asset_id'))->orderBy('simbolo')->get(['id', 'simbolo']) : collect(),
        ]);
    }

    public function edit(Portfolio $portfolio): View
    {
        Gate::authorize('update', $portfolio);

        return view('portfolios.edit', ['portfolio' => $portfolio, 'benchmarks' => Benchmark::orderBy('nombre')->get()]);
    }

    public function update(PortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $this->service->update($portfolio, $request->validated());

        return redirect()->route('portfolios.show', $portfolio)->with('status', 'Portafolio actualizado.');
    }

    public function destroy(Portfolio $portfolio): RedirectResponse
    {
        Gate::authorize('delete', $portfolio);

        $this->service->delete($portfolio);

        return redirect()->route('portfolios.index')->with('status', "Portafolio \"{$portfolio->nombre}\" eliminado.");
    }
}
