<?php

namespace App\Modules\Reports\Services;

use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Analytics\Services\ReturnCalculator;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Prices\Models\BenchmarkValue;
use App\Modules\Transactions\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Datos del reporte trimestral de un portafolio. Un trimestre en curso se
 * reporta hasta hoy.
 */
class ReportService
{
    public function __construct(private AnalyticsService $analytics) {}

    /**
     * @return array{anio:int, trimestre:int, desde:string, hasta:string, cerrado:bool,
     *               resumen:array<string, mixed>, anual:array<string, mixed>, movimientos:Collection<int, Transaction>}
     */
    public function trimestral(Portfolio $portfolio, int $anio, int $trimestre): array
    {
        $inicio = CarbonImmutable::create($anio, $trimestre * 3 - 2, 1);
        $fin = $inicio->endOfQuarter()->startOfDay();
        $hasta = $fin->min(CarbonImmutable::today())->toDateString();

        // "desde" es el cierre del trimestre anterior: AnalyticsService toma de ahí la base del periodo.
        $resumen = $this->analytics->resumen($portfolio, $inicio->subDay()->toDateString(), $hasta);

        $anual = QuarterlyAggregator::agregar($this->serieDelAnio($portfolio, $anio, $hasta), $anio, $trimestre);
        $benchmark = $this->benchmark($portfolio, $hasta);
        foreach ($anual['trimestres'] as $i => $q) {
            $anual['trimestres'][$i]['benchmark'] = $benchmark === [] ? null
                : ReturnCalculator::rendimientoSerie($benchmark, $q['fecha_base'], min($q['hasta'], $hasta));
        }
        if ($anual['anio'] !== null) {
            $anual['anio']['benchmark'] = $benchmark === [] ? null
                : ReturnCalculator::rendimientoSerie($benchmark, $anual['anio']['fecha_base'], $hasta);
        }

        return [
            'anio' => $anio,
            'trimestre' => $trimestre,
            'desde' => $inicio->toDateString(),
            'hasta' => $hasta,
            'cerrado' => $hasta === $fin->toDateString(),
            'resumen' => $resumen,
            'anual' => $anual,
            'movimientos' => $portfolio->transactions()->with('asset:id,simbolo')
                ->whereBetween('fecha', [$inicio->toDateString(), $hasta])
                ->orderBy('fecha')->orderBy('id')->get(),
        ];
    }

    /**
     * Snapshots del año hasta la fecha, precedidos por el último del año anterior (la base).
     *
     * @return list<array{fecha:string, valor:string, flujo:string}>
     */
    private function serieDelAnio(Portfolio $portfolio, int $anio, string $hasta): array
    {
        $primerDia = CarbonImmutable::create($anio, 1, 1)->toDateString();
        $base = PortfolioSnapshot::where('portfolio_id', $portfolio->id)->where('fecha', '<', $primerDia)->orderByDesc('fecha')->first();

        return collect($base ? [$base] : [])
            ->concat(PortfolioSnapshot::where('portfolio_id', $portfolio->id)
                ->where('fecha', '>=', $primerDia)->where('fecha', '<=', $hasta)
                ->orderBy('fecha')->get())
            ->map(fn (PortfolioSnapshot $s) => ['fecha' => $s->fecha->toDateString(), 'valor' => $s->valor, 'flujo' => $s->flujo_neto])
            ->all();
    }

    /** @return array<string, string> fecha => valor */
    private function benchmark(Portfolio $portfolio, string $hasta): array
    {
        if (! $portfolio->benchmark_id) {
            return [];
        }

        return BenchmarkValue::where('benchmark_id', $portfolio->benchmark_id)->where('fecha', '<=', $hasta)
            ->orderBy('fecha')->get(['fecha', 'valor'])
            ->mapWithKeys(fn (BenchmarkValue $b) => [$b->fecha->toDateString() => $b->valor])->all();
    }
}
