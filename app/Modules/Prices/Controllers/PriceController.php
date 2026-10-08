<?php

namespace App\Modules\Prices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Prices\Requests\FxRateRequest;
use App\Modules\Prices\Requests\PriceImportRequest;
use App\Modules\Prices\Requests\PriceRequest;
use App\Modules\Prices\Services\PriceCaptureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de Precios (solo superadmin: los precios y tipos de cambio son de toda la
 * plataforma). Captura manual e importación de cierres y tipos de cambio.
 */
class PriceController extends Controller
{
    public function __construct(private PriceCaptureService $precios) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'tipos' ? 'tipos' : 'precios';
        $activo = $request->integer('asset_id') ?: null;

        return view('superadmin.precios.index', [
            'tab' => $tab,
            'activos' => Asset::where('tipo', '!=', 'efectivo')->orderBy('simbolo')->get(['id', 'simbolo', 'nombre', 'exchange', 'moneda']),
            'activo' => $activo,
            'precios' => $tab === 'precios'
                ? Price::with('asset:id,simbolo,moneda')->when($activo, fn ($q) => $q->where('asset_id', $activo))
                    ->orderByDesc('fecha')->orderBy('asset_id')->paginate(50)->withQueryString()
                : null,
            'tasas' => $tab === 'tipos' ? FxRate::orderByDesc('fecha')->orderBy('moneda_origen')->paginate(50)->withQueryString() : null,
        ]);
    }

    public function store(PriceRequest $request): RedirectResponse
    {
        $asset = Asset::findOrFail($request->integer('asset_id'));
        $this->precios->guardarPrecio($asset, $request->validated('fecha'), (string) $request->validated('cierre'));

        return redirect()->route('superadmin.precios.index', ['asset_id' => $asset->id])
            ->with('status', "Cierre de {$asset->simbolo} guardado; los portafolios que lo tienen se recalcularon.");
    }

    public function storeTasa(FxRateRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $this->precios->guardarTasa($datos['moneda_origen'], $datos['moneda_destino'], $datos['fecha'], (string) $datos['tasa']);

        return redirect()->route('superadmin.precios.index', ['tab' => 'tipos'])
            ->with('status', "Tipo de cambio {$datos['moneda_origen']}/{$datos['moneda_destino']} guardado; los portafolios se recalcularon.");
    }

    public function importar(PriceImportRequest $request): RedirectResponse
    {
        $r = $this->precios->importar($request->file('archivo')->get(), today()->toDateString());
        $destino = redirect()->route('superadmin.precios.index', ['tab' => $r['tipo'] === 'tasas' ? 'tipos' : 'precios']);

        if ($r['errores'] !== []) {
            return $destino->withErrors(['importacion' => $r['errores']]);
        }

        return $destino->with('status', 'Se importaron '.$r['guardados'].($r['tipo'] === 'tasas' ? ' tipos de cambio' : ' precios').'; los portafolios se recalcularon.');
    }
}
