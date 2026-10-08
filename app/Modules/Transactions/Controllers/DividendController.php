<?php

namespace App\Modules\Transactions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Transactions\Models\ExpectedDividend;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Requests\ExpectedDividendRequest;
use App\Modules\Transactions\Services\DividendCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Calendario de dividendos esperados del usuario (?mes=AAAA-MM). */
class DividendController extends Controller
{
    public function index(Request $request, DividendCalendar $calendario): View
    {
        $mes = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('mes')) ? $request->query('mes') : now()->format('Y-m');
        $enCartera = Transaction::whereNotNull('asset_id')->distinct()->pluck('asset_id')->all();

        return view('dividendos.index', $calendario->mes($request->user(), $mes, today()->toDateString()) + [
            // Primero los activos que tiene en sus portafolios.
            'activos' => Asset::where('tipo', '!=', 'efectivo')->orderBy('simbolo')->get(['id', 'simbolo', 'nombre', 'moneda'])
                ->sortByDesc(fn (Asset $a) => in_array($a->id, $enCartera, true))->values(),
        ]);
    }

    public function store(ExpectedDividendRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $asset = Asset::findOrFail($datos['asset_id']);
        $dividendo = ExpectedDividend::create($datos + ['user_id' => $request->user()->id, 'moneda' => $asset->moneda]);

        return redirect()->route('dividendos', ['mes' => $dividendo->fecha_pago->format('Y-m')])
            ->with('status', "Dividendo de {$asset->simbolo} agregado al calendario.");
    }

    public function destroy(ExpectedDividend $dividendo): RedirectResponse
    {
        $mes = $dividendo->fecha_pago->format('Y-m');
        $dividendo->delete();

        return redirect()->route('dividendos', ['mes' => $mes])->with('status', 'Dividendo eliminado del calendario.');
    }
}
