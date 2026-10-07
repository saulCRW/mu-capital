<?php

namespace App\Modules\Analytics\Services;

/**
 * Cálculos 8, 14 y 15 de CLAUDE.md.
 *
 *  8. Rendimiento total = G/P total ÷ aportaciones netas (depósitos − retiros).
 *     Anualizado = (1 + total)^(365 ÷ días) − 1.
 * 14. Top ganadores / perdedores: posiciones abiertas por G/P no realizada %.
 * 15. Contribución de cada activo = (G/P no realizada + realizada + ingresos del activo)
 *     ÷ aportaciones netas. Lo no atribuible (intereses de la cuenta − comisiones
 *     sueltas) va en "otros". La suma de contribuciones = rendimiento total.
 */
final class PerformanceCalculator
{
    public static function rendimientoTotal(string $gpTotal, string $aportacionesNetas): ?float
    {
        return bccomp($aportacionesNetas, '0', 6) > 0 ? (float) bcdiv($gpTotal, $aportacionesNetas, 10) : null;
    }

    public static function anualizar(?float $rendimiento, int $dias): ?float
    {
        if ($rendimiento === null || $dias <= 0) {
            return null;
        }

        return $rendimiento <= -1 ? -1.0 : (1 + $rendimiento) ** (365 / $dias) - 1;
    }

    /**
     * @param  list<array{asset_id:int|string, cantidad:string, gp_no_realizada_pct:?float}>  $posiciones
     * @return array{ganadores: list<array>, perdedores: list<array>}
     */
    public static function top(array $posiciones, int $n = 5): array
    {
        $abiertas = array_filter($posiciones, fn ($p) => bccomp($p['cantidad'], '0', 6) > 0 && $p['gp_no_realizada_pct'] !== null);

        $ganadores = array_filter($abiertas, fn ($p) => $p['gp_no_realizada_pct'] > 0);
        usort($ganadores, fn ($a, $b) => $b['gp_no_realizada_pct'] <=> $a['gp_no_realizada_pct']);

        $perdedores = array_filter($abiertas, fn ($p) => $p['gp_no_realizada_pct'] < 0);
        usort($perdedores, fn ($a, $b) => $a['gp_no_realizada_pct'] <=> $b['gp_no_realizada_pct']);

        return ['ganadores' => array_slice($ganadores, 0, $n), 'perdedores' => array_slice($perdedores, 0, $n)];
    }

    /**
     * @param  list<array{asset_id:int|string, gp_no_realizada:string, gp_realizada:string, ingresos:string}>  $posiciones
     * @return list<array{asset_id:int|string|null, aporte:string, contribucion:?float}> de mayor a menor; asset_id null = "otros"
     */
    public static function contribucion(array $posiciones, string $otros, string $aportacionesNetas): array
    {
        $filas = [];
        foreach ($posiciones as $p) {
            $aporte = bcadd(bcadd($p['gp_no_realizada'], $p['gp_realizada'], 6), $p['ingresos'], 6);
            $filas[] = ['asset_id' => $p['asset_id'], 'aporte' => $aporte, 'contribucion' => self::rendimientoTotal($aporte, $aportacionesNetas)];
        }

        if (bccomp($otros, '0', 6) !== 0) {
            $filas[] = ['asset_id' => null, 'aporte' => $otros, 'contribucion' => self::rendimientoTotal($otros, $aportacionesNetas)];
        }

        usort($filas, fn ($a, $b) => bccomp($b['aporte'], $a['aporte'], 6));

        return $filas;
    }
}
