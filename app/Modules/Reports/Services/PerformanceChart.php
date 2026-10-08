<?php

namespace App\Modules\Reports\Services;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Prices\Models\BenchmarkValue;
use Carbon\CarbonImmutable;

/**
 * Gráfica del resumen del portafolio: rendimiento acumulado del portafolio
 * (TWR) contra su benchmark, en los rangos 1M, 3M, YTD, 1A y Todo.
 *
 * Parte de los índices base 100 de EvolutionSeries (leídos de
 * portfolio_snapshots, sin recalcular el historial) y los vuelve a llevar a
 * 0 % al inicio de cada rango:
 *
 *   rendimiento_t = índice_t ÷ índice_inicio − 1
 *
 * Ejemplo: si el índice vale 104 al inicio del rango y 106.08 hoy, el
 * rendimiento del rango es 106.08 ÷ 104 − 1 = 2 %. El "inicio" es el último
 * día con dato en o antes de la fecha de corte (si el portafolio es más
 * joven que el rango, su primer día).
 */
final class PerformanceChart
{
    /** Rangos en el orden de los botones. */
    public const RANGOS = ['1M' => '1M', '3M' => '3M', 'YTD' => 'YTD', '1A' => '1A', 'TODO' => 'Todo'];

    /**
     * @return array{rangos: array<string, array{portafolio: list<array{0:string, 1:float}>, benchmark: list<array{0:string, 1:float}>}>, benchmark: ?string}
     */
    public static function datos(Portfolio $portfolio, string $hoy): array
    {
        $serie = PortfolioSnapshot::where('portfolio_id', $portfolio->id)->where('fecha', '<=', $hoy)->orderBy('fecha')
            ->get(['fecha', 'valor', 'flujo_neto'])
            ->map(fn (PortfolioSnapshot $s) => ['fecha' => $s->fecha->toDateString(), 'valor' => $s->valor, 'flujo' => $s->flujo_neto])
            ->all();

        $benchmark = $portfolio->benchmark_id
            ? BenchmarkValue::where('benchmark_id', $portfolio->benchmark_id)->where('fecha', '<=', $hoy)->orderBy('fecha')
                ->get(['fecha', 'valor'])->mapWithKeys(fn (BenchmarkValue $b) => [$b->fecha->toDateString() => $b->valor])->all()
            : [];

        $evolucion = EvolutionSeries::calcular($serie, $benchmark);

        return [
            'rangos' => self::rangos($evolucion['indice'], $evolucion['benchmark'], $hoy),
            'benchmark' => $evolucion['benchmark'] === [] ? null : $portfolio->benchmark?->nombre,
        ];
    }

    /**
     * @param  list<array{0:string, 1:float}>  $indice  base 100
     * @param  list<array{0:string, 1:float}>  $benchmark  base 100, mismas fechas o subconjunto
     * @return array<string, array{portafolio: list<array{0:string, 1:float}>, benchmark: list<array{0:string, 1:float}>}>
     */
    public static function rangos(array $indice, array $benchmark, string $hoy): array
    {
        $resultado = [];
        foreach (array_keys(self::RANGOS) as $rango) {
            $corte = self::corte($rango, $hoy);
            $resultado[$rango] = [
                'portafolio' => self::recortar($indice, $corte),
                'benchmark' => self::recortar($benchmark, $corte),
            ];
        }

        return $resultado;
    }

    /** Fecha de inicio del rango (null = desde el primer dato). */
    public static function corte(string $rango, string $hoy): ?string
    {
        $dia = CarbonImmutable::parse($hoy);

        return match ($rango) {
            '1M' => $dia->subMonthNoOverflow()->toDateString(),
            '3M' => $dia->subMonthsNoOverflow(3)->toDateString(),
            'YTD' => $dia->startOfYear()->subDay()->toDateString(),
            '1A' => $dia->subYearNoOverflow()->toDateString(),
            'TODO' => null,
        };
    }

    /**
     * Puntos desde el inicio del rango, expresados como rendimiento contra ese inicio.
     *
     * @param  list<array{0:string, 1:float}>  $serie
     * @return list<array{0:string, 1:float}>
     */
    public static function recortar(array $serie, ?string $corte): array
    {
        if ($serie === []) {
            return [];
        }

        $inicio = 0;
        if ($corte !== null) {
            foreach ($serie as $i => [$fecha]) {
                if ($fecha > $corte) {
                    break;
                }
                $inicio = $i;
            }
        }

        $base = $serie[$inicio][1];

        return array_map(fn (array $p) => [$p[0], $base > 0 ? $p[1] / $base - 1 : 0.0], array_slice($serie, $inicio));
    }
}
