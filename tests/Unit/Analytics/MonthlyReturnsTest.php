<?php

namespace Tests\Unit\Analytics;

use App\Modules\Analytics\Services\MonthlyReturns;
use PHPUnit\Framework\TestCase;

class MonthlyReturnsTest extends TestCase
{
    /**
     * Ejemplo a mano:
     *   31/12/2025 base 1,000
     *   02/01 → 1,100 (+10 %)   05/01 deposita 900 → 2,000 (0 %)   06/01 → 1,800 (−10 %)
     *   enero = 1.10 × 1.00 × 0.90 − 1 = −1 %
     *   02/02 → 1,890 (+5 %)    febrero = +5 %
     *   2026 = 0.99 × 1.05 − 1 = 3.95 %
     */
    public function test_encadena_los_dias_de_cada_mes_y_los_meses_del_anio(): void
    {
        $serie = [
            ['fecha' => '2025-12-31', 'valor' => '1000', 'flujo' => '0'],
            ['fecha' => '2026-01-02', 'valor' => '1100', 'flujo' => '0'],
            ['fecha' => '2026-01-05', 'valor' => '2000', 'flujo' => '900'],
            ['fecha' => '2026-01-06', 'valor' => '1800', 'flujo' => '0'],
            ['fecha' => '2026-02-02', 'valor' => '1890', 'flujo' => '0'],
        ];

        $r = MonthlyReturns::calcular($serie);

        $this->assertSame([2026], array_keys($r));
        $this->assertSame([1, 2], array_keys($r[2026]['meses']));
        $this->assertEqualsWithDelta(-0.01, $r[2026]['meses'][1], 1e-12);
        $this->assertEqualsWithDelta(0.05, $r[2026]['meses'][2], 1e-12);
        $this->assertEqualsWithDelta(0.0395, $r[2026]['anio'], 1e-12);
    }

    public function test_sin_historial_no_hay_meses(): void
    {
        $this->assertSame([], MonthlyReturns::calcular([['fecha' => '2026-01-05', 'valor' => '1000', 'flujo' => '1000']]));
    }
}
