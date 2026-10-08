<?php

namespace App\Modules\Prices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Prices\Requests\BenchmarkRequest;
use App\Modules\Prices\Requests\BenchmarkValueRequest;
use App\Modules\Prices\Requests\FxRateRequest;
use App\Modules\Prices\Requests\PriceImportRequest;
use App\Modules\Prices\Requests\PriceRequest;
use App\Modules\Prices\Services\PriceCaptureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla de Precios (solo superadmin: los precios y tipos de cambio son de toda la
 * plataforma). Captura manual e importación de cierres, tipos de cambio y valores de índices.
 */
class PriceController extends Controller
{
    public function __construct(private PriceCaptureService $precios) {}

    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['tipos', 'indices'], true) ? $request->query('tab') : 'precios';
        $activo = $request->integer('asset_id') ?: null;
        $indice = $request->integer('benchmark_id') ?: null;

        return view('superadmin.precios.index', [
            'tab' => $tab,
            'activos' => Asset::where('tipo', '!=', 'efectivo')->orderBy('simbolo')->get(['id', 'simbolo', 'nombre', 'exchange', 'moneda']),
            'activo' => $activo,
            'precios' => $tab === 'precios'
                ? Price::with('asset:id,simbolo,moneda')->when($activo, fn ($q) => $q->where('asset_id', $activo))
                    ->orderByDesc('fecha')->orderBy('asset_id')->paginate(50)->withQueryString()
                : null,
            'tasas' => $tab === 'tipos' ? FxRate::orderByDesc('fecha')->orderBy('moneda_origen')->paginate(50)->withQueryString() : null,
            'indices' => Benchmark::withCount('values')->orderBy('nombre')->get(),
            'indice' => $indice,
            'valores' => $tab === 'indices'
                ? BenchmarkValue::with('benchmark:id,nombre')->when($indice, fn ($q) => $q->where('benchmark_id', $indice))
                    ->orderByDesc('fecha')->orderBy('benchmark_id')->paginate(50)->withQueryString()
                : null,
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

    public function storeIndice(BenchmarkRequest $request): RedirectResponse
    {
        $benchmark = Benchmark::create($request->validated());

        return redirect()->route('superadmin.precios.index', ['tab' => 'indices', 'benchmark_id' => $benchmark->id])
            ->with('status', "Índice {$benchmark->nombre} creado; ya puedes capturar sus valores y elegirlo como benchmark de un portafolio.");
    }

    public function storeValorIndice(BenchmarkValueRequest $request): RedirectResponse
    {
        $benchmark = Benchmark::findOrFail($request->integer('benchmark_id'));
        $this->precios->guardarValorIndice($benchmark, $request->validated('fecha'), (string) $request->validated('valor'));

        return redirect()->route('superadmin.precios.index', ['tab' => 'indices', 'benchmark_id' => $benchmark->id])
            ->with('status', "Valor de {$benchmark->nombre} guardado.");
    }

    public function importar(PriceImportRequest $request): RedirectResponse
    {
        $r = $this->precios->importar($request->file('archivo')->get(), today()->toDateString());
        $destino = redirect()->route('superadmin.precios.index', ['tab' => match ($r['tipo']) {
            'tasas' => 'tipos',
            'indices' => 'indices',
            default => 'precios',
        }]);

        if ($r['errores'] !== []) {
            return $destino->withErrors(['importacion' => $r['errores']]);
        }

        return $destino->with('status', match ($r['tipo']) {
            'tasas' => "Se importaron {$r['guardados']} tipos de cambio; los portafolios se recalcularon.",
            'indices' => "Se importaron {$r['guardados']} valores de índices.",
            default => "Se importaron {$r['guardados']} precios; los portafolios se recalcularon.",
        });
    }
}
