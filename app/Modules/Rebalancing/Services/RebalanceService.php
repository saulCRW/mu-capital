<?php

namespace App\Modules\Rebalancing\Services;

use App\Modules\Analytics\Services\AllocationCalculator;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\TargetAllocation;
use Illuminate\Support\Facades\DB;

class RebalanceService
{
    public function __construct(private AnalyticsService $analytics) {}

    /**
     * Asignación actual (valuada a hoy) contra la objetivo en una dimensión, y las operaciones sugeridas.
     *
     * @return array{vacio: bool, dimension: string, comparacion: list<array<string, mixed>>, operaciones: list<array<string, mixed>>,
     *               sin_activos: list<array<string, string>>, claves: list<string>, objetivos: array<string, string>, con_objetivo: list<string>, total: string}
     */
    public function analizar(Portfolio $portfolio, string $dimension, string $hoy): array
    {
        $objetivos = $portfolio->targetAllocations()->where('dimension', $dimension)->pluck('porcentaje_objetivo', 'clave')->all();
        $base = [
            'dimension' => $dimension,
            'objetivos' => $objetivos,
            'con_objetivo' => $portfolio->targetAllocations()->distinct()->pluck('dimension')->all(),
        ];

        $saldo = $this->analytics->saldo($portfolio, $hoy);
        if ($saldo['vacio']) {
            return $base + ['vacio' => true, 'comparacion' => [], 'operaciones' => [], 'sin_activos' => [], 'claves' => array_keys($objetivos), 'total' => '0'];
        }

        $estado = $saldo['estado'];
        $actual = AllocationCalculator::calcular($dimension, $estado['posiciones'], $estado['efectivo'], $portfolio->moneda_base);
        $comparacion = RebalanceCalculator::comparar($actual, array_map(fn ($p) => (float) $p / 100, $objetivos), $estado['valor_total']);
        $sugerencia = RebalanceCalculator::operaciones($comparacion, $estado['posiciones'], $dimension, self::grupoEfectivo($dimension, $portfolio));

        // Grupos que se pueden capturar: los que hay, los que ya tienen objetivo y, en clase, todas las clases.
        $claves = array_values(array_unique([
            ...array_column($actual, 'clave'),
            ...array_keys($objetivos),
            ...($dimension === TargetAllocation::DIMENSION_CLASE ? Asset::TIPOS : []),
        ]));

        return $base + [
            'vacio' => false,
            'comparacion' => $comparacion,
            'operaciones' => $sugerencia['operaciones'],
            'sin_activos' => $sugerencia['sin_activos'],
            'claves' => $claves,
            'total' => $estado['valor_total'],
        ];
    }

    /**
     * Alerta del dashboard: cuántos grupos están fuera de la banda de tolerancia, sumando
     * todas las dimensiones que tienen asignación objetivo. null si no hay objetivos.
     *
     * @param  array<string, mixed>|null  $saldo  el de AnalyticsService::saldo(), si ya se calculó
     */
    public function fueraDeBanda(Portfolio $portfolio, string $hoy, ?array $saldo = null): ?int
    {
        $objetivos = $portfolio->targetAllocations()->get(['dimension', 'clave', 'porcentaje_objetivo'])->groupBy('dimension');
        if ($objetivos->isEmpty()) {
            return null;
        }

        $saldo ??= $this->analytics->saldo($portfolio, $hoy);
        if ($saldo['vacio']) {
            return 0;
        }

        $estado = $saldo['estado'];
        $fuera = 0;
        foreach ($objetivos as $dimension => $filas) {
            $actual = AllocationCalculator::calcular($dimension, $estado['posiciones'], $estado['efectivo'], $portfolio->moneda_base);
            $metas = $filas->mapWithKeys(fn (TargetAllocation $t) => [$t->clave => (float) $t->porcentaje_objetivo / 100])->all();
            $fuera += collect(RebalanceCalculator::comparar($actual, $metas, $estado['valor_total']))->where('fuera', true)->count();
        }

        return $fuera;
    }

    /**
     * Grupo que hace de efectivo (igual que en AllocationCalculator). Por moneda, el efectivo
     * está en la moneda base: ese grupo se ajusta con lo que sobre o falte de las demás operaciones.
     */
    private static function grupoEfectivo(string $dimension, Portfolio $portfolio): string
    {
        return match ($dimension) {
            'clase' => 'efectivo',
            'moneda' => $portfolio->moneda_base,
            default => 'Efectivo',
        };
    }

    /**
     * Reemplaza la asignación objetivo de una dimensión. Los grupos en 0 % no se guardan.
     *
     * @param  array<string, string|float|null>  $objetivos  clave => porcentaje (50 = 50 %)
     */
    public function guardar(Portfolio $portfolio, string $dimension, array $objetivos): void
    {
        DB::transaction(function () use ($portfolio, $dimension, $objetivos) {
            $portfolio->targetAllocations()->where('dimension', $dimension)->delete();

            foreach ($objetivos as $clave => $porcentaje) {
                if ((float) $porcentaje > 0) {
                    $portfolio->targetAllocations()->create([
                        'user_id' => $portfolio->user_id,
                        'dimension' => $dimension,
                        'clave' => $clave,
                        'porcentaje_objetivo' => $porcentaje,
                    ]);
                }
            }
        });
    }
}
