<?php

namespace Tests\Unit\Rebalancing;

use App\Modules\Rebalancing\Services\RebalanceCalculator;
use PHPUnit\Framework\TestCase;

/**
 * Ejemplo a mano: el portafolio vale 10,000.
 *   Acciones: AAA, 100 títulos @ 60 = 6,000 (60 %)   objetivo 50 % → 5,000 → vender 1,000 = 16 títulos (16.67)
 *   ETF:      EEE,  10 títulos @ 100 = 1,000 (10 %)   objetivo 20 % → 2,000 → comprar 1,000 = 10 títulos
 *   Efectivo: 3,000 (30 %)                            objetivo 30 % → sin ajuste
 */
class RebalanceCalculatorTest extends TestCase
{
    private const ACTUAL = [
        ['clave' => 'accion', 'valor' => '6000', 'peso' => 0.6],
        ['clave' => 'efectivo', 'valor' => '3000', 'peso' => 0.3],
        ['clave' => 'etf', 'valor' => '1000', 'peso' => 0.1],
    ];

    private const POSICIONES = [
        ['asset_id' => 1, 'simbolo' => 'AAA', 'tipo' => 'accion', 'sector' => null, 'moneda' => 'MXN', 'pais' => null, 'cantidad' => '100', 'valor_base' => '6000', 'precio' => '60', 'tasa' => '1'],
        ['asset_id' => 2, 'simbolo' => 'EEE', 'tipo' => 'etf', 'sector' => null, 'moneda' => 'MXN', 'pais' => null, 'cantidad' => '10', 'valor_base' => '1000', 'precio' => '100', 'tasa' => '1'],
    ];

    public function test_compara_contra_el_objetivo_y_marca_lo_que_sale_de_la_banda(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, ['accion' => 0.5, 'etf' => 0.2, 'efectivo' => 0.3], '10000');

        $this->assertSame(['accion', 'efectivo', 'etf'], array_column($filas, 'clave'));
        $this->assertEqualsWithDelta([0.10, 0.0, -0.10], array_column($filas, 'desviacion'), 1e-12);
        $this->assertSame(['-1000.000000', '0.000000', '1000.000000'], array_column($filas, 'ajuste'));
        $this->assertSame([true, false, true], array_column($filas, 'fuera'));
    }

    public function test_propone_vender_y_comprar_titulos_enteros(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, ['accion' => 0.5, 'etf' => 0.2, 'efectivo' => 0.3], '10000');

        $r = RebalanceCalculator::operaciones($filas, self::POSICIONES, 'clase');

        $this->assertSame([
            ['asset_id' => 1, 'simbolo' => 'AAA', 'grupo' => 'accion', 'tipo' => 'VENTA', 'monto' => '-1000.000000', 'titulos' => '16'],
            ['asset_id' => 2, 'simbolo' => 'EEE', 'grupo' => 'etf', 'tipo' => 'COMPRA', 'monto' => '1000.000000', 'titulos' => '10'],
        ], $r['operaciones']);
        $this->assertSame([], $r['sin_activos']);
    }

    public function test_dentro_de_la_banda_no_propone_nada(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, ['accion' => 0.57, 'etf' => 0.13, 'efectivo' => 0.3], '10000');

        $this->assertSame(['operaciones' => [], 'sin_activos' => []], RebalanceCalculator::operaciones($filas, self::POSICIONES, 'clase'));
    }

    public function test_un_grupo_objetivo_sin_posiciones_queda_pendiente(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, ['accion' => 0.5, 'etf' => 0.1, 'bono' => 0.1, 'efectivo' => 0.3], '10000');

        $r = RebalanceCalculator::operaciones($filas, self::POSICIONES, 'clase');

        $this->assertSame([['clave' => 'bono', 'ajuste' => '1000.000000']], $r['sin_activos']);
        $this->assertSame(['AAA'], array_column($r['operaciones'], 'simbolo'));
    }

    public function test_un_grupo_sin_objetivo_cuenta_como_cero(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, ['accion' => 0.7, 'efectivo' => 0.3], '10000');

        $etf = collect($filas)->firstWhere('clave', 'etf');
        $this->assertSame(0.0, $etf['objetivo']);
        $this->assertTrue($etf['fuera']);
    }

    public function test_sin_objetivos_no_hay_comparacion(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, [], '10000');

        $this->assertSame([null, null, null], array_column($filas, 'objetivo'));
        $this->assertSame([false, false, false], array_column($filas, 'fuera'));
    }

    public function test_el_efectivo_no_genera_operaciones(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, ['accion' => 0.4, 'etf' => 0.1, 'efectivo' => 0.5], '10000');

        $r = RebalanceCalculator::operaciones($filas, self::POSICIONES, 'clase', 'efectivo');

        $this->assertSame([], $r['sin_activos']);
        $this->assertSame([['VENTA', 'AAA', '33']], array_map(fn ($o) => [$o['tipo'], $o['simbolo'], $o['titulos']], $r['operaciones']));
    }

    public function test_una_venta_no_pasa_de_lo_que_se_tiene(): void
    {
        $filas = RebalanceCalculator::comparar(self::ACTUAL, ['efectivo' => 1.0], '10000');

        $r = RebalanceCalculator::operaciones($filas, self::POSICIONES, 'clase');

        $this->assertSame(['100', '10'], array_column($r['operaciones'], 'titulos'));
    }
}
