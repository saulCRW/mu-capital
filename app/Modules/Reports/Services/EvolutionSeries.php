<?php

namespace App\Modules\Reports\Services;

use App\Modules\Analytics\Services\ReturnCalculator;

/**
 * Series para las gráficas de evolución, a partir de la serie diaria
 * (portfolio_snapshots) y los valores del benchmark.
 *
 *   valor     = valor del portafolio cada día
 *   aportado  = valor del primer punto + flujos acumulados (capital puesto por el cliente)
 *   indice    = 100 × Π(1 + r_día)  (TWR acumulado, sin el efecto de depósitos y retiros)
 *   benchmark = 100 × valor_índice ÷ valor_índice en el primer punto
 */
final class EvolutionSeries
{
    /**
     * @param  list<array{fecha:string, valor:string, flujo:string}>  $serie  ordenada; el primer punto es la base
     * @param  array<string, string>  $benchmark  fecha => valor
     * @return array{valor: list<array{0:string, 1:float}>, aportado: list<array{0:string, 1:float}>,
     *               indice: list<array{0:string, 1:float}>, benchmark: list<array{0:string, 1:float}>}
     */
    public static function calcular(array $serie, array $benchmark = []): array
    {
        $vacio = ['valor' => [], 'aportado' => [], 'indice' => [], 'benchmark' => []];
        if ($serie === []) {
            return $vacio;
        }

        $r = array_column(ReturnCalculator::rendimientosDiarios($serie), 'r', 'fecha');
        $baseBenchmark = $benchmark === [] ? null : (ReturnCalculator::valorEn($benchmark, $serie[0]['fecha']) ?? self::primero($benchmark));

        $resultado = $vacio;
        $aportado = '0';
        $indice = 100.0;
        foreach ($serie as $i => $p) {
            $aportado = $i === 0 ? $p['valor'] : bcadd($aportado, $p['flujo'], 6);
            $indice *= 1 + ($r[$p['fecha']] ?? 0.0);

            $resultado['valor'][] = [$p['fecha'], (float) $p['valor']];
            $resultado['aportado'][] = [$p['fecha'], (float) $aportado];
            $resultado['indice'][] = [$p['fecha'], $indice];

            $b = $baseBenchmark ? ReturnCalculator::valorEn($benchmark, $p['fecha']) : null;
            if ($b !== null) {
                $resultado['benchmark'][] = [$p['fecha'], 100 * $b / $baseBenchmark];
            }
        }

        return $resultado;
    }

    /** @param  array<string, string>  $serie */
    private static function primero(array $serie): ?float
    {
        ksort($serie);
        $v = reset($serie);

        return $v === false || (float) $v <= 0 ? null : (float) $v;
    }
}
