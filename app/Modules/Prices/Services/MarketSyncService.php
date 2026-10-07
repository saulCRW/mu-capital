<?php

namespace App\Modules\Prices\Services;

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Organizations\Services\TenantContext;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Prices\Services\Providers\FxProvider;
use App\Modules\Prices\Services\Providers\PriceProvider;
use App\Modules\Prices\Services\Providers\ProviderException;
use App\Modules\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use LogicException;

/**
 * Trae de los proveedores los cierres de los activos en uso, los valores de
 * los benchmarks y los tipos de cambio a MXN; los guarda y recalcula los
 * snapshots de todos los portafolios desde el primer día que cambió.
 *
 * Los datos de mercado son globales (de todos los despachos), por eso solo
 * corre sin filtro de despacho: consola, tareas programadas o superadmin.
 */
class MarketSyncService
{
    public const CACHE_ULTIMA = 'mercado:ultima_sincronizacion';

    /** Días que se vuelven a pedir antes del último dato guardado (el proveedor puede corregir cierres). */
    private const REPASO = 5;

    /** Historial que se pide la primera vez que se sincroniza un activo, índice o moneda. */
    private const HISTORIAL_INICIAL = 400;

    public function __construct(
        private PriceProvider $precios,
        private FxProvider $fx,
        private SnapshotService $snapshots,
    ) {}

    /**
     * @return array{precios:int, indices:int, tasas:int, portafolios:int, errores:list<string>, avisos:list<string>}
     */
    public function sincronizar(?string $desde = null, ?string $hasta = null): array
    {
        if (! TenantContext::isGlobal()) {
            throw new LogicException('La sincronización de mercado recalcula todos los despachos: solo corre desde consola o como superadmin.');
        }

        $hasta ??= CarbonImmutable::today()->toDateString();
        $r = ['precios' => 0, 'indices' => 0, 'tasas' => 0, 'portafolios' => 0, 'errores' => [], 'avisos' => []];
        $primerCambio = null;
        $ahora = now();

        // 1. Activos que valúan algún portafolio.
        $enUso = Transaction::whereNotNull('asset_id')->distinct()->pluck('asset_id');
        $activos = Asset::whereIn('id', $enUso)->orderBy('simbolo')->get();
        foreach ($activos as $asset) {
            $simbolo = $this->precios->simboloPara($asset);
            if ($simbolo === null) {
                continue;
            }
            $inicio = $desde ?? $this->inicio(Price::where('asset_id', $asset->id)->max('fecha'), $hasta);
            $cierres = $this->consultar(fn () => $this->precios->cierres($simbolo, $inicio, $hasta), $r['errores']);
            if ($cierres) {
                Price::upsert($this->filas($cierres, ['asset_id' => $asset->id], 'cierre', $ahora), ['asset_id', 'fecha'], ['cierre', 'updated_at']);
                $r['precios'] += count($cierres);
                $primerCambio = min($primerCambio ?? array_key_first($cierres), array_key_first($cierres));
            }
        }

        // 2. Benchmarks con símbolo en el proveedor.
        foreach (Benchmark::whereNotNull('simbolo_proveedor')->get() as $benchmark) {
            $inicio = $desde ?? $this->inicio(BenchmarkValue::where('benchmark_id', $benchmark->id)->max('fecha'), $hasta);
            $valores = $this->consultar(fn () => $this->precios->cierres($benchmark->simbolo_proveedor, $inicio, $hasta), $r['errores']);
            if ($valores) {
                BenchmarkValue::upsert($this->filas($valores, ['benchmark_id' => $benchmark->id], 'valor', $ahora), ['benchmark_id', 'fecha'], ['valor', 'updated_at']);
                $r['indices'] += count($valores);
                $primerCambio = min($primerCambio ?? array_key_first($valores), array_key_first($valores));
            }
        }

        // 3. Tipos de cambio a MXN de las monedas de los activos y portafolios.
        $monedas = $activos->pluck('moneda')->merge(Portfolio::distinct()->pluck('moneda_base'))
            ->unique()->reject(fn ($m) => $m === 'MXN')->values();
        if ($monedas->isNotEmpty() && ! $this->fx->disponible()) {
            $r['avisos'][] = 'Sin BANXICO_TOKEN: no se actualizaron los tipos de cambio ('.$monedas->implode(', ').').';
        } elseif ($monedas->isNotEmpty()) {
            foreach ($monedas as $moneda) {
                if (! in_array($moneda, $this->fx->monedas(), true)) {
                    $r['avisos'][] = "Banxico no publica el tipo de cambio de {$moneda}; captúralo a mano.";

                    continue;
                }
                $inicio = $desde ?? $this->inicio(FxRate::where('moneda_origen', $moneda)->where('moneda_destino', 'MXN')->max('fecha'), $hasta);
                $tasas = $this->consultar(fn () => $this->fx->tasasAPesos($moneda, $inicio, $hasta), $r['errores']);
                if ($tasas) {
                    FxRate::upsert($this->filas($tasas, ['moneda_origen' => $moneda, 'moneda_destino' => 'MXN'], 'tasa', $ahora), ['moneda_origen', 'moneda_destino', 'fecha'], ['tasa', 'updated_at']);
                    $r['tasas'] += count($tasas);
                    $primerCambio = min($primerCambio ?? array_key_first($tasas), array_key_first($tasas));
                }
            }
        }

        // 4. Las valuaciones diarias cambian desde el primer dato nuevo.
        if ($primerCambio !== null) {
            foreach (Portfolio::orderBy('id')->get() as $portfolio) {
                $this->snapshots->recalcular($portfolio, $primerCambio);
                $r['portafolios']++;
            }
        }

        Cache::forever(self::CACHE_ULTIMA, ['momento' => $ahora->toIso8601String()] + $r);

        return $r;
    }

    /** Desde dónde pedir: unos días antes del último dato guardado, o un historial inicial. */
    private function inicio(?string $ultimo, string $hasta): string
    {
        return $ultimo
            ? CarbonImmutable::parse($ultimo)->subDays(self::REPASO)->toDateString()
            : CarbonImmutable::parse($hasta)->subDays(self::HISTORIAL_INICIAL)->toDateString();
    }

    /**
     * Un proveedor que falla no detiene la sincronización: se anota y se sigue.
     *
     * @param  list<string>  $errores
     * @return array<string, string>
     */
    private function consultar(callable $consulta, array &$errores): array
    {
        try {
            return $consulta();
        } catch (ProviderException $e) {
            $errores[] = $e->getMessage();

            return [];
        }
    }

    /**
     * @param  array<string, string>  $valores  fecha => valor
     * @param  array<string, mixed>  $fijos
     * @return list<array<string, mixed>>
     */
    private function filas(array $valores, array $fijos, string $columna, $ahora): array
    {
        $filas = [];
        foreach ($valores as $fecha => $valor) {
            $filas[] = $fijos + ['fecha' => $fecha, $columna => $valor, 'created_at' => $ahora, 'updated_at' => $ahora];
        }

        return $filas;
    }
}
