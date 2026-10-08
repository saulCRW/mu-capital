<?php

namespace App\Modules\Portfolios\Services;

use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Models\Price;
use App\Modules\Reports\Services\PerformanceChart;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Services\DividendCalendar;
use Illuminate\Support\Collection;

/**
 * Detalle de un activo dentro de un portafolio: su posición (la misma fila que
 * Holdings, de PortfolioLedger::valuar), sus transacciones, la gráfica de cierres
 * con compras, ventas y dividendos marcados, y sus dividendos esperados.
 * No hay fórmulas nuevas: peso % = valor de la posición ÷ valor total del portafolio.
 */
final class AssetDetail
{
    /** Tipos de transacción que se marcan en la gráfica. */
    public const MARCADOS = [Transaction::COMPRA, Transaction::VENTA, Transaction::DIVIDENDO];

    public function __construct(private DividendCalendar $dividendos) {}

    /**
     * Devuelve null si el activo nunca se operó en el portafolio.
     *
     * @param  array<string, mixed>  $estado  el "estado" de AnalyticsService::saldo()
     * @return array{posicion: array<string, mixed>, peso: ?float, transacciones: Collection<int, Transaction>, grafica: array{cierres: list<array{0: string, 1: float}>, marcadores: list<array{fecha: string, tipo: string, texto: string}>, costo: ?float, cortes: array<string, ?string>}, dividendos: list<array<string, mixed>>}|null
     */
    public function calcular(Portfolio $portfolio, Asset $asset, array $estado, string $hoy): ?array
    {
        $posicion = collect($estado['posiciones'])->firstWhere('asset_id', $asset->id);
        if ($posicion === null) {
            return null;
        }

        $transacciones = Transaction::where('portfolio_id', $portfolio->id)->where('asset_id', $asset->id)
            ->orderByDesc('fecha')->orderByDesc('id')->get();
        $valorTotal = (string) $estado['valor_total'];

        return [
            'posicion' => $posicion,
            'peso' => bccomp($valorTotal, '0', 6) > 0 ? (float) bcdiv((string) $posicion['valor_base'], $valorTotal, 10) : null,
            'transacciones' => $transacciones,
            'grafica' => [
                // "Todo" empieza en la primera operación del activo en este portafolio.
                'cierres' => Price::where('asset_id', $asset->id)
                    ->whereBetween('fecha', [$transacciones->last()->fecha->toDateString(), $hoy])
                    ->orderBy('fecha')->get(['fecha', 'cierre'])
                    ->map(fn (Price $p) => [$p->fecha->toDateString(), (float) $p->cierre])->all(),
                'marcadores' => $transacciones->reverse()
                    ->filter(fn (Transaction $t) => in_array($t->tipo, self::MARCADOS, true))
                    ->map(fn (Transaction $t) => ['fecha' => $t->fecha->toDateString(), 'tipo' => $t->tipo, 'texto' => self::texto($t)])
                    ->values()->all(),
                'costo' => $posicion['costo_promedio_local'] === null ? null : (float) $posicion['costo_promedio_local'],
                'cortes' => array_map(fn (string $rango) => PerformanceChart::corte($rango, $hoy), array_combine(array_keys(PerformanceChart::RANGOS), array_keys(PerformanceChart::RANGOS))),
            ],
            'dividendos' => $this->dividendos->delActivo($portfolio, $asset->id, $hoy),
        ];
    }

    /** Descripción de la operación para la leyenda de la gráfica, ej. "Compra 100 a 50.00 MXN". */
    private static function texto(Transaction $t): string
    {
        $cantidad = rtrim(rtrim(number_format((float) $t->cantidad, 6, '.', ','), '0'), '.');
        $dinero = fn ($v) => number_format((float) $v, 2, '.', ',');

        return match ($t->tipo) {
            Transaction::COMPRA => "Compra {$cantidad} a {$dinero($t->precio)} {$t->moneda}",
            Transaction::VENTA => "Venta {$cantidad} a {$dinero($t->precio)} {$t->moneda}",
            Transaction::DIVIDENDO => "Dividendo {$dinero($t->cantidad)} {$t->moneda}",
        };
    }
}
