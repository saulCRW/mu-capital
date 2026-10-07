<?php

namespace App\Modules\Reports\Services;

use App\Modules\Analytics\Services\PriceBook;
use App\Modules\Analytics\Services\ReturnCalculator;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Dashboard del usuario: sus portafolios, con su valor y rendimiento del año.
 *
 * Los totales se expresan en una moneda de referencia (la moneda base más
 * común); cada portafolio en otra moneda se convierte al tipo de cambio de
 * cada día. Si falta un tipo de cambio, ese portafolio queda fuera del total.
 */
class DashboardService
{
    /** Días antes del 1 de enero en que se busca la valuación base (cubre fines de semana y feriados). */
    private const MARGEN_BASE = 15;

    /**
     * @return array{vacio: true}|array{vacio: false, hoy: string, anio: int, moneda: string,
     *               portafolios: list<array<string, mixed>>, total: ?array<string, mixed>, evolucion: array<string, mixed>, sin_conversion: list<string>}
     */
    public function resumen(User $user, string $hoy): array
    {
        $portafolios = Portfolio::with('benchmark:id,nombre')->where('user_id', $user->id)->get();

        if ($portafolios->isEmpty()) {
            return ['vacio' => true];
        }

        $hoyC = CarbonImmutable::parse($hoy);
        $primerDia = $hoyC->startOfYear()->toDateString();
        // Moneda de referencia: la más común; en empate, la primera en orden alfabético.
        $moneda = $portafolios->countBy('moneda_base')->sortKeys()->sortDesc()->keys()->first();

        $snapshots = PortfolioSnapshot::whereIn('portfolio_id', $portafolios->pluck('id'))
            ->where('fecha', '>=', $hoyC->startOfYear()->subDays(self::MARGEN_BASE)->toDateString())
            ->where('fecha', '<=', $hoy)
            ->orderBy('fecha')
            ->get(['portfolio_id', 'fecha', 'valor', 'flujo_neto'])
            ->groupBy('portfolio_id');

        $ultimosMovimientos = Transaction::whereIn('portfolio_id', $portafolios->pluck('id'))
            ->groupBy('portfolio_id')
            ->selectRaw('portfolio_id, MAX(fecha) AS ultima')
            ->pluck('ultima', 'portfolio_id');

        $benchmarks = $this->benchmarks($portafolios->pluck('benchmark_id')->filter()->unique()->values()->all(), $hoyC, $hoy);
        $otrasMonedas = $portafolios->pluck('moneda_base')->unique()->reject(fn ($m) => $m === $moneda)->values()->all();
        $tasas = PriceBook::cargar([], $otrasMonedas, $moneda, $hoy);

        $filas = [];
        $agregado = [];
        $sinConversion = [];
        foreach ($portafolios as $p) {
            $serie = $this->serieDelAnio($snapshots->get($p->id, collect()), $primerDia);
            $anio = $serie === [] ? null : QuarterlyAggregator::agregar($serie, $hoyC->year, $hoyC->quarter)['anio'];
            $valor = $serie === [] ? null : end($serie)['valor'];
            $benchmark = $anio && $p->benchmark_id && isset($benchmarks[$p->benchmark_id])
                ? ReturnCalculator::rendimientoSerie($benchmarks[$p->benchmark_id], $anio['fecha_base'], $hoy) : null;

            // Conversión día por día a la moneda de referencia para el total del usuario.
            $convertible = $serie !== [];
            $valorReferencia = null;
            foreach ($serie as $punto) {
                $tasa = $tasas->tasa($p->moneda_base, $moneda, $punto['fecha']);
                if ($tasa === null) {
                    $convertible = false;
                    break;
                }
                $agregado[$punto['fecha']]['valor'][] = bcmul($punto['valor'], $tasa, 6);
                $agregado[$punto['fecha']]['flujo'][] = bcmul($punto['flujo'], $tasa, 6);
                $valorReferencia = bcmul($punto['valor'], $tasa, 6);
            }
            if ($serie !== [] && ! $convertible) {
                $sinConversion[] = $p->moneda_base;
            }

            $filas[] = [
                'portfolio' => $p,
                'valor' => $valor,
                'valor_referencia' => $convertible ? $valorReferencia : null,
                'twr' => $anio['twr'] ?? null,
                'benchmark' => $benchmark,
                'ganancia' => $anio['ganancia'] ?? null,
                'ultimo_movimiento' => $ultimosMovimientos[$p->id] ?? null,
            ];
        }

        usort($filas, fn ($a, $b) => (float) ($b['valor_referencia'] ?? -1) <=> (float) ($a['valor_referencia'] ?? -1));

        $serieTotal = $this->serieAgregada($agregado);
        $total = $serieTotal === [] ? null : QuarterlyAggregator::agregar($serieTotal, $hoyC->year, $hoyC->quarter)['anio'];

        return [
            'vacio' => false,
            'hoy' => $hoy,
            'anio' => $hoyC->year,
            'moneda' => $moneda,
            'portafolios' => $filas,
            'total' => $total,
            'evolucion' => EvolutionSeries::calcular($serieTotal),
            'sin_conversion' => array_values(array_unique($sinConversion)),
            'varias_monedas' => $otrasMonedas !== [],
        ];
    }

    /**
     * Snapshots del año precedidos por el último anterior al 1 de enero (la base).
     *
     * @return list<array{fecha:string, valor:string, flujo:string}>
     */
    /** @param  Collection<int, PortfolioSnapshot>  $snapshots */
    private function serieDelAnio($snapshots, string $primerDia): array
    {
        $base = null;
        $serie = [];
        foreach ($snapshots as $s) {
            $punto = ['fecha' => $s->fecha->toDateString(), 'valor' => $s->valor, 'flujo' => $s->flujo_neto];
            if ($punto['fecha'] < $primerDia) {
                $base = $punto;
            } else {
                $serie[] = $punto;
            }
        }

        return $base ? [$base, ...$serie] : $serie;
    }

    /**
     * Suma por fecha de los portafolios convertidos. Solo cuenta las fechas en que
     * todos los portafolios que ya existían tienen valuación, para no mezclar días incompletos.
     *
     * @param  array<string, array{valor: list<string>, flujo: list<string>}>  $agregado
     * @return list<array{fecha:string, valor:string, flujo:string}>
     */
    private function serieAgregada(array $agregado): array
    {
        ksort($agregado);
        $serie = [];
        $maximo = 0;
        foreach ($agregado as $fecha => $d) {
            if (count($d['valor']) < $maximo) {
                continue;
            }
            $maximo = count($d['valor']);
            $serie[] = [
                'fecha' => $fecha,
                'valor' => array_reduce($d['valor'], fn ($s, $v) => bcadd($s, $v, 6), '0'),
                'flujo' => array_reduce($d['flujo'], fn ($s, $v) => bcadd($s, $v, 6), '0'),
            ];
        }

        return $serie;
    }

    /** @return array<int, array<string, string>> benchmark_id => [fecha => valor] */
    private function benchmarks(array $ids, CarbonImmutable $hoy, string $hasta): array
    {
        if ($ids === []) {
            return [];
        }

        return BenchmarkValue::whereIn('benchmark_id', $ids)
            ->where('fecha', '>=', $hoy->startOfYear()->subDays(self::MARGEN_BASE)->toDateString())
            ->where('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->get(['benchmark_id', 'fecha', 'valor'])
            ->groupBy('benchmark_id')
            ->map(fn ($valores) => $valores->mapWithKeys(fn (BenchmarkValue $b) => [$b->fecha->toDateString() => $b->valor])->all())
            ->all();
    }
}
