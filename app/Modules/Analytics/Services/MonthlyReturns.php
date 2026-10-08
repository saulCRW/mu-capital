<?php

namespace App\Modules\Analytics\Services;

/**
 * Rendimiento de cada mes y de cada año (heatmap de la pestaña Análisis),
 * encadenando los rendimientos diarios (TWR) de portfolio_snapshots:
 *
 *   rendimiento_mes = Π(1 + r_día) − 1   para los días del mes
 *   rendimiento_año = Π(1 + rendimiento_mes) − 1
 *
 * Ejemplo: en enero los días rinden +10 %, 0 % y −10 % → 1.10 × 1.00 × 0.90 − 1 = −1 %.
 * Si febrero rinde +5 %, el año va en 0.99 × 1.05 − 1 = 3.95 %.
 * Depósitos y retiros no cuentan como rendimiento (ver ReturnCalculator::rendimientosDiarios).
 */
final class MonthlyReturns
{
    /**
     * @param  list<array{fecha:string, valor:string|float, flujo:string|float}>  $serie  ordenada; el primer punto es la base
     * @return array<int, array{meses: array<int, float>, anio: float}> año => [mes (1–12) => rendimiento, anio => rendimiento]
     */
    public static function calcular(array $serie): array
    {
        $factores = [];
        foreach (ReturnCalculator::rendimientosDiarios($serie) as $d) {
            $anio = (int) substr($d['fecha'], 0, 4);
            $mes = (int) substr($d['fecha'], 5, 2);
            $factores[$anio][$mes] = ($factores[$anio][$mes] ?? 1.0) * (1 + $d['r']);
        }
        ksort($factores);

        $resultado = [];
        foreach ($factores as $anio => $meses) {
            ksort($meses);
            $resultado[$anio] = [
                'meses' => array_map(fn (float $f) => $f - 1, $meses),
                'anio' => array_product($meses) - 1,
            ];
        }

        return $resultado;
    }
}
