<?php

namespace Tests\Unit\Reports;

use App\Modules\Reports\Services\QuarterlyAggregator;
use PHPUnit\Framework\TestCase;

class QuarterlyAggregatorTest extends TestCase
{
    /**
     * Ejemplo a mano (año 2026):
     *   31/12/2025  vale 1,000 (base, año anterior)
     *   1T: 31/03 sube a 1,100                          ganancia 100, TWR +10%
     *   2T: 01/04 deposita 900 → 2,000; 30/06 baja a 1,800
     *       r(01/04) = 2,000 ÷ (1,100 + 900) − 1 = 0;  r(30/06) = 1,800 ÷ 2,000 − 1 = −10%
     *       ganancia = 1,800 − 1,100 − 900 = −200,     TWR −10%
     *   Año: ganancia −100, flujo 900, TWR = 1.10 × 0.90 − 1 = −1%
     */
    public function test_agrega_valor_flujo_ganancia_y_twr_por_trimestre(): void
    {
        $serie = [
            ['fecha' => '2025-12-31', 'valor' => '1000', 'flujo' => '0'],
            ['fecha' => '2026-03-31', 'valor' => '1100', 'flujo' => '0'],
            ['fecha' => '2026-04-01', 'valor' => '2000', 'flujo' => '900'],
            ['fecha' => '2026-06-30', 'valor' => '1800', 'flujo' => '0'],
        ];

        $r = QuarterlyAggregator::agregar($serie, 2026);

        $this->assertSame([1, 2], array_column($r['trimestres'], 'trimestre'));
        $this->assertSame(['2026-01-01', '2026-03-31'], [$r['trimestres'][0]['desde'], $r['trimestres'][0]['hasta']]);
        $this->assertSame(['2025-12-31', '2026-03-31'], array_column($r['trimestres'], 'fecha_base'));
        $this->assertSame('2025-12-31', $r['anio']['fecha_base']);
        $this->assertSame(['1000.000000', '1100.000000'], array_column($r['trimestres'], 'valor_inicial'));
        $this->assertSame(['1100.000000', '1800.000000'], array_column($r['trimestres'], 'valor_final'));
        $this->assertSame(['0.000000', '900.000000'], array_column($r['trimestres'], 'flujo_neto'));
        $this->assertSame(['100.000000', '-200.000000'], array_column($r['trimestres'], 'ganancia'));
        $this->assertEqualsWithDelta([0.10, -0.10], array_column($r['trimestres'], 'twr'), 1e-12);

        $this->assertSame('1000.000000', $r['anio']['valor_inicial']);
        $this->assertSame('1800.000000', $r['anio']['valor_final']);
        $this->assertSame('900.000000', $r['anio']['flujo_neto']);
        $this->assertSame('-100.000000', $r['anio']['ganancia']);
        $this->assertEqualsWithDelta(-0.01, $r['anio']['twr'], 1e-12);
    }

    public function test_un_portafolio_que_empieza_a_mitad_de_anio_parte_de_cero(): void
    {
        $serie = [
            ['fecha' => '2026-05-04', 'valor' => '5000', 'flujo' => '5000'],
            ['fecha' => '2026-06-30', 'valor' => '5250', 'flujo' => '0'],
        ];

        $r = QuarterlyAggregator::agregar($serie, 2026);

        $this->assertSame([2], array_column($r['trimestres'], 'trimestre'));
        $this->assertSame('0.000000', $r['trimestres'][0]['valor_inicial']);
        $this->assertSame('2026-05-04', $r['trimestres'][0]['fecha_base']);
        $this->assertSame('250.000000', $r['trimestres'][0]['ganancia']);
        $this->assertEqualsWithDelta(0.05, $r['trimestres'][0]['twr'], 1e-12);
    }

    public function test_corta_en_el_trimestre_pedido(): void
    {
        $serie = [
            ['fecha' => '2026-03-31', 'valor' => '100', 'flujo' => '100'],
            ['fecha' => '2026-06-30', 'valor' => '110', 'flujo' => '0'],
        ];

        $r = QuarterlyAggregator::agregar($serie, 2026, 1);

        $this->assertSame([1], array_column($r['trimestres'], 'trimestre'));
        $this->assertSame('100.000000', $r['anio']['valor_final']);
    }

    public function test_sin_datos_en_el_anio_no_hay_trimestres(): void
    {
        $r = QuarterlyAggregator::agregar([['fecha' => '2025-12-31', 'valor' => '100', 'flujo' => '0']], 2026);

        $this->assertSame(['trimestres' => [], 'anio' => null], $r);
    }
}
