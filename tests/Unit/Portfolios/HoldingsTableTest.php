<?php

namespace Tests\Unit\Portfolios;

use App\Modules\Portfolios\Services\HoldingsTable;
use PHPUnit\Framework\TestCase;

class HoldingsTableTest extends TestCase
{
    /** Valor total del portafolio: 10,000 (6,000 en posiciones + 4,000 de efectivo). */
    private const TOTAL = '10000';

    private static function posicion(string $simbolo, string $moneda, ?string $sector, string $cantidad, string $valor, string $gpNoRealizada, string $gpRealizada, string $ingresos, string $invertido): array
    {
        return [
            'asset_id' => crc32($simbolo), 'simbolo' => $simbolo, 'moneda' => $moneda, 'tipo' => 'accion', 'sector' => $sector, 'exchange' => null,
            'cantidad' => $cantidad, 'costo_promedio_local' => '1', 'precio' => '1', 'valor_base' => $valor,
            'gp_no_realizada' => $gpNoRealizada, 'gp_realizada' => $gpRealizada, 'ingresos' => $ingresos, 'invertido_base' => $invertido,
            'rendimiento_pct' => (float) bcdiv(bcadd(bcadd($gpNoRealizada, $gpRealizada, 6), $ingresos, 6), $invertido, 10),
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function posiciones(): array
    {
        return [
            self::posicion('AAA', 'MXN', 'Tecnología', '10', '1100', '100', '0', '20', '1000'),
            self::posicion('BBB', 'USD', 'Tecnología', '5', '2940', '-60', '50', '0', '3000'),
            self::posicion('CCC', 'MXN', null, '1', '1960', '-40', '0', '0', '2000'),
            self::posicion('ZZZ', 'MXN', 'Energía', '0', '0', '0', '300', '0', '500'), // vendida
        ];
    }

    public function test_ordena_por_la_columna_pedida_y_oculta_las_vendidas(): void
    {
        $tabla = HoldingsTable::armar(self::posiciones(), self::TOTAL, null, 'valor', 'desc', false);

        $this->assertSame(['BBB', 'CCC', 'AAA'], array_column($tabla['grupos'][0]['filas'], 'simbolo'));
        $this->assertEqualsWithDelta(0.294, $tabla['grupos'][0]['filas'][0]['peso'], 1e-12);

        $porSimbolo = HoldingsTable::armar(self::posiciones(), self::TOTAL, null, 'simbolo', 'asc', true);
        $this->assertSame(['AAA', 'BBB', 'CCC', 'ZZZ'], array_column($porSimbolo['grupos'][0]['filas'], 'simbolo'));
        $this->assertFalse($porSimbolo['grupos'][0]['filas'][3]['abierta']);
    }

    /**
     * Grupo Tecnología (AAA + BBB): valor 1,100 + 2,940 = 4,040 → 40.4 % del total;
     * rendimiento (100 − 60 + 0 + 50 + 20) ÷ (1,000 + 3,000) = 110 ÷ 4,000 = 2.75 %.
     */
    public function test_agrupa_con_subtotales(): void
    {
        $tabla = HoldingsTable::armar(self::posiciones(), self::TOTAL, 'sector', 'valor', 'desc', false);

        $this->assertSame(['Tecnología', HoldingsTable::SIN_CLASIFICAR], array_column($tabla['grupos'], 'clave'));
        $tecnologia = $tabla['grupos'][0]['subtotal'];
        $this->assertSame(['4040.000000', '40.000000', '50.000000'], [$tecnologia['valor_base'], $tecnologia['gp_no_realizada'], $tecnologia['gp_realizada']]);
        $this->assertEqualsWithDelta(0.404, $tecnologia['peso'], 1e-12);
        $this->assertEqualsWithDelta(0.0275, $tecnologia['rendimiento_pct'], 1e-12);
        // Total: 6,000 de valor; rendimiento (0 + 50 + 20) ÷ 6,000.
        $this->assertSame('6000.000000', $tabla['total']['valor_base']);
        $this->assertEqualsWithDelta(70 / 6000, $tabla['total']['rendimiento_pct'], 1e-9);
    }

    public function test_las_vendidas_suman_su_ganancia_realizada(): void
    {
        $tabla = HoldingsTable::armar(self::posiciones(), self::TOTAL, 'moneda', 'valor', 'desc', true);

        $mxn = collect($tabla['grupos'])->firstWhere('clave', 'MXN');
        $this->assertSame(['CCC', 'AAA', 'ZZZ'], array_column($mxn['filas'], 'simbolo'));
        $this->assertSame('300.000000', $mxn['subtotal']['gp_realizada']);
    }
}
