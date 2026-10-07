<?php

namespace Tests\Unit\Analytics;

use App\Modules\Analytics\Services\PriceBook;
use App\Modules\Analytics\Services\ReturnCalculator;
use PHPUnit\Framework\TestCase;

/** Cálculos 9, 10 y 11. */
class ReturnCalculatorTest extends TestCase
{
    /**
     * TWR (9), ejemplo a mano:
     *   Día 1: deposita 1,000, termina en 1,000              r = 1,000 ÷ (0 + 1,000) − 1     =   0%
     *   Día 2: sube a 1,100                                  r = 1,100 ÷ (1,000 + 0) − 1     = +10%
     *   Día 3: deposita 1,000 al inicio, termina en 2,310    r = 2,310 ÷ (1,100 + 1,000) − 1 = +10%
     *   Día 4: baja a 2,079                                  r = 2,079 ÷ 2,310 − 1           = −10%
     *   TWR = 1.00 × 1.10 × 1.10 × 0.90 − 1 = 8.9%
     *   (El rendimiento simple sería (2,079 − 2,000) ÷ 2,000 = 3.95%: el depósito antes de la caída lo distorsiona.)
     */
    public function test_9_twr_quita_el_efecto_de_los_depositos(): void
    {
        $serie = [
            ['fecha' => '2026-01-01', 'valor' => 0, 'flujo' => 0],
            ['fecha' => '2026-01-02', 'valor' => 1000, 'flujo' => 1000],
            ['fecha' => '2026-01-05', 'valor' => 1100, 'flujo' => 0],
            ['fecha' => '2026-01-06', 'valor' => 2310, 'flujo' => 1000],
            ['fecha' => '2026-01-07', 'valor' => 2079, 'flujo' => 0],
        ];

        $r = array_column(ReturnCalculator::rendimientosDiarios($serie), 'r');

        $this->assertEqualsWithDelta([0.0, 0.10, 0.10, -0.10], $r, 1e-12);
        $this->assertEqualsWithDelta(0.089, ReturnCalculator::twr($r), 1e-12);
    }

    public function test_9_dias_con_portafolio_vacio_se_omiten(): void
    {
        $serie = [
            ['fecha' => '2026-01-01', 'valor' => 0, 'flujo' => 0],
            ['fecha' => '2026-01-02', 'valor' => 0, 'flujo' => 0],
            ['fecha' => '2026-01-05', 'valor' => 500, 'flujo' => 500],
        ];

        $this->assertCount(1, ReturnCalculator::rendimientosDiarios($serie));
    }

    /** XIRR (10): invierte 1,000 y un año (365 días) después recibe 1,100 → 10% anual. */
    public function test_10_xirr_un_anio(): void
    {
        $this->assertEqualsWithDelta(0.10, ReturnCalculator::xirr([
            ['fecha' => '2025-01-01', 'monto' => -1000],
            ['fecha' => '2026-01-01', 'monto' => 1100],
        ]), 1e-9);
    }

    /**
     * XIRR con dos aportaciones: −1,000 el 01/01/2025, −1,000 el 01/01/2026 y
     * recibe 2,310 el 01/01/2027. Con r = 10%: 1,000 × 1.1² + 1,000 × 1.1 = 1,210 + 1,100 = 2,310 ✓
     */
    public function test_10_xirr_con_varias_aportaciones(): void
    {
        $this->assertEqualsWithDelta(0.10, ReturnCalculator::xirr([
            ['fecha' => '2025-01-01', 'monto' => -1000],
            ['fecha' => '2026-01-01', 'monto' => -1000],
            ['fecha' => '2027-01-01', 'monto' => 2310],
        ]), 1e-9);
    }

    public function test_10_xirr_con_perdida_y_sin_solucion(): void
    {
        // Invierte 1,000 y recupera 800 en un año → −20%.
        $this->assertEqualsWithDelta(-0.20, ReturnCalculator::xirr([
            ['fecha' => '2025-01-01', 'monto' => -1000],
            ['fecha' => '2026-01-01', 'monto' => 800],
        ]), 1e-9);

        // Solo aportaciones: no hay tasa que calcular.
        $this->assertNull(ReturnCalculator::xirr([['fecha' => '2025-01-01', 'monto' => -1000]]));
    }

    /** Benchmark (11): 100 → 110 en el periodo = +10%; usa el último valor en o antes de cada fecha. */
    public function test_11_rendimiento_del_benchmark_en_el_mismo_periodo(): void
    {
        $serie = ['2026-01-02' => 100, '2026-03-31' => 104, '2026-06-30' => 110];

        $this->assertEqualsWithDelta(0.10, ReturnCalculator::rendimientoSerie($serie, '2026-01-02', '2026-06-30'), 1e-12);
        // 01/01 no tiene dato previo → no se puede calcular.
        $this->assertNull(ReturnCalculator::rendimientoSerie($serie, '2026-01-01', '2026-06-30'));
        // 04/04 (sin dato) usa el del 31/03.
        $this->assertEqualsWithDelta(110 / 104 - 1, ReturnCalculator::rendimientoSerie($serie, '2026-04-04', '2026-07-15'), 1e-12);
    }

    public function test_price_book_usa_el_ultimo_dato_y_la_tasa_inversa(): void
    {
        $libro = new PriceBook(
            [7 => ['2026-01-02' => '10', '2026-01-09' => '12']],
            ['MXN>USD' => ['2026-01-02' => '0.050000']],
        );

        $this->assertNull($libro->precio(7, '2026-01-01'));
        $this->assertSame('10', $libro->precio(7, '2026-01-08'));
        $this->assertSame('12', $libro->precio(7, '2026-02-01'));
        $this->assertSame('20.000000', $libro->tasa('USD', 'MXN', '2026-01-05')); // 1 ÷ 0.05
        $this->assertSame('1', $libro->tasa('MXN', 'MXN', '2026-01-05'));
        $this->assertNull($libro->tasa('EUR', 'MXN', '2026-01-05'));
    }
}
