<?php

namespace App\Modules\Transactions\Services;

use App\Modules\Analytics\Services\SnapshotService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Billing\Services\PlanLimits;
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
    public function __construct(private LedgerValidator $ledger, private SnapshotService $snapshots, private PlanLimits $limites) {}

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
            $this->limites->asegurarTransaccion($portfolio);
            $datos = $this->normalizar($portfolio, $data);
            $posiciones = $this->validarLibro($portfolio, $datos, excluirId: null);
            $this->asegurarPosiciones($portfolio, $datos, $posiciones);

            $transaction = Transaction::create($datos + [
                'user_id' => $portfolio->user_id,
                'portfolio_id' => $portfolio->id,
                'created_by' => $autor->id,
            ]);

            $this->snapshots->recalcular($portfolio, $datos['fecha']);

            return $transaction;
        });
    }

    /**
     * Registra varias transacciones de una vez (importación): todas o ninguna.
     * El libro se valida una sola vez con todas aplicadas y los snapshots se
     * recalculan una sola vez desde la fecha más antigua.
     *
     * @param  list<array{linea:int, tipo:string, fecha:string, asset_id:?int, cantidad:string, precio:?string, comision:?string, moneda:?string, tipo_cambio:?string, nota:?string}>  $filas
     * @param  bool  $simular  valida todo sin guardar nada (vista previa)
     * @return int transacciones registradas (o que se registrarían)
     */
    public function importar(Portfolio $portfolio, array $filas, User $autor, bool $simular = false): int
    {
        return DB::transaction(function () use ($portfolio, $filas, $autor, $simular) {
            $this->bloquear($portfolio);
            $this->limites->asegurarTransaccion($portfolio, count($filas));

            $normalizadas = [];
            foreach ($filas as $fila) {
                try {
                    $normalizadas[] = $this->normalizar($portfolio, $fila);
                } catch (ValidationException $e) {
                    throw ValidationException::withMessages(['general' => "Fila {$fila['linea']}: ".collect($e->errors())->flatten()->first()]);
                }
            }

            $simbolos = Asset::whereIn('id', array_filter(array_column($normalizadas, 'asset_id')))->pluck('simbolo', 'id');
            $movimientos = [
                ...$this->movimientos($portfolio, null),
                ...array_map(fn ($n) => $n + ['id' => null, 'simbolo' => $n['asset_id'] ? $simbolos[$n['asset_id']] : null], $normalizadas),
            ];
            $resultado = $this->ledger->reproducir($movimientos, $portfolio->moneda_base);
            if ($resultado['error'] !== null) {
                throw ValidationException::withMessages(['general' => $resultado['error']]);
            }
            if (collect($normalizadas)->contains('tipo', Transaction::COMPRA)) {
                $this->limites->asegurarPosiciones($portfolio, $this->posicionesAbiertas($portfolio), self::contarAbiertas($resultado['posiciones']));
            }

            if ($simular) {
                return count($normalizadas);
            }

            foreach ($normalizadas as $datos) {
                Transaction::create($datos + [
                    'user_id' => $portfolio->user_id,
                    'portfolio_id' => $portfolio->id,
                    'created_by' => $autor->id,
                ]);
            }
            $this->snapshots->recalcular($portfolio, min(array_column($normalizadas, 'fecha')));

            return count($normalizadas);
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $portfolio = $transaction->portfolio;
            $this->bloquear($portfolio);
            $datos = $this->normalizar($portfolio, $data);
            $posiciones = $this->validarLibro($portfolio, $datos + ['id' => $transaction->id], excluirId: $transaction->id);
            $this->asegurarPosiciones($portfolio, $datos, $posiciones);

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
    /** Si el cambio es una compra, no puede abrir más posiciones de las que permite el plan. */
    private function asegurarPosiciones(Portfolio $portfolio, array $datos, int $despues): void
    {
        if ($datos['tipo'] === Transaction::COMPRA) {
            $this->limites->asegurarPosiciones($portfolio, $this->posicionesAbiertas($portfolio), $despues);
        }
    }

    /** Activos distintos en cartera según el libro actual (sin el cambio). */
    public function posicionesAbiertas(Portfolio $portfolio): int
    {
        return self::contarAbiertas($this->ledger->reproducir($this->movimientos($portfolio, null), $portfolio->moneda_base)['posiciones']);
    }

    /** @param  array<int|string, string>  $posiciones  asset_id => cantidad */
    private static function contarAbiertas(array $posiciones): int
    {
        return count(array_filter($posiciones, fn ($cantidad) => bccomp($cantidad, '0', 6) > 0));
    }

    /** @return list<array<string, mixed>> */
    private function movimientos(Portfolio $portfolio, ?int $excluirId): array
    {
        return $portfolio->transactions()
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
    }

    /**
     * Reproduce el libro con el cambio aplicado; rechaza el cambio si deja el libro inválido.
     *
     * @return int activos distintos en cartera después del cambio
     */
    private function validarLibro(Portfolio $portfolio, ?array $nuevo, ?int $excluirId): int
    {
        $movimientos = $this->movimientos($portfolio, $excluirId);

        if ($nuevo !== null) {
            $movimientos[] = $nuevo + [
                'id' => null,
                'simbolo' => $nuevo['asset_id'] ? Asset::whereKey($nuevo['asset_id'])->value('simbolo') : null,
            ];
        }

        $resultado = $this->ledger->reproducir($movimientos, $portfolio->moneda_base);

        if ($resultado['error'] !== null) {
            throw ValidationException::withMessages(['general' => $resultado['error']]);
        }

        return self::contarAbiertas($resultado['posiciones']);
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
