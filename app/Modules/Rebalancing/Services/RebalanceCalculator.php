<?php

namespace App\Modules\Rebalancing\Services;

use App\Modules\Analytics\Services\AllocationCalculator;

/**
 * Rebalanceo contra la asignación objetivo.
 *
 *   desviación = peso actual − peso objetivo            (en puntos de la fracción)
 *   ajuste     = valor total × peso objetivo − valor actual del grupo
 *                (positivo = comprar, negativo = vender; en moneda base)
 *   fuera      = |desviación| > tolerancia
 *
 * Si algún grupo queda fuera de la banda se propone volver TODOS al objetivo:
 * el ajuste de cada grupo se reparte entre sus posiciones en proporción a su
 * valor y se convierte a títulos con el último precio y tipo de cambio.
 */
final class RebalanceCalculator
{
    /** Banda de tolerancia por omisión: ±5 puntos porcentuales. */
    public const TOLERANCIA = 0.05;

    /**
     * @param  list<array{clave:string, valor:string, peso:float}>  $actual  de AllocationCalculator
     * @param  array<string, float>  $objetivo  clave => fracción (0.5 = 50 %)
     * @return list<array{clave:string, actual:float, objetivo:?float, desviacion:?float, valor:string, valor_objetivo:?string, ajuste:?string, fuera:bool}>
     */
    public static function comparar(array $actual, array $objetivo, string $total, float $tolerancia = self::TOLERANCIA): array
    {
        $porClave = array_column($actual, null, 'clave');
        $claves = array_values(array_unique([...array_keys($objetivo), ...array_keys($porClave)]));

        $filas = [];
        foreach ($claves as $clave) {
            $peso = $porClave[$clave]['peso'] ?? 0.0;
            $valor = $porClave[$clave]['valor'] ?? '0';
            // Sin objetivos definidos no hay contra qué comparar; un grupo sin objetivo, con objetivos definidos, vale 0 %.
            $meta = $objetivo === [] ? null : ($objetivo[$clave] ?? 0.0);
            $valorObjetivo = $meta === null ? null : bcmul($total, self::decimal($meta), 6);

            $filas[] = [
                'clave' => (string) $clave,
                'actual' => $peso,
                'objetivo' => $meta,
                'desviacion' => $meta === null ? null : $peso - $meta,
                'valor' => $valor,
                'valor_objetivo' => $valorObjetivo,
                'ajuste' => $valorObjetivo === null ? null : bcsub($valorObjetivo, $valor, 6),
                'fuera' => $meta !== null && abs($peso - $meta) > $tolerancia + 1e-12,
            ];
        }

        usort($filas, fn ($a, $b) => [$b['objetivo'] ?? -1, $b['actual']] <=> [$a['objetivo'] ?? -1, $a['actual']]);

        return $filas;
    }

    /**
     * Operaciones para volver al objetivo. El grupo del efectivo no genera
     * operaciones: es lo que queda tras comprar y vender. Los demás grupos sin
     * posiciones quedan en "sin_activos" (hay que elegir qué comprar).
     *
     * @param  list<array{clave:string, ajuste:?string, fuera:bool}>  $comparacion
     * @param  list<array{asset_id:int, simbolo:string, tipo:?string, sector:?string, moneda:?string, pais:?string, cantidad:string, valor_base:string, precio:?string, tasa:?string}>  $posiciones
     * @param  ?string  $grupoEfectivo  clave del grupo que representa el efectivo en esta dimensión
     * @return array{operaciones: list<array{asset_id:int, simbolo:string, grupo:string, tipo:string, monto:string, titulos:string}>,
     *               sin_activos: list<array{clave:string, ajuste:string}>}
     */
    public static function operaciones(array $comparacion, array $posiciones, string $dimension, ?string $grupoEfectivo = null): array
    {
        $resultado = ['operaciones' => [], 'sin_activos' => []];
        if (! collect($comparacion)->contains('fuera', true)) {
            return $resultado;
        }

        $abiertas = array_filter($posiciones, fn ($p) => bccomp($p['cantidad'], '0', 6) > 0 && bccomp($p['valor_base'], '0', 6) > 0);
        $porGrupo = [];
        foreach ($abiertas as $p) {
            $porGrupo[AllocationCalculator::grupo($dimension, $p)][] = $p;
        }

        foreach ($comparacion as $fila) {
            if ($fila['ajuste'] === null || abs((float) $fila['ajuste']) < 0.01 || $fila['clave'] === $grupoEfectivo) {
                continue;
            }
            $grupo = $porGrupo[$fila['clave']] ?? [];
            if ($grupo === []) {
                $resultado['sin_activos'][] = ['clave' => $fila['clave'], 'ajuste' => $fila['ajuste']];

                continue;
            }

            $valorGrupo = array_reduce($grupo, fn ($s, $p) => bcadd($s, $p['valor_base'], 6), '0');
            foreach ($grupo as $p) {
                $monto = bcdiv(bcmul($fila['ajuste'], $p['valor_base'], 6), $valorGrupo, 6);
                $titulos = self::titulos($monto, $p);
                if ($titulos === null) {
                    continue;
                }
                $resultado['operaciones'][] = [
                    'asset_id' => $p['asset_id'],
                    'simbolo' => $p['simbolo'],
                    'grupo' => $fila['clave'],
                    'tipo' => bccomp($monto, '0', 6) > 0 ? 'COMPRA' : 'VENTA',
                    'monto' => $monto,
                    'titulos' => $titulos,
                ];
            }
        }

        usort($resultado['operaciones'], fn ($a, $b) => [$a['tipo'] === 'COMPRA', $a['simbolo']] <=> [$b['tipo'] === 'COMPRA', $b['simbolo']]);

        return $resultado;
    }

    /**
     * Títulos que equivalen al monto: enteros, salvo cripto (6 decimales). Una venta
     * nunca pasa de lo que se tiene. null si no alcanza para un título.
     */
    private static function titulos(string $monto, array $p): ?string
    {
        if ($p['precio'] === null || $p['tasa'] === null || bccomp($p['precio'], '0', 6) <= 0) {
            return null;
        }

        $exacto = bcdiv(ltrim($monto, '-'), bcmul($p['precio'], $p['tasa'], 10), 10);
        $titulos = bcadd($exacto, '0', ($p['tipo'] ?? null) === 'cripto' ? 6 : 0);
        if (bccomp($monto, '0', 6) < 0 && bccomp($titulos, $p['cantidad'], 6) > 0) {
            $titulos = bcadd($p['cantidad'], '0', ($p['tipo'] ?? null) === 'cripto' ? 6 : 0);
        }

        return bccomp($titulos, '0', 6) > 0 ? $titulos : null;
    }

    private static function decimal(float $fraccion): string
    {
        return number_format($fraccion, 10, '.', '');
    }
}
