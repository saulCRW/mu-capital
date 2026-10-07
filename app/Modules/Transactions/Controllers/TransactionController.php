<?php

namespace App\Modules\Transactions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Requests\TransactionRequest;
use App\Modules\Transactions\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $service)
    {
    }

    public function create(Portfolio $portfolio): View
    {
        Gate::authorize('create', [Transaction::class, $portfolio]);

        return view('transactions.create', ['portfolio' => $portfolio, 'activos' => $this->activos()]);
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
