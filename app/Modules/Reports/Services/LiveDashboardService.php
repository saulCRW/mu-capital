<?php

namespace App\Modules\Reports\Services;

use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Prices\Services\QuoteService;
use App\Modules\Users\Models\User;

/**
 * Inicio en vivo: revalúa cada portafolio con las cotizaciones del momento y
 * ajusta lo que el dashboard calculó con los cierres guardados.
 *
 * El ajuste es el movimiento del mercado desde la última valuación guardada
 * (valor en vivo − valor con los últimos cierres): se suma a la ganancia del
 * año y encadena un día más al TWR. Los depósitos y retiros no cambian. No se
 * guarda nada; los cierres los guarda la tarea programada.
 */
class LiveDashboardService
{
    public function __construct(
        private DashboardService $dashboard,
        private AnalyticsService $analytics,
        private QuoteService $cotizaciones,
    ) {}

    /**
     * @return array{moneda: ?string, total: ?array{valor: float, ganancia: ?float, twr: ?float},
     *               portafolios: array<int, array{valor: float, peso: ?float, ganancia: ?float, twr: ?float, diferencia: ?float}>, hora: ?int, generado: string}
     */
    public function calcular(User $user, string $hoy): array
    {
        $resumen = $this->dashboard->resumen($user, $hoy);
        if ($resumen['vacio']) {
            return ['moneda' => null, 'total' => null, 'portafolios' => [], 'hora' => null, 'generado' => now()->toIso8601String()];
        }

        $saldos = [];
        foreach ($resumen['portafolios'] as $fila) {
            $saldos[$fila['portfolio']->id] = $this->analytics->saldo($fila['portfolio'], $hoy);
        }
        $ids = collect($saldos)->reject(fn ($s) => $s['vacio'])->flatMap(fn ($s) => array_column($s['estado']['posiciones'], 'asset_id'))->unique()->values();
        $cotizacion = $this->cotizaciones->cotizar(Asset::whereIn('id', $ids)->get());

        $moneda = $resumen['moneda'];
        $hora = null;
        $vivos = [];
        foreach ($resumen['portafolios'] as $fila) {
            $p = $fila['portfolio'];
            $saldo = $saldos[$p->id];
            if ($saldo['vacio'] || $fila['valor'] === null) {
                continue;
            }

            $guardado = (float) $saldo['estado']['valor_total'];
            $vivo = (float) $saldo['estado']['efectivo'];
            foreach ($saldo['estado']['posiciones'] as $pos) {
                $c = $cotizacion['activos'][$pos['asset_id']] ?? null;
                $tasa = QuoteService::tasa($pos['moneda'], $p->moneda_base, $cotizacion['fx']) ?? ($pos['tasa'] === null ? null : (float) $pos['tasa']);
                if ($c !== null && $tasa !== null) {
                    $vivo += (float) $pos['cantidad'] * $c['precio'] * $tasa;
                    $hora = max($hora ?? 0, (int) $c['hora']);
                } else {
                    $vivo += (float) $pos['valor_base'];
                }
            }

            // Tipo de cambio a la moneda de referencia: el del último día guardado y el del momento
            // (si no lo hay, el guardado). Así la ganancia total incluye el efecto cambiario del día.
            $guardadaAReferencia = $fila['valor_referencia'] !== null && (float) $fila['valor'] > 0
                ? (float) $fila['valor_referencia'] / (float) $fila['valor'] : null;
            $aReferencia = $guardadaAReferencia === null ? null : (QuoteService::tasa($p->moneda_base, $moneda, $cotizacion['fx']) ?? $guardadaAReferencia);

            $twr = $fila['twr'] !== null && $guardado > 0 ? (1 + $fila['twr']) * ($vivo / $guardado) - 1 : null;
            $vivos[$p->id] = [
                'valor' => $vivo,
                'guardado' => $guardado,
                'a_referencia' => $aReferencia,
                'guardada_a_referencia' => $guardadaAReferencia,
                'ganancia' => $fila['ganancia'] === null ? null : (float) $fila['ganancia'] + $vivo - $guardado,
                'twr' => $twr,
                'diferencia' => $twr !== null && $fila['benchmark'] !== null ? $twr - $fila['benchmark'] : null,
            ];
        }

        $convertibles = array_filter($vivos, fn ($v) => $v['a_referencia'] !== null);
        $valorTotal = array_sum(array_map(fn ($v) => $v['valor'] * $v['a_referencia'], $convertibles));
        $guardadoTotal = array_sum(array_map(fn ($v) => $v['guardado'] * $v['guardada_a_referencia'], $convertibles));
        $total = $resumen['total'];

        return [
            'moneda' => $moneda,
            'total' => $total === null || $convertibles === [] ? null : [
                'valor' => $valorTotal,
                'ganancia' => (float) $total['ganancia'] + $valorTotal - $guardadoTotal,
                'twr' => $total['twr'] !== null && $guardadoTotal > 0 ? (1 + $total['twr']) * ($valorTotal / $guardadoTotal) - 1 : null,
            ],
            'portafolios' => array_map(fn ($v) => [
                'valor' => $v['valor'],
                'peso' => $v['a_referencia'] !== null && $valorTotal > 0 ? $v['valor'] * $v['a_referencia'] / $valorTotal : null,
                'ganancia' => $v['ganancia'],
                'twr' => $v['twr'],
                'diferencia' => $v['diferencia'],
            ], $vivos),
            'hora' => $hora,
            'generado' => now()->toIso8601String(),
        ];
    }
}
