<?php

namespace Tests\Unit\Analytics;

use App\Modules\Analytics\Services\PortfolioLedger;
use App\Modules\Analytics\Services\PriceBook;
use PHPUnit\Framework\TestCase;

/** Cálculos 1–7 sobre el CASO DE EJEMPLO (ver CasoEjemplo.php). */
class PortfolioLedgerTest extends TestCase
{
    use CasoEjemplo;

    private array $estado;

    protected function setUp(): void
    {
        $this->estado = PortfolioLedger::reproducir($this->transaccionesEjemplo(), 'MXN')
            ->valuar('2026-06-30', $this->preciosEjemplo());
    }

    private function posicion(int $activo): array
    {
        return collect($this->estado['posiciones'])->firstWhere('asset_id', $activo);
    }

    public function test_1_posicion_ajustada_por_split(): void
    {
        $this->assertSame('180.000000', $this->posicion(1)['cantidad']); // (100 + 50 − 60) × 2
        $this->assertSame('10.000000', $this->posicion(2)['cantidad']);
    }

    public function test_2_costo_promedio_ponderado(): void
    {
        // A: 6,600 ÷ 150 = 44; tras vender 60 queda costo 3,960 / 90 = 44; tras split 2:1 → 22.
        $this->assertSame('3960.000000', $this->posicion(1)['costo_base']);
        $this->assertSame('22.000000', $this->posicion(1)['costo_promedio_local']);
        // B: 20 USD por título; en pesos 3,400 / 10 = 340.
        $this->assertSame('20.000000', $this->posicion(2)['costo_promedio_local']);
        $this->assertSame('340.000000', $this->posicion(2)['costo_promedio_base']);
    }

    public function test_3_efectivo(): void
    {
        // 11,000 depósitos − 500 retiro − 10,000 compras + 3,000 venta + 90 dividendo + 10 interés − 40 comisión
        $this->assertSame('3560.000000', $this->estado['efectivo']);
    }

    public function test_4_valor_de_mercado_en_moneda_base(): void
    {
        $this->assertSame('4500.000000', $this->posicion(1)['valor_base']); // 180 × 25
        $this->assertSame('3960.000000', $this->posicion(2)['valor_base']); // 10 × 22 × 18
        $this->assertSame('12020.000000', $this->estado['valor_total']);
    }

    public function test_5_gp_no_realizada(): void
    {
        $this->assertSame('540.000000', $this->posicion(1)['gp_no_realizada']);
        $this->assertEqualsWithDelta(0.136364, $this->posicion(1)['gp_no_realizada_pct'], 1e-6); // 540 ÷ 3,960
        $this->assertSame('560.000000', $this->posicion(2)['gp_no_realizada']);
        $this->assertEqualsWithDelta(0.164706, $this->posicion(2)['gp_no_realizada_pct'], 1e-6); // 560 ÷ 3,400
    }

    public function test_6_gp_realizada_contra_costo_promedio(): void
    {
        $this->assertSame('360.000000', $this->posicion(1)['gp_realizada']); // 60 × 50 − 60 × 44
        $this->assertSame('360.000000', $this->estado['gp_realizada']);
    }

    public function test_7_ingresos(): void
    {
        $this->assertSame('90.000000', $this->estado['dividendos']);
        $this->assertSame('10.000000', $this->estado['intereses']);
        $this->assertSame('100.000000', $this->estado['ingresos']);
    }

    public function test_gp_total_cuadra_con_sus_componentes(): void
    {
        $this->assertSame('10500.000000', $this->estado['aportaciones_netas']);
        $this->assertSame('1520.000000', $this->estado['gp_total']);
        // 1,100 + 360 + 90 + 10 − 40
        $suma = bcadd(bcadd($this->estado['gp_no_realizada'], $this->estado['gp_realizada'], 6), bcsub($this->estado['ingresos'], $this->estado['comisiones'], 6), 6);
        $this->assertSame($this->estado['gp_total'], $suma);
    }

    public function test_rendimiento_de_cada_posicion(): void
    {
        // A: (540 + 360 + 90) ÷ (4,000 + 2,600 invertidos) = 15%
        $this->assertEqualsWithDelta(0.15, $this->posicion(1)['rendimiento_pct'], 1e-9);
    }

    public function test_posicion_vendida_completa_queda_con_su_realizada(): void
    {
        $estado = PortfolioLedger::reproducir([
            ['id' => 1, 'tipo' => 'DEPOSITO', 'fecha' => '2026-01-02', 'asset_id' => null, 'cantidad' => '1000', 'precio' => '1', 'comision' => '0', 'tipo_cambio' => '1'],
            ['id' => 2, 'tipo' => 'COMPRA', 'fecha' => '2026-01-02', 'asset_id' => 1, 'cantidad' => '10', 'precio' => '30', 'comision' => '5', 'tipo_cambio' => '1'],
            ['id' => 3, 'tipo' => 'VENTA', 'fecha' => '2026-02-02', 'asset_id' => 1, 'cantidad' => '10', 'precio' => '35', 'comision' => '5', 'tipo_cambio' => '1'],
        ], 'MXN')->valuar('2026-03-01', new PriceBook);

        $p = $estado['posiciones'][0];
        $this->assertSame('0.000000', $p['cantidad']);
        $this->assertSame('0', $p['costo_base']);
        // Venta neta 350 − 5 = 345; costo con comisión 300 + 5 = 305 → realizada 40.
        $this->assertSame('40.000000', $p['gp_realizada']);
        $this->assertSame('1040.000000', $estado['valor_total']);
    }

    public function test_sin_precio_registrado_usa_el_de_la_ultima_operacion(): void
    {
        $estado = PortfolioLedger::reproducir([
            ['id' => 1, 'tipo' => 'DEPOSITO', 'fecha' => '2026-01-02', 'asset_id' => null, 'cantidad' => '1000', 'precio' => '1', 'comision' => '0', 'tipo_cambio' => '1'],
            ['id' => 2, 'tipo' => 'COMPRA', 'fecha' => '2026-01-02', 'asset_id' => 1, 'cantidad' => '10', 'precio' => '30', 'comision' => '0', 'tipo_cambio' => '1'],
        ], 'MXN')->valuar('2026-03-01', new PriceBook);

        $this->assertSame('300.000000', $estado['posiciones'][0]['valor_base']);
    }
}
