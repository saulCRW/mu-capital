<?php

namespace App\Modules\Reports\Services;

use App\Modules\Analytics\Services\ReturnCalculator;
use Carbon\CarbonImmutable;

/**
 * Agrega la serie diaria de un portafolio (portfolio_snapshots) por trimestre.
 *
 * Para cada trimestre:
 *   valor inicial = valor al cierre del trimestre anterior (0 si no existía)
 *   flujo neto    = Σ depósitos − retiros del trimestre
 *   ganancia      = valor final − valor inicial − flujo neto
 *   TWR           = rendimientos diarios encadenados dentro del trimestre
 *   fecha base    = cierre del trimestre anterior, o el primer día con datos
 *                   si el portafolio empezó en el trimestre (para medir el benchmark igual)
 * El año acumula: ganancia y flujo se suman, el TWR se encadena por trimestre.
 */
final class QuarterlyAggregator
{
    /**
     * @param  list<array{fecha:string, valor:string, flujo:string}>  $serie  ordenada por fecha; puede empezar antes del año
     * @param  int  $hastaTrimestre  último trimestre a incluir (1–4)
     * @return array{trimestres: list<array{trimestre:int, desde:string, hasta:string, fecha_base:string, valor_inicial:string, valor_final:string, flujo_neto:string, ganancia:string, twr:?float}>,
     *               anio: ?array{fecha_base:string, valor_inicial:string, valor_final:string, flujo_neto:string, ganancia:string, twr:?float}}
     */
    public static function agregar(array $serie, int $anio, int $hastaTrimestre = 4): array
    {
        $anterior = null;
        $porTrimestre = [];

        foreach ($serie as $punto) {
            $fecha = CarbonImmutable::parse($punto['fecha']);
            if ($fecha->year < $anio) {
                $anterior = $punto;
            } elseif ($fecha->year === $anio && $fecha->quarter <= $hastaTrimestre) {
                $porTrimestre[$fecha->quarter][] = $punto;
            }
        }

        $trimestres = [];
        foreach (range(1, $hastaTrimestre) as $t) {
            if (empty($porTrimestre[$t])) {
                continue; // el portafolio aún no existía (o no hay datos) en ese trimestre
            }

            $puntos = $porTrimestre[$t];
            $inicio = CarbonImmutable::create($anio, $t * 3 - 2, 1);
            $inicial = $anterior['valor'] ?? '0';
            $final = end($puntos)['valor'];
            $flujo = array_reduce($puntos, fn ($s, $p) => bcadd($s, $p['flujo'], 6), '0');
            $diarios = ReturnCalculator::rendimientosDiarios([
                ['fecha' => $anterior['fecha'] ?? $inicio->subDay()->toDateString(), 'valor' => $inicial, 'flujo' => '0'],
                ...$puntos,
            ]);

            $trimestres[] = [
                'trimestre' => $t,
                'desde' => $inicio->toDateString(),
                'hasta' => $inicio->endOfQuarter()->toDateString(),
                'fecha_base' => $anterior['fecha'] ?? $puntos[0]['fecha'],
                'valor_inicial' => bcadd($inicial, '0', 6),
                'valor_final' => bcadd($final, '0', 6),
                'flujo_neto' => $flujo,
                'ganancia' => bcsub(bcsub($final, $inicial, 6), $flujo, 6),
                'twr' => $diarios === [] ? null : ReturnCalculator::twr(array_column($diarios, 'r')),
            ];
            $anterior = end($puntos);
        }

        if ($trimestres === []) {
            return ['trimestres' => [], 'anio' => null];
        }

        $conTwr = array_filter(array_column($trimestres, 'twr'), fn ($r) => $r !== null);

        return [
            'trimestres' => $trimestres,
            'anio' => [
                'fecha_base' => $trimestres[0]['fecha_base'],
                'valor_inicial' => $trimestres[0]['valor_inicial'],
                'valor_final' => end($trimestres)['valor_final'],
                'flujo_neto' => array_reduce($trimestres, fn ($s, $q) => bcadd($s, $q['flujo_neto'], 6), '0'),
                'ganancia' => array_reduce($trimestres, fn ($s, $q) => bcadd($s, $q['ganancia'], 6), '0'),
                'twr' => $conTwr === [] ? null : ReturnCalculator::twr($conTwr),
            ],
        ];
    }
}
