<?php

namespace App\Modules\Portfolios\Services;

use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Analytics\Services\MonthlyReturns;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Reports\Services\PerformanceChart;

/**
 * Pestaña "Análisis" del portafolio (PROYECTO.md, pantalla 6): métricas de
 * riesgo del periodo elegido, heatmap de rendimiento mensual y contribución
 * de cada activo al rendimiento total.
 */
class PortfolioAnalysis
{
    public function __construct(private AnalyticsService $analytics) {}

    /**
     * @param  string  $periodo  una clave de PerformanceChart::RANGOS
     * @return array{periodo: string, desde: string, hasta: string, metricas: array<string, mixed>, heatmap: array<int, array{meses: array<int, float>, anio: float}>, contribucion: list<array<string, mixed>>}
     */
    public function calcular(Portfolio $portfolio, string $primera, string $periodo, string $hoy): array
    {
        $desde = max(PerformanceChart::corte($periodo, $hoy) ?? $primera, $primera);
        $resumen = $this->analytics->resumen($portfolio, $desde, $hoy);

        $serie = PortfolioSnapshot::where('portfolio_id', $portfolio->id)->where('fecha', '<=', $hoy)->orderBy('fecha')
            ->get(['fecha', 'valor', 'flujo_neto'])
            ->map(fn (PortfolioSnapshot $s) => ['fecha' => $s->fecha->toDateString(), 'valor' => $s->valor, 'flujo' => $s->flujo_neto])
            ->all();

        $simbolos = array_column($resumen['estado']['posiciones'], 'simbolo', 'asset_id');

        return [
            'periodo' => $periodo,
            'desde' => $desde,
            'hasta' => $hoy,
            'metricas' => $resumen['periodo'],
            'heatmap' => MonthlyReturns::calcular($serie),
            'contribucion' => array_map(fn ($c) => $c + ['simbolo' => $c['asset_id'] === null ? null : ($simbolos[$c['asset_id']] ?? null)], $resumen['contribucion']),
        ];
    }
}
