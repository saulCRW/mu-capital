<?php

namespace App\Modules\Transactions\Services;

use App\Modules\Transactions\Models\Transaction;

/**
 * Reproduce el libro de transacciones de un portafolio en orden cronológico
 * y detecta el primer momento en que:
 *   - se vende más de lo que se tiene, o
 *   - el efectivo queda negativo.
 *
 * Es una clase "pura": no consulta la base de datos, recibe arreglos.
 * Toda la aritmética usa bcmath (texto con decimales exactos), nunca float.
 *
 * Efecto de cada tipo en el efectivo (en moneda base; tc = tipo_cambio):
 *   DEPOSITO, DIVIDENDO, INTERES  →  + cantidad × tc
 *   RETIRO, COMISION              →  − cantidad × tc
 *   COMPRA                        →  − (cantidad × precio + comisión) × tc
 *   VENTA                         →  + (cantidad × precio − comisión) × tc
 *   SPLIT                         →  0  (la posición se multiplica por el factor)
 */
final class LedgerValidator
{
    private const ESCALA = 6;

    /**
     * Orden dentro de un mismo día: primero el split, luego las entradas de
     * efectivo (incluidas las ventas) y al final las salidas. Así un depósito
     * o una venta del mismo día pueden fondear una compra de ese día.
     */
    private const ORDEN_DEL_DIA = [
        Transaction::SPLIT => 0,
        Transaction::DEPOSITO => 1,
        Transaction::DIVIDENDO => 2,
        Transaction::INTERES => 3,
        Transaction::VENTA => 4,
        Transaction::COMPRA => 5,
        Transaction::RETIRO => 6,
        Transaction::COMISION => 7,
    ];

    /**
     * @param  list<array{id?:int|null, tipo:string, fecha:string, asset_id?:int|null, simbolo?:string|null, cantidad:string, precio:string, comision:string, tipo_cambio:string}>  $movimientos
     * @return array{efectivo:string, posiciones:array<int|string,string>, error:?string}
     */
    public function reproducir(array $movimientos, string $monedaBase): array
    {
        $efectivo = '0';
        $posiciones = [];

        foreach (self::ordenar($movimientos) as $m) {
            $activo = $m['asset_id'] ?? null;
            $tenencia = $activo !== null ? ($posiciones[$activo] ?? '0') : '0';

            switch ($m['tipo']) {
                case Transaction::COMPRA:
                    $posiciones[$activo] = bcadd($tenencia, $m['cantidad'], self::ESCALA);
                    break;

                case Transaction::VENTA:
                    if (bccomp($m['cantidad'], $tenencia, self::ESCALA) > 0) {
                        return $this->resultado($efectivo, $posiciones, sprintf(
                            'El %s se venderían %s %s, pero en esa fecha solo se tendrían %s.',
                            self::fecha($m['fecha']),
                            self::cantidad($m['cantidad']),
                            $m['simbolo'] ?? 'títulos',
                            self::cantidad($tenencia),
                        ));
                    }
                    $posiciones[$activo] = bcsub($tenencia, $m['cantidad'], self::ESCALA);
                    break;

                case Transaction::SPLIT:
                    $posiciones[$activo] = bcmul($tenencia, $m['cantidad'], self::ESCALA);
                    break;
            }

            $efectivo = bcadd($efectivo, self::efectoEnEfectivo($m), self::ESCALA);

            if (bccomp($efectivo, '0', self::ESCALA) < 0) {
                return $this->resultado($efectivo, $posiciones, sprintf(
                    'El %s el efectivo quedaría en %s %s. No se puede gastar efectivo inexistente.',
                    self::fecha($m['fecha']),
                    self::dinero($efectivo),
                    $monedaBase,
                ));
            }
        }

        return $this->resultado($efectivo, $posiciones, null);
    }

    /** Mensaje del primer problema del libro, o null si todo cuadra. */
    public function primerError(array $movimientos, string $monedaBase): ?string
    {
        return $this->reproducir($movimientos, $monedaBase)['error'];
    }

    /** Cuánto suma o resta al efectivo una transacción, ya en moneda base. */
    public static function efectoEnEfectivo(array $m): string
    {
        $importe = bcmul($m['cantidad'], $m['precio'], 12);

        $enMonedaDeLaTransaccion = match ($m['tipo']) {
            Transaction::DEPOSITO, Transaction::DIVIDENDO, Transaction::INTERES => $m['cantidad'],
            Transaction::RETIRO, Transaction::COMISION => bcsub('0', $m['cantidad'], 12),
            Transaction::COMPRA => bcsub('0', bcadd($importe, $m['comision'], 12), 12),
            Transaction::VENTA => bcsub($importe, $m['comision'], 12),
            Transaction::SPLIT => '0',
        };

        return bcmul($enMonedaDeLaTransaccion, $m['tipo_cambio'], self::ESCALA);
    }

    /** Orden cronológico: fecha, luego ORDEN_DEL_DIA, luego id (las nuevas, sin id, al final). */
    public static function ordenar(array $movimientos): array
    {
        usort($movimientos, fn ($a, $b) => [$a['fecha'], self::ORDEN_DEL_DIA[$a['tipo']], $a['id'] ?? PHP_INT_MAX]
            <=> [$b['fecha'], self::ORDEN_DEL_DIA[$b['tipo']], $b['id'] ?? PHP_INT_MAX]);

        return $movimientos;
    }

    private function resultado(string $efectivo, array $posiciones, ?string $error): array
    {
        return ['efectivo' => $efectivo, 'posiciones' => $posiciones, 'error' => $error];
    }

    private static function fecha(string $ymd): string
    {
        [$y, $m, $d] = explode('-', $ymd);

        return "$d/$m/$y";
    }

    /** Formato es-MX con 2 decimales: 1,234,567.89 */
    private static function dinero(string $valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }

    /** Formato es-MX con hasta 6 decimales, sin ceros sobrantes: 1,250.5 */
    private static function cantidad(string $valor): string
    {
        return rtrim(rtrim(number_format((float) $valor, 6, '.', ','), '0'), '.');
    }
}
