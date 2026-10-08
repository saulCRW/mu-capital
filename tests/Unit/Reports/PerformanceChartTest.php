<?php

namespace Tests\Unit\Reports;

use App\Modules\Reports\Services\PerformanceChart;
use PHPUnit\Framework\TestCase;

class PerformanceChartTest extends TestCase
{
    private const HOY = '2026-10-07';

    /**
     * Ejemplo a mano (índice base 100):
     *   31/12/2025 → 100   02/01/2026 → 104   07/09 → 104   10/09 → 106.08   07/10 → 101.92
     *   YTD: inicio el 31/12/2025 (100)       → 0 %, 4 %, 4 %, 6.08 %, 1.92 %
     *   1M:  corte 07/09, inicio ese día (104) → 0 %, 2 %, −2 %   (106.08 ÷ 104 − 1 = 2 %; 101.92 ÷ 104 − 1 = −2 %)
     */
    public function test_cada_rango_mide_contra_su_inicio(): void
    {
        $indice = [['2025-12-31', 100.0], ['2026-01-02', 104.0], ['2026-09-07', 104.0], ['2026-09-10', 106.08], ['2026-10-07', 101.92]];

        $rangos = PerformanceChart::rangos($indice, [], self::HOY);

        $this->assertEqualsWithDelta([0, 0.04, 0.04, 0.0608, 0.0192], array_column($rangos['YTD']['portafolio'], 1), 1e-12);
        $this->assertSame(['2026-09-07', '2026-09-10', '2026-10-07'], array_column($rangos['1M']['portafolio'], 0));
        $this->assertEqualsWithDelta([0, 0.02, -0.02], array_column($rangos['1M']['portafolio'], 1), 1e-12);
        $this->assertSame([], $rangos['1M']['benchmark']);
    }

    public function test_si_no_hay_dato_en_el_corte_usa_el_ultimo_anterior(): void
    {
        // Corte de 3M: 07/07. El último dato en o antes es el 03/07 (viernes) = 110.
        $serie = [['2026-07-03', 110.0], ['2026-07-08', 121.0]];

        $this->assertEqualsWithDelta([0, 0.1], array_column(PerformanceChart::recortar($serie, PerformanceChart::corte('3M', self::HOY)), 1), 1e-12);
    }

    public function test_un_portafolio_mas_joven_que_el_rango_empieza_en_su_primer_dia(): void
    {
        $serie = [['2026-09-20', 100.0], ['2026-10-07', 103.0]];

        $recorte = PerformanceChart::recortar($serie, PerformanceChart::corte('1A', self::HOY));

        $this->assertSame('2026-09-20', $recorte[0][0]);
        $this->assertEqualsWithDelta(0.03, $recorte[1][1], 1e-12);
    }

    public function test_cortes_de_cada_rango(): void
    {
        $this->assertSame(
            ['1M' => '2026-09-07', '3M' => '2026-07-07', 'YTD' => '2025-12-31', '1A' => '2025-10-07', 'TODO' => null],
            array_map(fn ($r) => PerformanceChart::corte($r, self::HOY), array_combine(array_keys(PerformanceChart::RANGOS), array_keys(PerformanceChart::RANGOS))),
        );
    }
}
