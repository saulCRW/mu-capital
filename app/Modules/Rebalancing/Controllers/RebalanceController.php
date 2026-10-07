<?php

namespace App\Modules\Rebalancing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AllocationCalculator;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Rebalancing\Requests\TargetAllocationRequest;
use App\Modules\Rebalancing\Services\RebalanceCalculator;
use App\Modules\Rebalancing\Services\RebalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RebalanceController extends Controller
{
    public function __construct(private RebalanceService $service) {}

    public function show(Request $request, Portfolio $portfolio): View
    {
        Gate::authorize('update', $portfolio);

        $dimension = in_array($request->query('dimension'), AllocationCalculator::DIMENSIONES, true)
            ? $request->query('dimension')
            : ($portfolio->targetAllocations()->value('dimension') ?? 'clase');

        return view('rebalancing.show', [
            'portfolio' => $portfolio,
            'analisis' => $this->service->analizar($portfolio, $dimension, today()->toDateString()),
            'tolerancia' => RebalanceCalculator::TOLERANCIA,
        ]);
    }

    public function update(TargetAllocationRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $this->service->guardar($portfolio, $request->validated('dimension'), $request->validated('objetivos', []));

        return redirect()->route('portfolios.rebalanceo', [$portfolio, 'dimension' => $request->validated('dimension')])
            ->with('status', 'Asignación objetivo guardada.');
    }
}
