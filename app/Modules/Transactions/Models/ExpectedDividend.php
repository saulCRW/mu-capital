<?php

namespace App\Modules\Transactions\Models;

use App\Modules\Assets\Models\Asset;
use App\Modules\Users\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dividendo anunciado de un activo (lo captura el usuario). No mueve dinero:
 * al cobrarlo se registra una transacción DIVIDENDO en el portafolio.
 */
#[Fillable(['user_id', 'asset_id', 'fecha_ex', 'fecha_pago', 'monto_por_titulo', 'moneda', 'nota'])]
class ExpectedDividend extends Model
{
    use BelongsToUser;

    protected function casts(): array
    {
        return [
            'fecha_ex' => 'date',
            'fecha_pago' => 'date',
            'monto_por_titulo' => 'decimal:6',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Día en que se cuentan los títulos: el anterior a la fecha ex (o la fecha de pago si no
     * hay fecha ex). Si todavía no llega, se usan los títulos de hoy.
     */
    public function fechaCorte(string $hoy): string
    {
        $corte = $this->fecha_ex !== null ? $this->fecha_ex->copy()->subDay()->toDateString() : $this->fecha_pago->toDateString();

        return min($corte, $hoy);
    }
}
