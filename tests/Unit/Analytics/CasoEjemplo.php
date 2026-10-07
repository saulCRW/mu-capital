<?php

namespace Tests\Unit\Analytics;

use App\Modules\Analytics\Services\PriceBook;

/**
 * CASO DE EJEMPLO verificable a mano (moneda base MXN).
 *
 * Activos: A = acción mexicana en MXN (id 1), B = ETF estadounidense en USD (id 2).
 *
 *  #  Fecha       Tipo        Detalle                    Efectivo   Posición
 *  1  02/01/2026  DEPOSITO    10,000                       10,000
 *  2  02/01/2026  COMPRA A    100 @ 40                      6,000    A 100 (costo 4,000)
 *  3  05/01/2026  COMPRA B    10 @ 20 USD, tc 17 = 3,400    2,600    B 10  (costo 3,400)
 *  4  02/02/2026  COMPRA A    50 @ 52 = 2,600                   0    A 150 (costo 6,600, prom. 44)
 *  5  02/03/2026  VENTA A     60 @ 50 = 3,000               3,000    A 90  (costo 3,960; realizada 3,000 − 60×44 = 360)
 *  6  16/03/2026  DIVIDENDO A 90                            3,090
 *  7  01/04/2026  SPLIT A     2 por 1                       3,090    A 180 (costo 3,960, prom. 22)
 *  8  15/04/2026  DEPOSITO    1,000                         4,090
 *  9  30/04/2026  COMISION    40                            4,050
 * 10  04/05/2026  RETIRO      500                           3,550
 * 11  15/05/2026  INTERES     10                            3,560
 *
 * Valuación al 30/06/2026: A = 25 MXN, B = 22 USD, USD/MXN = 18.
 *   A: 180 × 25       = 4,500   G/P no realizada 4,500 − 3,960 =   540 (13.64%)
 *   B: 10 × 22 × 18   = 3,960   G/P no realizada 3,960 − 3,400 =   560 (16.47%)
 *   Efectivo                           3,560
 *   Valor total                       12,020
 *   Aportaciones netas 10,000 + 1,000 − 500 = 10,500
 *   G/P total 12,020 − 10,500 = 1,520 = 1,100 no realizada + 360 realizada + 90 dividendo + 10 interés − 40 comisión
 *   Rendimiento total 1,520 ÷ 10,500 = 14.4762%; en 179 días → anualizado 31.7427%
 */
trait CasoEjemplo
{
    protected function transaccionesEjemplo(): array
    {
        $t = fn (int $id, string $tipo, string $fecha, ?int $activo, string $cantidad, string $precio = '1', string $moneda = 'MXN', string $tc = '1') => [
            'id' => $id, 'tipo' => $tipo, 'fecha' => $fecha, 'asset_id' => $activo, 'cantidad' => $cantidad,
            'precio' => $precio, 'comision' => '0', 'moneda' => $moneda, 'tipo_cambio' => $tc,
        ];

        return [
            $t(1, 'DEPOSITO', '2026-01-02', null, '10000'),
            $t(2, 'COMPRA', '2026-01-02', 1, '100', '40'),
            $t(3, 'COMPRA', '2026-01-05', 2, '10', '20', 'USD', '17'),
            $t(4, 'COMPRA', '2026-02-02', 1, '50', '52'),
            $t(5, 'VENTA', '2026-03-02', 1, '60', '50'),
            $t(6, 'DIVIDENDO', '2026-03-16', 1, '90'),
            $t(7, 'SPLIT', '2026-04-01', 1, '2', '0'),
            $t(8, 'DEPOSITO', '2026-04-15', null, '1000'),
            $t(9, 'COMISION', '2026-04-30', null, '40'),
            $t(10, 'RETIRO', '2026-05-04', null, '500'),
            $t(11, 'INTERES', '2026-05-15', null, '10'),
        ];
    }

    protected function preciosEjemplo(): PriceBook
    {
        return new PriceBook(
            [1 => ['2026-06-30' => '25'], 2 => ['2026-06-30' => '22']],
            ['USD>MXN' => ['2026-06-30' => '18']],
        );
    }

    /** Datos descriptivos de los activos (para asignación). */
    protected function metaEjemplo(): array
    {
        return [
            1 => ['tipo' => 'accion', 'sector' => 'Tecnología', 'pais' => 'México'],
            2 => ['tipo' => 'etf', 'sector' => 'Diversificado', 'pais' => 'Estados Unidos'],
        ];
    }
}
