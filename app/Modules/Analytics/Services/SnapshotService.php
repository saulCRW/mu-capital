<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Models\PortfolioSnapshot;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Transactions\Services\LedgerValidator;
use Carbon\CarbonImmutable;

/**
 * Genera portfolio_snapshots: una fila por día hábil (lunes a viernes) con
 * el valor total, el efectivo y el flujo neto externo (depósitos − retiros)
 * de ese día. Las gráficas y el TWR leen de aquí sin recalcular el historial.
 *
 * Al editar o borrar una transacción se recalcula desde su fecha.
 */
class SnapshotService
{
    /** @return int filas generadas */
    public function recalcular(Portfolio $portfolio, ?string $desde = null, ?string $hasta = null): int
    {
        $hasta ??= CarbonImmutable::today()->toDateString();
        $transacciones = self::transacciones($portfolio);
        $snapshots = PortfolioSnapshot::where('portfolio_id', $portfolio->id);

        if ($transacciones === []) {
            $snapshots->delete();

            return 0;
        }

        $primera = $transacciones[0]['fecha'];

        // Se reconstruye desde el día siguiente al último snapshot anterior a $desde,
        // para no perder transacciones de fin de semana entre ambos.
        $previo = $desde !== null
            ? PortfolioSnapshot::where('portfolio_id', $portfolio->id)->where('fecha', '<', $desde)->where('fecha', '>=', $primera)->max('fecha')
            : null;
        $previo = $previo !== null ? CarbonImmutable::parse($previo)->toDateString() : null;
        $inicio = $previo !== null ? CarbonImmutable::parse($previo)->addDay()->toDateString() : $primera;

        (clone $snapshots)->where(fn ($q) => $q->where('fecha', '>=', $inicio)->orWhere('fecha', '<', $primera))->delete();

        $libro = new PortfolioLedger($portfolio->moneda_base);
        $i = 0;
        $n = count($transacciones);
        while ($previo !== null && $i < $n && $transacciones[$i]['fecha'] <= $previo) {
            $libro->aplicar($transacciones[$i++]);
        }

        $precios = PriceBook::cargar(
            array_values(array_unique(array_filter(array_column($transacciones, 'asset_id')))),
            array_values(array_diff(array_unique(array_column($transacciones, 'moneda')), [$portfolio->moneda_base])),
            $portfolio->moneda_base,
            $hasta,
        );

        $filas = [];
        $ahora = now();
        for ($dia = CarbonImmutable::parse($inicio); $dia->toDateString() <= $hasta; $dia = $dia->addDay()) {
            $fecha = $dia->toDateString();
            $flujo = '0';
            while ($i < $n && $transacciones[$i]['fecha'] <= $fecha) {
                $flujo = bcadd($flujo, self::flujoExterno($transacciones[$i]), 6);
                $libro->aplicar($transacciones[$i++]);
            }

            if ($dia->isWeekend()) {
                continue; // lo ocurrido en fin de semana se refleja el lunes
            }

            $filas[] = [
                'user_id' => $portfolio->user_id,
                'portfolio_id' => $portfolio->id,
                'fecha' => $fecha,
                'valor' => $libro->valorTotal($fecha, $precios),
                'efectivo' => $libro->efectivo(),
                'flujo_neto' => $flujo,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        foreach (array_chunk($filas, 500) as $bloque) {
            PortfolioSnapshot::insert($bloque);
        }

        return count($filas);
    }

    /**
     * Transacciones del portafolio como arreglos, en orden cronológico.
     *
     * @return list<array<string, mixed>>
     */
    public static function transacciones(Portfolio $portfolio, ?string $hasta = null): array
    {
        $filas = $portfolio->transactions()
            ->when($hasta, fn ($q) => $q->where('fecha', '<=', $hasta))
            ->get()
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'tipo' => $t->tipo,
                'fecha' => $t->fecha->toDateString(),
                'asset_id' => $t->asset_id,
                'cantidad' => $t->cantidad,
                'precio' => $t->precio,
                'comision' => $t->comision,
                'moneda' => $t->moneda,
                'tipo_cambio' => $t->tipo_cambio,
            ])
            ->all();

        return LedgerValidator::ordenar($filas);
    }

    /** Depósito suma, retiro resta; lo demás no es flujo externo (es rendimiento). */
    private static function flujoExterno(array $t): string
    {
        return match ($t['tipo']) {
            Transaction::DEPOSITO => bcmul($t['cantidad'], $t['tipo_cambio'], 6),
            Transaction::RETIRO => bcmul(bcsub('0', $t['cantidad'], 6), $t['tipo_cambio'], 6),
            default => '0',
        };
    }
}
