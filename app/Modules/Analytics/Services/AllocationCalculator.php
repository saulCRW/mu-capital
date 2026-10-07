<?php

namespace App\Modules\Analytics\Services;

/**
 * Cálculo 13 de CLAUDE.md: asignación % por clase de activo, sector, moneda y país.
 *   peso = valor del grupo ÷ valor total del portafolio (incluido el efectivo)
 * El efectivo cuenta como clase "efectivo", en la moneda base, y como
 * "Efectivo" en sector y país. Activos sin dato quedan en "Sin clasificar".
 */
final class AllocationCalculator
{
    public const DIMENSIONES = ['clase', 'sector', 'moneda', 'pais'];

    private const SIN_CLASIFICAR = 'Sin clasificar';

    /**
     * @param  list<array{valor_base:string, tipo?:?string, sector?:?string, moneda?:?string, pais?:?string}>  $posiciones
     * @return list<array{clave:string, valor:string, peso:float}> de mayor a menor
     */
    /**
     * Grupo de una posición en la dimensión (su clase, sector, moneda o país).
     *
     * @param  array{tipo?:?string, sector?:?string, moneda?:?string, pais?:?string}  $p
     */
    public static function grupo(string $dimension, array $p): string
    {
        return match ($dimension) {
            'clase' => $p['tipo'] ?? null,
            'sector' => $p['sector'] ?? null,
            'moneda' => $p['moneda'] ?? null,
            'pais' => $p['pais'] ?? null,
        } ?: self::SIN_CLASIFICAR;
    }

    public static function calcular(string $dimension, array $posiciones, string $efectivo, string $monedaBase): array
    {
        $grupos = [];

        foreach ($posiciones as $p) {
            if (bccomp($p['valor_base'], '0', 6) === 0) {
                continue;
            }
            $clave = self::grupo($dimension, $p);
            $grupos[$clave] = bcadd($grupos[$clave] ?? '0', $p['valor_base'], 6);
        }

        if (bccomp($efectivo, '0', 6) !== 0) {
            $clave = match ($dimension) {
                'clase' => 'efectivo',
                'moneda' => $monedaBase,
                default => 'Efectivo',
            };
            $grupos[$clave] = bcadd($grupos[$clave] ?? '0', $efectivo, 6);
        }

        $total = array_reduce($grupos, fn ($s, $v) => bcadd($s, $v, 6), '0');
        if (bccomp($total, '0', 6) <= 0) {
            return [];
        }

        $resultado = [];
        foreach ($grupos as $clave => $valor) {
            $resultado[] = ['clave' => (string) $clave, 'valor' => $valor, 'peso' => (float) bcdiv($valor, $total, 10)];
        }
        usort($resultado, fn ($a, $b) => bccomp($b['valor'], $a['valor'], 6));

        return $resultado;
    }
}
