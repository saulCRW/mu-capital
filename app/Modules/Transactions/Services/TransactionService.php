<?php

namespace App\Modules\Transactions\Services;

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alta, edición y baja de transacciones.
 *
 * Antes de guardar cualquier cambio se reproduce TODO el libro del portafolio
 * con el cambio aplicado (LedgerValidator). Si en alguna fecha se vende más de
 * lo que se tiene o el efectivo queda negativo, el cambio se rechaza.
 * Después de guardar, los snapshots se recalculan desde la fecha afectada.
 */
class TransactionService
{
    public function __construct(private LedgerValidator $ledger, private SnapshotService $snapshots)
    {
    }

    /** @param  array{tipo?:string, asset_id?:int, desde?:string, hasta?:string}  $filtros */
    public function filtrar(Portfolio $portfolio, array $filtros): Builder
    {
        return $portfolio->transactions()
            ->getQuery()
            ->with('asset:id,simbolo')
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $v) => $q->where('tipo', $v))
            ->when($filtros['asset_id'] ?? null, fn (Builder $q, $v) => $q->where('asset_id', $v))
            ->when($filtros['desde'] ?? null, fn (Builder $q, $v) => $q->where('fecha', '>=', $v))
            ->when($filtros['hasta'] ?? null, fn (Builder $q, $v) => $q->where('fecha', '<=', $v))
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }

    public function create(Portfolio $portfolio, array $data, User $autor): Transaction
    {
        return DB::transaction(function () use ($portfolio, $data, $autor) {
            $this->bloquear($portfolio);
            $datos = $this->normalizar($portfolio, $data);
            $this->validarLibro($portfolio, $datos, excluirId: null);

            $transaction = Transaction::create($datos + [
                'organization_id' => $portfolio->organization_id,
                'portfolio_id' => $portfolio->id,
                'created_by' => $autor->id,
            ]);

            $this->snapshots->recalcular($portfolio, $datos['fecha']);

            return $transaction;
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $portfolio = $transaction->portfolio;
            $this->bloquear($portfolio);
            $datos = $this->normalizar($portfolio, $data);
            $this->validarLibro($portfolio, $datos + ['id' => $transaction->id], excluirId: $transaction->id);

            $fechaAnterior = $transaction->fecha->toDateString();
            $transaction->update($datos);

            // Si cambió la fecha, el historial se afecta desde la más antigua de las dos.
            $this->snapshots->recalcular($portfolio, min($fechaAnterior, $datos['fecha']));

            return $transaction;
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $portfolio = $transaction->portfolio;
            $this->bloquear($portfolio);
            $this->validarLibro($portfolio, null, excluirId: $transaction->id);

            $transaction->delete();

            $this->snapshots->recalcular($portfolio, $transaction->fecha->toDateString());
        });
    }

    /**
     * Aplica la convención del ledger según el tipo:
     *   COMPRA/VENTA: cantidad = títulos, precio = unitario, moneda = la del activo.
     *   SPLIT: cantidad = factor, precio = 0, moneda = la del activo.
     *   DEPOSITO/RETIRO/DIVIDENDO/INTERES/COMISION: cantidad = monto, precio = 1, sin comisión.
     *   tipo_cambio = 1 si la moneda es la base; si no, el capturado o el último de fx_rates.
     */
    private function normalizar(Portfolio $portfolio, array $d): array
    {
        $tipo = $d['tipo'];
        $asset = in_array($tipo, Transaction::SIN_ACTIVO, true) || empty($d['asset_id'])
            ? null
            : Asset::findOrFail($d['asset_id']);

        $moneda = in_array($tipo, [Transaction::COMPRA, Transaction::VENTA, Transaction::SPLIT], true)
            ? $asset->moneda
            : (($d['moneda'] ?? null) ?: ($asset?->moneda ?? $portfolio->moneda_base));

        if ($tipo === Transaction::SPLIT && bccomp($d['cantidad'], '1', 6) === 0) {
            throw ValidationException::withMessages(['cantidad' => 'El factor del split no puede ser 1.']);
        }

        return [
            'tipo' => $tipo,
            'fecha' => $d['fecha'],
            'asset_id' => $asset?->id,
            'cantidad' => $d['cantidad'],
            'precio' => match (true) {
                in_array($tipo, Transaction::OPERACIONES, true) => $d['precio'],
                $tipo === Transaction::SPLIT => '0',
                default => '1',
            },
            'comision' => in_array($tipo, Transaction::OPERACIONES, true) ? (($d['comision'] ?? null) ?: '0') : '0',
            'moneda' => $moneda,
            'tipo_cambio' => $this->tipoCambio($moneda, $portfolio->moneda_base, $d['fecha'], $d['tipo_cambio'] ?? null),
            'nota' => $d['nota'] ?? null,
        ];
    }

    private function tipoCambio(string $moneda, string $base, string $fecha, ?string $capturado): string
    {
        if ($moneda === $base) {
            return '1';
        }

        if (! empty($capturado)) {
            return $capturado;
        }

        // Último tipo de cambio registrado en o antes de la fecha (directo o inverso).
        $directo = $this->ultimaTasa($moneda, $base, $fecha);
        if ($directo !== null) {
            return $directo;
        }

        $inverso = $this->ultimaTasa($base, $moneda, $fecha);
        if ($inverso !== null && bccomp($inverso, '0', 6) > 0) {
            return bcdiv('1', $inverso, 6);
        }

        throw ValidationException::withMessages([
            'tipo_cambio' => sprintf(
                'No hay tipo de cambio %s→%s registrado en o antes del %s. Captúralo manualmente.',
                $moneda, $base, CarbonImmutable::parse($fecha)->format('d/m/Y'),
            ),
        ]);
    }

    private function ultimaTasa(string $origen, string $destino, string $fecha): ?string
    {
        return FxRate::where('moneda_origen', $origen)
            ->where('moneda_destino', $destino)
            ->where('fecha', '<=', $fecha)
            ->orderByDesc('fecha')
            ->value('tasa');
    }

    /** Reproduce el libro con el cambio aplicado; lanza error de validación si algo no cuadra. */
    private function validarLibro(Portfolio $portfolio, ?array $nuevo, ?int $excluirId): void
    {
        $movimientos = $portfolio->transactions()
            ->with('asset:id,simbolo')
            ->when($excluirId, fn ($q) => $q->whereKeyNot($excluirId))
            ->get()
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'tipo' => $t->tipo,
                'fecha' => $t->fecha->toDateString(),
                'asset_id' => $t->asset_id,
                'simbolo' => $t->asset?->simbolo,
                'cantidad' => $t->cantidad,
                'precio' => $t->precio,
                'comision' => $t->comision,
                'tipo_cambio' => $t->tipo_cambio,
            ])
            ->all();

        if ($nuevo !== null) {
            $movimientos[] = $nuevo + [
                'id' => null,
                'simbolo' => $nuevo['asset_id'] ? Asset::whereKey($nuevo['asset_id'])->value('simbolo') : null,
            ];
        }

        $error = $this->ledger->primerError($movimientos, $portfolio->moneda_base);

        if ($error !== null) {
            throw ValidationException::withMessages(['general' => $error]);
        }
    }

    /**
     * Bloquea la fila del portafolio hasta terminar la transacción de base de datos,
     * para que dos personas no puedan, al mismo tiempo, vender las mismas acciones.
     */
    private function bloquear(Portfolio $portfolio): void
    {
        Portfolio::whereKey($portfolio->id)->lockForUpdate()->first();
    }
}
