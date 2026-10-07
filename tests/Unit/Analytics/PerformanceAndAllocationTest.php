<?php

namespace Tests\Unit\Analytics;

use App\Modules\Analytics\Services\AllocationCalculator;
use App\Modules\Analytics\Services\PerformanceCalculator;
use App\Modules\Analytics\Services\PortfolioLedger;
use PHPUnit\Framework\TestCase;

/** Cálculos 8, 13, 14 y 15 sobre el CASO DE EJEMPLO (ver CasoEjemplo.php). */
class PerformanceAndAllocationTest extends TestCase
{
    use CasoEjemplo;

    private array $estado;

    protected function setUp(): void
    {
        $this->estado = PortfolioLedger::reproducir($this->transaccionesEjemplo(), 'MXN')
            ->valuar('2026-06-30', $this->preciosEjemplo());

        $meta = $this->metaEjemplo();
        foreach ($this->estado['posiciones'] as $i => $p) {
            $this->estado['posiciones'][$i] += $meta[$p['asset_id']];
        }
    }

    public function test_8_rendimiento_total_y_anualizado(): void
    {
        $total = PerformanceCalculator::rendimientoTotal($this->estado['gp_total'], $this->estado['aportaciones_netas']);
        $this->assertEqualsWithDelta(0.144762, $total, 1e-6); // 1,520 ÷ 10,500

        // Del 02/01/2026 al 30/06/2026 hay 179 días: 1.144762^(365/179) − 1
        $this->assertEqualsWithDelta(0.317427, PerformanceCalculator::anualizar($total, 179), 1e-6);
    }

    public function test_13_asignacion_por_clase(): void
    {
        $clase = AllocationCalculator::calcular('clase', $this->estado['posiciones'], $this->estado['efectivo'], 'MXN');

        $this->assertSame(['accion', 'etf', 'efectivo'], array_column($clase, 'clave'));
        $this->assertEqualsWithDelta(0.374376, $clase[0]['peso'], 1e-6); // 4,500 ÷ 12,020
        $this->assertEqualsWithDelta(0.329451, $clase[1]['peso'], 1e-6); // 3,960 ÷ 12,020
        $this->assertEqualsWithDelta(0.296173, $clase[2]['peso'], 1e-6); // 3,560 ÷ 12,020
        $this->assertEqualsWithDelta(1.0, array_sum(array_column($clase, 'peso')), 1e-9);
    }

    public function test_13_asignacion_por_moneda_sector_y_pais(): void
    {
        $moneda = AllocationCalculator::calcular('moneda', $this->estado['posiciones'], $this->estado['efectivo'], 'MXN');
        $this->assertSame('MXN', $moneda[0]['clave']);
        $this->assertSame('8060.000000', $moneda[0]['valor']); // 4,500 de A + 3,560 de efectivo
        $this->assertEqualsWithDelta(0.670549, $moneda[0]['peso'], 1e-6);

        $sector = AllocationCalculator::calcular('sector', $this->estado['posiciones'], $this->estado['efectivo'], 'MXN');
        $this->assertSame(['Tecnología', 'Diversificado', 'Efectivo'], array_column($sector, 'clave'));

        $pais = AllocationCalculator::calcular('pais', $this->estado['posiciones'], $this->estado['efectivo'], 'MXN');
        $this->assertSame(['México', 'Estados Unidos', 'Efectivo'], array_column($pais, 'clave'));
    }

    public function test_14_top_ganadores_y_perdedores(): void
    {
        $top = PerformanceCalculator::top($this->estado['posiciones']);

        $this->assertSame([2, 1], array_column($top['ganadores'], 'asset_id')); // B 16.47% > A 13.64%
        $this->assertSame([], $top['perdedores']);
    }

    public function test_15_contribucion_por_activo_suma_el_rendimiento_total(): void
    {
        $filas = PerformanceCalculator::contribucion($this->estado['posiciones'], $this->estado['otros'], $this->estado['aportaciones_netas']);
        $porActivo = [];
        foreach ($filas as $f) {
            $porActivo[$f['asset_id'] ?? 'otros'] = $f;
        }

        // A: (540 + 360 + 90) ÷ 10,500
        $this->assertSame('990.000000', $porActivo[1]['aporte']);
        $this->assertEqualsWithDelta(0.094286, $porActivo[1]['contribucion'], 1e-6);
        // B: 560 ÷ 10,500
        $this->assertEqualsWithDelta(0.053333, $porActivo[2]['contribucion'], 1e-6);
        // Otros: 10 interés − 40 comisión = −30 ÷ 10,500
        $this->assertSame('-30.000000', $porActivo['otros']['aporte']);
        $this->assertEqualsWithDelta(-0.002857, $porActivo['otros']['contribucion'], 1e-6);

        $this->assertEqualsWithDelta(0.144762, array_sum(array_column($filas, 'contribucion')), 1e-6);
    }

    public function test_perdedores_se_ordenan_del_peor_al_menos_malo(): void
    {
        $top = PerformanceCalculator::top([
            ['asset_id' => 1, 'cantidad' => '1', 'gp_no_realizada_pct' => -0.05],
            ['asset_id' => 2, 'cantidad' => '1', 'gp_no_realizada_pct' => -0.20],
            ['asset_id' => 3, 'cantidad' => '0', 'gp_no_realizada_pct' => -0.90], // vendida: no cuenta
            ['asset_id' => 4, 'cantidad' => '1', 'gp_no_realizada_pct' => 0.10],
        ]);

        $this->assertSame([2, 1], array_column($top['perdedores'], 'asset_id'));
        $this->assertSame([4], array_column($top['ganadores'], 'asset_id'));
    }
}
