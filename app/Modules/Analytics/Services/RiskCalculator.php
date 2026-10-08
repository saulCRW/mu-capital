<?php

namespace App\Modules\Analytics\Services;

/**
 * Cálculo 12 de PROYECTO.md (sección 5), a partir de rendimientos diarios (TWR diario).
 * Se anualiza con 252 días hábiles por año.
 *
 *  Volatilidad anualizada = desviación estándar muestral (n − 1) × √252
 *  Beta    = covarianza(portafolio, benchmark) ÷ varianza(benchmark)
 *  Sharpe  = (promedio(r) − rf_diaria) ÷ desviación(r) × √252
 *  Sortino = (promedio(r) − rf_diaria) ÷ desviación_a_la_baja × √252
 *            desviación_a_la_baja = √( Σ min(0, r − rf_diaria)² ÷ n )
 *  rf_diaria = (1 + rf_anual)^(1/252) − 1
 *  Máximo drawdown = peor caída del índice acumulado desde su máximo previo.
 */
final class RiskCalculator
{
    public const DIAS_POR_ANIO = 252;

    /** @param  list<float>  $r */
    public static function volatilidad(array $r): ?float
    {
        $desv = self::desviacion($r);

        return $desv === null ? null : $desv * sqrt(self::DIAS_POR_ANIO);
    }

    /**
     * @param  list<float>  $portafolio
     * @param  list<float>  $benchmark  mismos días que $portafolio
     */
    public static function beta(array $portafolio, array $benchmark): ?float
    {
        $n = count($portafolio);
        if ($n < 2 || $n !== count($benchmark)) {
            return null;
        }

        $mp = array_sum($portafolio) / $n;
        $mb = array_sum($benchmark) / $n;
        $cov = 0.0;
        $var = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $cov += ($portafolio[$i] - $mp) * ($benchmark[$i] - $mb);
            $var += ($benchmark[$i] - $mb) ** 2;
        }

        return $var > 0 ? $cov / $var : null;
    }

    /** @param  list<float>  $r */
    public static function sharpe(array $r, float $rfAnual): ?float
    {
        $desv = self::desviacion($r);
        if ($desv === null || $desv == 0.0) {
            return null;
        }

        return (self::promedio($r) - self::rfDiaria($rfAnual)) / $desv * sqrt(self::DIAS_POR_ANIO);
    }

    /** @param  list<float>  $r */
    public static function sortino(array $r, float $rfAnual): ?float
    {
        if (count($r) < 2) {
            return null;
        }

        $rf = self::rfDiaria($rfAnual);
        $abajo = array_sum(array_map(fn ($x) => min(0.0, $x - $rf) ** 2, $r)) / count($r);
        if ($abajo == 0.0) {
            return null; // ningún día por debajo de la tasa libre de riesgo
        }

        return (self::promedio($r) - $rf) / sqrt($abajo) * sqrt(self::DIAS_POR_ANIO);
    }

    /**
     * Valor negativo o cero: −0.02 significa una caída máxima de 2%.
     *
     * @param  list<float>  $r
     */
    public static function maxDrawdown(array $r): float
    {
        $maximo = 1.0;
        $peor = 0.0;
        foreach (ReturnCalculator::indice($r) as $valor) {
            $maximo = max($maximo, $valor);
            $peor = min($peor, $valor / $maximo - 1);
        }

        return $peor;
    }

    public static function rfDiaria(float $rfAnual): float
    {
        return (1 + $rfAnual) ** (1 / self::DIAS_POR_ANIO) - 1;
    }

    private static function promedio(array $r): float
    {
        return array_sum($r) / count($r);
    }

    private static function desviacion(array $r): ?float
    {
        $n = count($r);
        if ($n < 2) {
            return null;
        }
        $m = self::promedio($r);

        return sqrt(array_sum(array_map(fn ($x) => ($x - $m) ** 2, $r)) / ($n - 1));
    }
}
