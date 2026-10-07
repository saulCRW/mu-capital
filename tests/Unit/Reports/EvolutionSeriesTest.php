<?php

namespace Tests\Unit\Reports;

use App\Modules\Reports\Services\EvolutionSeries;
use App\Modules\Reports\Services\SvgLineChart;
use PHPUnit\Framework\TestCase;

class EvolutionSeriesTest extends TestCase
{
    /**
     * Ejemplo a mano:
     *   31/12  base 1,000
     *   02/01  sube a 1,100                         r = +10%   índice 110
     *   05/01  deposita 900 → 2,000                 r = 2,000 ÷ (1,100 + 900) − 1 = 0   índice 110
     *   06/01  baja a 1,800                         r = −10%   índice 99
     *   Capital aportado: 1,000 → 1,000 → 1,900 → 1,900.
     *   Benchmark 200 → 210 → (sin dato el 05/01: usa el 02/01) → 190  ⇒ 100, 105, 105, 95.
     */
    public function test_calcula_valor_capital_aportado_e_indices_base_100(): void
    {
        $serie = [
            ['fecha' => '2025-12-31', 'valor' => '1000', 'flujo' => '0'],
            ['fecha' => '2026-01-02', 'valor' => '1100', 'flujo' => '0'],
            ['fecha' => '2026-01-05', 'valor' => '2000', 'flujo' => '900'],
            ['fecha' => '2026-01-06', 'valor' => '1800', 'flujo' => '0'],
        ];
        $benchmark = ['2025-12-31' => '200', '2026-01-02' => '210', '2026-01-06' => '190'];

        $r = EvolutionSeries::calcular($serie, $benchmark);

        $this->assertSame([1000.0, 1100.0, 2000.0, 1800.0], array_column($r['valor'], 1));
        $this->assertSame([1000.0, 1000.0, 1900.0, 1900.0], array_column($r['aportado'], 1));
        $this->assertEqualsWithDelta([100, 110, 110, 99], array_column($r['indice'], 1), 1e-9);
        $this->assertEqualsWithDelta([100, 105, 105, 95], array_column($r['benchmark'], 1), 1e-9);
        $this->assertSame(['2025-12-31', '2026-01-02', '2026-01-05', '2026-01-06'], array_column($r['indice'], 0));
    }

    public function test_si_el_benchmark_empieza_despues_se_mide_desde_su_primer_valor(): void
    {
        $serie = [
            ['fecha' => '2026-01-02', 'valor' => '1000', 'flujo' => '1000'],
            ['fecha' => '2026-01-05', 'valor' => '1000', 'flujo' => '0'],
            ['fecha' => '2026-01-06', 'valor' => '1000', 'flujo' => '0'],
        ];

        $r = EvolutionSeries::calcular($serie, ['2026-01-05' => '50', '2026-01-06' => '55']);

        $this->assertSame(['2026-01-05', '2026-01-06'], array_column($r['benchmark'], 0));
        $this->assertEqualsWithDelta([100, 110], array_column($r['benchmark'], 1), 1e-9);
    }

    public function test_sin_benchmark_ni_serie_quedan_vacias(): void
    {
        $this->assertSame([], EvolutionSeries::calcular([['fecha' => '2026-01-02', 'valor' => '10', 'flujo' => '10']])['benchmark']);
        $this->assertSame(['valor' => [], 'aportado' => [], 'indice' => [], 'benchmark' => []], EvolutionSeries::calcular([]));
    }

    public function test_la_escala_usa_pasos_redondos(): void
    {
        $this->assertSame([950000.0, 1150000.0, 50000.0], SvgLineChart::escala(985000, 1110000));
        $this->assertSame([95.0, 115.0, 5.0], SvgLineChart::escala(98.7, 112.4));
        // Serie plana: se abre ±5% para que la línea no quede pegada a un borde.
        $this->assertSame([95.0, 105.0, 2.5], SvgLineChart::escala(100, 100));
    }

    public function test_las_etiquetas_del_eje_son_compactas(): void
    {
        $this->assertSame('1.05 M', SvgLineChart::eje(1050000, 50000, 'dinero'));
        $this->assertSame('950 k', SvgLineChart::eje(950000, 50000, 'dinero'));
        $this->assertSame('12.5 k', SvgLineChart::eje(12500, 2500, 'dinero'));
        $this->assertSame('105', SvgLineChart::eje(105, 5, 'indice'));
    }
}
