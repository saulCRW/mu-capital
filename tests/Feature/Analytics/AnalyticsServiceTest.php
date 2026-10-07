<?php

namespace Tests\Feature\Analytics;

use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;
use Tests\Unit\Analytics\CasoEjemplo;

/**
 * El CASO DE EJEMPLO de tests/Unit/Analytics/CasoEjemplo.php, ahora guardado
 * en la base de datos y calculado de punta a punta.
 * A = NAFTRAC (ETF, MXN) y B = AAPL (acción, USD).
 */
class AnalyticsServiceTest extends TestCase
{
    use CasoEjemplo, CreaEscenario, RefreshDatabase;

    private const HASTA = '2026-06-30';

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();

        $ids = [1 => $this->naftrac->id, 2 => $this->aapl->id];
        foreach ($this->transaccionesEjemplo() as $t) {
            Transaction::create([
                'organization_id' => $this->org->id,
                'portfolio_id' => $this->portafolio->id,
                'asset_id' => $t['asset_id'] ? $ids[$t['asset_id']] : null,
                'created_by' => $this->asesor->id,
            ] + array_intersect_key($t, array_flip(['tipo', 'fecha', 'cantidad', 'precio', 'comision', 'moneda', 'tipo_cambio'])));
        }

        Price::insert([
            ['asset_id' => $this->naftrac->id, 'fecha' => self::HASTA, 'cierre' => '25'],
            ['asset_id' => $this->aapl->id, 'fecha' => self::HASTA, 'cierre' => '22'],
        ]);
        // insert() en vez de create(): en SQLite (pruebas) el cast de fecha guardaría "2026-06-30 00:00:00"
        // y la comparación de texto con "2026-06-30" fallaría. En MySQL la columna DATE lo recorta.
        FxRate::insert(['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => self::HASTA, 'tasa' => '18']);

        app(SnapshotService::class)->recalcular($this->portafolio, null, self::HASTA);
    }

    public function test_resumen_coincide_con_el_ejemplo_calculado_a_mano(): void
    {
        $r = app(AnalyticsService::class)->resumen($this->portafolio, '2026-01-02', self::HASTA);

        $this->assertSame('12020.000000', $r['estado']['valor_total']);
        $this->assertSame('3560.000000', $r['estado']['efectivo']);
        $this->assertSame('1520.000000', $r['estado']['gp_total']);
        $this->assertSame('1100.000000', $r['estado']['gp_no_realizada']);
        $this->assertSame('360.000000', $r['estado']['gp_realizada']);
        $this->assertSame('100.000000', $r['estado']['ingresos']);
        $this->assertEqualsWithDelta(0.144762, $r['rendimiento_total'], 1e-6);
        $this->assertEqualsWithDelta(0.317427, $r['rendimiento_anualizado'], 1e-6);

        $this->assertSame(['etf', 'accion', 'efectivo'], array_column($r['asignacion']['clase'], 'clave'));
        $this->assertSame(['AAPL', 'NAFTRAC'], array_column($r['top']['ganadores'], 'simbolo'));
        $this->assertEqualsWithDelta(0.144762, array_sum(array_column($r['contribucion'], 'contribucion')), 1e-6);
    }

    public function test_genera_un_snapshot_por_dia_habil(): void
    {
        $snapshots = PortfolioSnapshot::where('portfolio_id', $this->portafolio->id)->orderBy('fecha')->get();

        // Del viernes 02/01/2026 al martes 30/06/2026 hay 128 días hábiles.
        $this->assertCount(128, $snapshots);

        // 02/01: depósito de 10,000 y compra de 100 A @ 40 (sin cierre registrado → vale 40) = 10,000.
        $this->assertSame('2026-01-02', $snapshots->first()->fecha->toDateString());
        $this->assertSame('10000.000000', $snapshots->first()->valor);
        $this->assertSame('10000.000000', $snapshots->first()->flujo_neto);

        // El retiro del 04/05 aparece como flujo negativo ese día.
        $this->assertSame('-500.000000', $snapshots->firstWhere(fn ($s) => $s->fecha->toDateString() === '2026-05-04')->flujo_neto);

        // 30/06: valor final del ejemplo.
        $this->assertSame('12020.000000', $snapshots->last()->valor);
        $this->assertSame('3560.000000', $snapshots->last()->efectivo);
    }

    public function test_metricas_del_periodo_se_calculan_desde_los_snapshots(): void
    {
        $periodo = app(AnalyticsService::class)->resumen($this->portafolio, '2026-01-02', self::HASTA)['periodo'];

        $this->assertNotNull($periodo['twr']);
        $this->assertNotNull($periodo['xirr']);
        $this->assertNotNull($periodo['volatilidad']);
        $this->assertLessThanOrEqual(0, $periodo['max_drawdown']);
        $this->assertNull($periodo['benchmark']); // el portafolio de prueba no tiene benchmark
    }

    public function test_benchmark_se_mide_desde_el_primer_dia_del_portafolio(): void
    {
        $bench = \App\Modules\Prices\Models\Benchmark::create(['nombre' => 'Índice X']);
        \App\Modules\Prices\Models\BenchmarkValue::insert([
            ['benchmark_id' => $bench->id, 'fecha' => '2026-01-02', 'valor' => '100'],
            ['benchmark_id' => $bench->id, 'fecha' => '2026-06-30', 'valor' => '108'],
        ]);
        $this->portafolio->update(['benchmark_id' => $bench->id]);

        // El periodo pide desde el 01/01, pero el portafolio y el índice arrancan el 02/01: 100 → 108 = 8%.
        $periodo = app(AnalyticsService::class)->resumen($this->portafolio->fresh(), '2026-01-01', self::HASTA)['periodo'];

        $this->assertEqualsWithDelta(0.08, $periodo['benchmark'], 1e-12);
    }

    public function test_al_registrar_editar_o_borrar_una_transaccion_se_recalculan_los_snapshots(): void
    {
        // Portafolio limpio para medir solo el efecto de las transacciones vía la aplicación.
        Transaction::where('portfolio_id', $this->portafolio->id)->delete();
        PortfolioSnapshot::where('portfolio_id', $this->portafolio->id)->delete();

        $this->depositar('5000', '2026-09-01')->assertSessionHasNoErrors();
        $deposito = Transaction::where('portfolio_id', $this->portafolio->id)->firstOrFail();
        $primero = fn () => PortfolioSnapshot::where('portfolio_id', $this->portafolio->id)->orderBy('fecha')->first();

        $this->assertSame('2026-09-01', $primero()->fecha->toDateString());
        $this->assertSame('5000.000000', $primero()->valor);

        // Mover el depósito a una fecha anterior reconstruye desde esa fecha.
        $this->actingAs($this->asesor)->put(route('transactions.update', $deposito), [
            'tipo' => 'DEPOSITO', 'fecha' => '2026-08-03', 'cantidad' => '7000',
        ])->assertSessionHasNoErrors();
        $this->assertSame('2026-08-03', $primero()->fecha->toDateString());
        $this->assertSame('7000.000000', $primero()->valor);

        // Borrarlo deja el portafolio sin snapshots.
        $this->actingAs($this->asesor)->delete(route('transactions.destroy', $deposito));
        $this->assertSame(0, PortfolioSnapshot::where('portfolio_id', $this->portafolio->id)->count());
    }
}
