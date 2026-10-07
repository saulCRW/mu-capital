<?php

namespace Tests\Unit\Analytics;

use App\Modules\Analytics\Services\RiskCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Cálculo 12, ejemplo a mano con 5 rendimientos diarios:
 *   r = [+1%, −2%, +3%, −1%, +2%]      promedio = 0.6%
 *   desviaciones: 0.4, −2.6, 2.4, −1.6, 1.4 (en %) → cuadrados 0.16 + 6.76 + 5.76 + 2.56 + 1.96 = 17.2
 *   varianza muestral = 17.2 ÷ 4 = 4.3 (%²) → desviación = 2.0736%
 *   Volatilidad anual = 2.0736% × √252 = 32.92%
 *   Sharpe (rf = 0)   = 0.6 ÷ 2.0736 × √252 = 4.5932
 *   Sortino (rf = 0)  : días bajo 0 → −2% y −1% → √((4 + 1) ÷ 5) = 1% → 0.6 ÷ 1 × √252 = 9.5247
 *   Benchmark = [+0.5%, −1%, +1.5%, −0.5%, +1%] (exactamente la mitad) → beta = 2
 *   Índice: 1.01, 0.9898, 1.0195, 1.0093, 1.0295 → peor caída 1.01 → 0.9898 = −2% (máximo drawdown)
 */
class RiskCalculatorTest extends TestCase
{
    private array $r = [0.01, -0.02, 0.03, -0.01, 0.02];

    public function test_volatilidad_anualizada(): void
    {
        $this->assertEqualsWithDelta(0.329181, RiskCalculator::volatilidad($this->r), 1e-6);
    }

    public function test_beta(): void
    {
        $benchmark = array_map(fn ($x) => $x / 2, $this->r);

        $this->assertEqualsWithDelta(2.0, RiskCalculator::beta($this->r, $benchmark), 1e-12);
    }

    public function test_sharpe_y_sortino(): void
    {
        $this->assertEqualsWithDelta(4.593220, RiskCalculator::sharpe($this->r, 0.0), 1e-6);
        $this->assertEqualsWithDelta(9.524705, RiskCalculator::sortino($this->r, 0.0), 1e-6);
    }

    public function test_tasa_libre_de_riesgo_reduce_el_sharpe(): void
    {
        // rf anual 10.5% → rf diaria = 1.105^(1/252) − 1 = 0.0396%
        $this->assertEqualsWithDelta(0.000396, RiskCalculator::rfDiaria(0.105), 1e-6);
        $this->assertLessThan(RiskCalculator::sharpe($this->r, 0.0), RiskCalculator::sharpe($this->r, 0.105));
    }

    public function test_maximo_drawdown(): void
    {
        $this->assertEqualsWithDelta(-0.02, RiskCalculator::maxDrawdown($this->r), 1e-12);
        $this->assertSame(0.0, RiskCalculator::maxDrawdown([0.01, 0.02]));
    }

    public function test_datos_insuficientes_devuelven_null(): void
    {
        $this->assertNull(RiskCalculator::volatilidad([0.01]));
        $this->assertNull(RiskCalculator::beta([0.01, 0.02], [0.01]));
        $this->assertNull(RiskCalculator::sortino([0.01, 0.02], 0.0)); // ningún día negativo
    }
}
