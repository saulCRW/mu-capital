<?php

namespace App\Modules\Portfolios\Services;

use App\Modules\Analytics\Services\AllocationCalculator;
use App\Modules\Analytics\Services\PerformanceCalculator;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Reports\Services\PerformanceChart;

/**
 * Pestaña "Resumen" del portafolio (PROYECTO.md, pantalla 3): asignación por
 * clase, sector y moneda, top ganadores/perdedores y la gráfica contra el benchmark.
 * Los KPI salen de AnalyticsService::saldo().
 */
final class PortfolioOverview
{
    public const DIMENSIONES = ['clase' => 'Clase de activo', 'sector' => 'Sector', 'moneda' => 'Moneda'];

    /**
     * @param  array<string, mixed>  $estado  el "estado" de AnalyticsService::saldo()
     * @return array{asignacion: array<string, list<array{clave: string, valor: string, peso: float}>>, top: array{ganadores: list<array>, perdedores: list<array>}, grafica: array<string, mixed>}
     */
    public static function calcular(Portfolio $portfolio, array $estado, string $hoy): array
    {
        $asignacion = [];
        foreach (array_keys(self::DIMENSIONES) as $dimension) {
            $asignacion[$dimension] = AllocationCalculator::calcular($dimension, $estado['posiciones'], $estado['efectivo'], $portfolio->moneda_base);
        }

        return [
            'asignacion' => $asignacion,
            'top' => PerformanceCalculator::top($estado['posiciones']),
            'grafica' => PerformanceChart::datos($portfolio, $hoy),
        ];
    }
}
