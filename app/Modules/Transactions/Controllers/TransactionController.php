<?php

namespace App\Modules\Transactions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Analytics\Services\PriceBook;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Requests\TransactionRequest;
use App\Modules\Transactions\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $service) {}

    public function create(Portfolio $portfolio): View
    {
        Gate::authorize('create', [Transaction::class, $portfolio]);

        return view('transactions.create', ['portfolio' => $portfolio, 'activos' => $this->activos()]);
    }

    /**
     * Vista de compra y venta: muestra el efectivo disponible, lo que se tiene
     * de cada activo y su último precio. Registra con store(), así que las
     * validaciones son las mismas que en la captura general.
     */
    public function operar(Request $request, Portfolio $portfolio, AnalyticsService $analytics): View
    {
        Gate::authorize('create', [Transaction::class, $portfolio]);

        $hoy = today()->toDateString();
        $saldo = $analytics->saldo($portfolio, $hoy);
        $tenencia = collect($saldo['estado']['posiciones'] ?? [])->pluck('cantidad', 'asset_id');
        $activos = $this->activos();
        $precios = PriceBook::cargarUltimos(
            $activos->pluck('id')->all(),
            $activos->pluck('moneda')->unique()->reject(fn ($m) => $m === $portfolio->moneda_base)->values()->all(),
            $portfolio->moneda_base,
            $hoy,
        );

        return view('transactions.operar', [
            'portfolio' => $portfolio,
            'efectivo' => $saldo['estado']['efectivo'] ?? '0',
            'activos' => $activos->map(fn (Asset $a) => [
                'id' => $a->id,
                'simbolo' => $a->simbolo,
                'nombre' => $a->nombre,
                'moneda' => $a->moneda,
                'tenencia' => $tenencia[$a->id] ?? '0',
                'precio' => $precios->precio($a->id, $hoy),
                'tasa' => $precios->tasa($a->moneda, $portfolio->moneda_base, $hoy),
            ]),
            'tipo' => $request->query('tipo') === Transaction::VENTA ? Transaction::VENTA : Transaction::COMPRA,
            'assetId' => (int) $request->query('asset_id'),
            'cantidad' => is_numeric($request->query('cantidad')) ? (string) $request->query('cantidad') : null,
        ]);
    }

    public function store(TransactionRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $this->service->create($portfolio, $request->validated(), $request->user());

        return redirect()->route('portfolios.show', $portfolio)->with('status', 'Transacción registrada.');
    }

    public function edit(Transaction $transaction): View
    {
        Gate::authorize('update', $transaction);

        return view('transactions.edit', [
            'transaction' => $transaction,
            'portfolio' => $transaction->portfolio,
            'activos' => $this->activos(),
        ]);
    }

    public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->service->update($transaction, $request->validated());

        return redirect()->route('portfolios.show', $transaction->portfolio_id)->with('status', 'Transacción actualizada.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        Gate::authorize('delete', $transaction);

        $this->service->delete($transaction);

        return redirect()->route('portfolios.show', $transaction->portfolio_id)->with('status', 'Transacción eliminada.');
    }

    private function activos()
    {
        return Asset::orderBy('simbolo')->get(['id', 'simbolo', 'nombre', 'moneda']);
    }
}
