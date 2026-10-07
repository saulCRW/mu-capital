<?php

namespace App\Modules\Portfolios\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Requests\PortfolioRequest;
use App\Modules\Portfolios\Services\PortfolioService;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Transactions\Requests\TransactionFilterRequest;
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
     * Saldo actual del portafolio (valuado a hoy) y su libro de transacciones
     * (filtrable y paginado). Rendimiento por periodo y riesgo llegan en la Fase 6.
     */
    public function show(TransactionFilterRequest $request, Portfolio $portfolio, TransactionService $transactions, AnalyticsService $analytics): View
    {
        $filtros = $request->validated();

        return view('portfolios.show', [
            'portfolio' => $portfolio->load('benchmark:id,nombre'),
            'saldo' => $analytics->saldo($portfolio, today()->toDateString()),
            'transactions' => $transactions->filtrar($portfolio, $filtros)->paginate(50)->withQueryString(),
            'filtros' => $filtros,
            'activos' => Asset::whereIn('id', $portfolio->transactions()->select('asset_id'))->orderBy('simbolo')->get(['id', 'simbolo']),
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
