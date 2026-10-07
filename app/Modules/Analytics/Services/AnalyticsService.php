<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Prices\Models\BenchmarkValue;
use Carbon\CarbonImmutable;

/**
 * Reúne los 15 cálculos de CLAUDE.md para un portafolio y un periodo.
 * Las pantallas (Fase 6) solo leen este resultado.
 *
 * - Estado a la fecha "hasta" (posiciones, efectivo, G/P, asignación…): se
 *   calcula desde el libro de transacciones.
 * - Rendimiento del periodo, riesgo y benchmark: se calculan desde
 *   portfolio_snapshots (no se recalcula el historial).
 */
class AnalyticsService
{
    /**
     * Saldo a la fecha: estado valuado desde el libro y rendimiento total.
     * No lee snapshots, así que sirve para la vista de saldo actual.
     *
     * @return array{vacio: true}|array{vacio: false, hasta: string, primera: string, estado: array<string, mixed>, rendimiento_total: ?float}
     */
    public function saldo(Portfolio $portfolio, string $hasta): array
    {
        $transacciones = SnapshotService::transacciones($portfolio, $hasta);

        if ($transacciones === []) {
            return ['vacio' => true];
        }

        $estado = $this->estado($portfolio, $transacciones, $hasta);

        return [
            'vacio' => false,
            'hasta' => $hasta,
            'primera' => $transacciones[0]['fecha'],
            'estado' => $estado,
            'rendimiento_total' => PerformanceCalculator::rendimientoTotal($estado['gp_total'], $estado['aportaciones_netas']),
        ];
    }

    public function resumen(Portfolio $portfolio, string $desde, string $hasta): array
    {
        $saldo = $this->saldo($portfolio, $hasta);

        if ($saldo['vacio']) {
            return $saldo;
        }

        $estado = $saldo['estado'];
        $dias = (int) CarbonImmutable::parse($saldo['primera'])->diffInDays(CarbonImmutable::parse($hasta));
        $total = $saldo['rendimiento_total'];

        return [
            'vacio' => false,
            'desde' => $desde,
            'hasta' => $hasta,
            'estado' => $estado,
            'rendimiento_total' => $total,
            'rendimiento_anualizado' => PerformanceCalculator::anualizar($total, $dias),
            'asignacion' => array_combine(
                AllocationCalculator::DIMENSIONES,
                array_map(fn ($d) => AllocationCalculator::calcular($d, $estado['posiciones'], $estado['efectivo'], $portfolio->moneda_base), AllocationCalculator::DIMENSIONES),
            ),
            'top' => PerformanceCalculator::top($estado['posiciones']),
            'contribucion' => PerformanceCalculator::contribucion($estado['posiciones'], $estado['otros'], $estado['aportaciones_netas']),
            'periodo' => $this->periodo($portfolio, $desde, $hasta),
        ];
    }

    /** Estado valuado con los datos descriptivos de cada activo. */
    private function estado(Portfolio $portfolio, array $transacciones, string $hasta): array
    {
        $activos = array_values(array_unique(array_filter(array_column($transacciones, 'asset_id'))));
        $precios = PriceBook::cargar(
            $activos,
            array_values(array_diff(array_unique(array_column($transacciones, 'moneda')), [$portfolio->moneda_base])),
            $portfolio->moneda_base,
            $hasta,
        );

        $estado = PortfolioLedger::reproducir($transacciones, $portfolio->moneda_base)->valuar($hasta, $precios);

        $meta = Asset::whereIn('id', $activos)->get(['id', 'simbolo', 'nombre', 'tipo', 'sector', 'pais', 'exchange'])->keyBy('id');
        foreach ($estado['posiciones'] as $i => $p) {
            $a = $meta[$p['asset_id']];
            $estado['posiciones'][$i] += [
                'simbolo' => $a->simbolo, 'nombre' => $a->nombre, 'tipo' => $a->tipo,
                'sector' => $a->sector, 'pais' => $a->pais, 'exchange' => $a->exchange,
            ];
        }

        return $estado;
    }

    /** TWR, XIRR, benchmark y riesgo del periodo, a partir de los snapshots. */
    private function periodo(Portfolio $portfolio, string $desde, string $hasta): array
    {
        // Base: último snapshot en o antes de "desde"; si no hay, el portafolio arrancó en cero.
        $base = PortfolioSnapshot::where('portfolio_id', $portfolio->id)->where('fecha', '<=', $desde)->orderByDesc('fecha')->first();
        $serie = [[
            'fecha' => $base?->fecha->toDateString() ?? $desde,
            'valor' => $base?->valor ?? '0',
            'flujo' => '0',
        ]];

        PortfolioSnapshot::where('portfolio_id', $portfolio->id)
            ->where('fecha', '>', $serie[0]['fecha'])->where('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->get(['fecha', 'valor', 'flujo_neto'])
            ->each(function (PortfolioSnapshot $s) use (&$serie) {
                $serie[] = ['fecha' => $s->fecha->toDateString(), 'valor' => $s->valor, 'flujo' => $s->flujo_neto];
            });

        $diarios = ReturnCalculator::rendimientosDiarios($serie);
        $r = array_column($diarios, 'r');
        $fin = end($serie);

        // XIRR del periodo: valor inicial y aportaciones como salidas del inversionista, retiros y valor final como entradas.
        $flujos = [['fecha' => $serie[0]['fecha'], 'monto' => -(float) $serie[0]['valor']]];
        foreach (array_slice($serie, 1) as $s) {
            $flujos[] = ['fecha' => $s['fecha'], 'monto' => -(float) $s['flujo']];
        }
        $flujos[] = ['fecha' => $fin['fecha'], 'monto' => (float) $fin['valor']];

        // Benchmark: rendimiento del periodo y rendimientos diarios en los mismos días (para beta).
        $bench = $portfolio->benchmark_id
            ? BenchmarkValue::where('benchmark_id', $portfolio->benchmark_id)->where('fecha', '<=', $hasta)
                ->get(['fecha', 'valor'])->mapWithKeys(fn (BenchmarkValue $b) => [$b->fecha->toDateString() => $b->valor])->all()
            : [];
        $libroBench = new PriceBook(['b' => $bench]); // búsqueda binaria del último valor en o antes de cada fecha
        $rendimientoBench = function (string $de, string $a) use ($libroBench): ?float {
            $inicial = $libroBench->precio('b', $de);
            $final = $libroBench->precio('b', $a);

            return $inicial !== null && $final !== null && (float) $inicial > 0 ? (float) $final / (float) $inicial - 1 : null;
        };

        $rBench = [];
        $rPortafolioAlineado = [];
        $anterior = $serie[0]['fecha'];
        foreach ($diarios as $d) {
            $b = $rendimientoBench($anterior, $d['fecha']);
            if ($b !== null) {
                $rBench[] = $b;
                $rPortafolioAlineado[] = $d['r'];
            }
            $anterior = $d['fecha'];
        }

        $rf = (float) $portfolio->user->tasa_libre_riesgo;

        return [
            'twr' => $r === [] ? null : ReturnCalculator::twr($r),
            'xirr' => ReturnCalculator::xirr($flujos),
            // Si el portafolio empezó dentro del periodo, el benchmark se mide desde su primer día.
            'benchmark' => $rendimientoBench($base !== null ? $serie[0]['fecha'] : ($serie[1]['fecha'] ?? $desde), $fin['fecha']),
            'volatilidad' => RiskCalculator::volatilidad($r),
            'beta' => RiskCalculator::beta($rPortafolioAlineado, $rBench),
            'sharpe' => RiskCalculator::sharpe($r, $rf),
            'sortino' => RiskCalculator::sortino($r, $rf),
            'max_drawdown' => $r === [] ? null : RiskCalculator::maxDrawdown($r),
            'serie' => $serie,
        ];
    }
}
