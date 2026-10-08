<?php

namespace App\Modules\Analytics\Services;

use Carbon\CarbonImmutable;

/**
 * Cálculos 9–11 de PROYECTO.md (sección 5). Son razones (porcentajes), por eso usan float.
 *
 *  9. TWR (time-weighted return): encadena los rendimientos diarios quitando
 *     el efecto de depósitos y retiros → mide la calidad de las decisiones de inversión.
 *       r_día = V_hoy ÷ (V_ayer + flujo_de_hoy) − 1      (flujo al inicio del día)
 *       TWR   = (1 + r_1) × (1 + r_2) × … − 1
 * 10. XIRR (money-weighted): la tasa anual r que hace
 *       Σ flujo_i ÷ (1 + r)^(días_i ÷ 365) = 0
 *     (aportaciones negativas, retiros y valor final positivos) → lo que ganó el inversionista.
 * 11. Benchmark: valor_final ÷ valor_inicial − 1 en el mismo periodo.
 */
final class ReturnCalculator
{
    /**
     * Rendimientos diarios a partir de una serie de valores. El primer
     * elemento es la base (su flujo se ignora).
     *
     * @param  list<array{fecha:string, valor:string|float, flujo:string|float}>  $serie
     * @return list<array{fecha:string, r:float}>
     */
    public static function rendimientosDiarios(array $serie): array
    {
        $rendimientos = [];

        for ($i = 1, $n = count($serie); $i < $n; $i++) {
            $denominador = (float) $serie[$i - 1]['valor'] + (float) $serie[$i]['flujo'];

            if ($denominador <= 0) {
                continue; // portafolio vacío: no hay rendimiento que medir ese día
            }

            $rendimientos[] = ['fecha' => $serie[$i]['fecha'], 'r' => (float) $serie[$i]['valor'] / $denominador - 1];
        }

        return $rendimientos;
    }

    /** @param  list<float>  $rendimientos */
    public static function twr(array $rendimientos): float
    {
        return array_product(array_map(fn ($r) => 1 + $r, $rendimientos)) - 1;
    }

    /**
     * Índice acumulado (base 1) para graficar o medir drawdown.
     *
     * @param  list<float>  $rendimientos
     * @return list<float>
     */
    public static function indice(array $rendimientos): array
    {
        $acumulado = 1.0;

        return array_map(function ($r) use (&$acumulado) {
            return $acumulado *= 1 + $r;
        }, $rendimientos);
    }

    /**
     * XIRR por Newton-Raphson con respaldo de bisección.
     *
     * @param  list<array{fecha:string, monto:float|string}>  $flujos
     */
    public static function xirr(array $flujos): ?float
    {
        $flujos = array_values(array_filter($flujos, fn ($f) => (float) $f['monto'] != 0.0));
        $montos = array_map(fn ($f) => (float) $f['monto'], $flujos);

        if (count($flujos) < 2 || min($montos) >= 0 || max($montos) <= 0) {
            return null; // se necesita al menos un flujo negativo y uno positivo
        }

        $inicio = CarbonImmutable::parse(min(array_column($flujos, 'fecha')));
        $anios = array_map(fn ($f) => $inicio->diffInDays(CarbonImmutable::parse($f['fecha'])) / 365, $flujos);

        $vpn = function (float $r) use ($montos, $anios): float {
            $suma = 0.0;
            foreach ($montos as $i => $m) {
                $suma += $m / ((1 + $r) ** $anios[$i]);
            }

            return $suma;
        };

        // Newton-Raphson
        $r = 0.1;
        for ($i = 0; $i < 100; $i++) {
            $f = $vpn($r);
            $derivada = 0.0;
            foreach ($montos as $k => $m) {
                $derivada -= $anios[$k] * $m / ((1 + $r) ** ($anios[$k] + 1));
            }
            if (abs($f) < 1e-9) {
                return $r;
            }
            if ($derivada == 0.0) {
                break;
            }
            $siguiente = $r - $f / $derivada;
            if (! is_finite($siguiente) || $siguiente <= -0.999999) {
                break;
            }
            if (abs($siguiente - $r) < 1e-12) {
                return $siguiente;
            }
            $r = $siguiente;
        }

        // Bisección entre -99.99% y +10,000%
        $bajo = -0.9999;
        $alto = 100.0;
        $fBajo = $vpn($bajo);
        if ($fBajo * $vpn($alto) > 0) {
            return null;
        }
        for ($i = 0; $i < 300; $i++) {
            $medio = ($bajo + $alto) / 2;
            $fMedio = $vpn($medio);
            if (abs($fMedio) < 1e-9 || ($alto - $bajo) < 1e-12) {
                return $medio;
            }
            if ($fBajo * $fMedio < 0) {
                $alto = $medio;
            } else {
                $bajo = $medio;
                $fBajo = $fMedio;
            }
        }

        return ($bajo + $alto) / 2;
    }

    /**
     * Rendimiento de una serie de valores (benchmark) entre dos fechas,
     * usando el último valor en o antes de cada fecha.
     *
     * @param  array<string, string|float>  $serie  fecha => valor
     */
    public static function rendimientoSerie(array $serie, string $desde, string $hasta): ?float
    {
        $inicial = self::valorEn($serie, $desde);
        $final = self::valorEn($serie, $hasta);

        return $inicial !== null && $final !== null && $inicial > 0 ? $final / $inicial - 1 : null;
    }

    /** @param  array<string, string|float>  $serie  fecha => valor */
    public static function valorEn(array $serie, string $fecha): ?float
    {
        $valor = null;
        ksort($serie);
        foreach ($serie as $f => $v) {
            if ($f > $fecha) {
                break;
            }
            $valor = (float) $v;
        }

        return $valor;
    }
}
