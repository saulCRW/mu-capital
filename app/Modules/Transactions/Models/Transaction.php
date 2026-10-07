<?php

namespace App\Modules\Transactions\Models;

use App\Modules\Assets\Models\Asset;
use App\Modules\Audit\Observers\AuditObserver;
use App\Modules\Organizations\Models\Concerns\BelongsToOrganization;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Policies\TransactionPolicy;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'portfolio_id',
    'asset_id',
    'tipo',
    'fecha',
    'cantidad',
    'precio',
    'comision',
    'moneda',
    'tipo_cambio',
    'nota',
    'created_by',
])]
#[ObservedBy(AuditObserver::class)]
#[UsePolicy(TransactionPolicy::class)]
class Transaction extends Model
{
    use BelongsToOrganization;

    public const COMPRA = 'COMPRA';
    public const VENTA = 'VENTA';
    public const DEPOSITO = 'DEPOSITO';
    public const RETIRO = 'RETIRO';
    public const DIVIDENDO = 'DIVIDENDO';
    public const INTERES = 'INTERES';
    public const COMISION = 'COMISION';
    public const SPLIT = 'SPLIT';

    public const TIPOS = [
        self::COMPRA, self::VENTA, self::DEPOSITO, self::RETIRO,
        self::DIVIDENDO, self::INTERES, self::COMISION, self::SPLIT,
    ];

    /** Tipos que siempre se refieren a un activo. */
    public const REQUIEREN_ACTIVO = [self::COMPRA, self::VENTA, self::DIVIDENDO, self::SPLIT];

    /** Tipos que nunca se refieren a un activo. */
    public const SIN_ACTIVO = [self::DEPOSITO, self::RETIRO];

    /** Tipos con precio unitario y comisión. */
    public const OPERACIONES = [self::COMPRA, self::VENTA];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cantidad' => 'decimal:6',
            'precio' => 'decimal:6',
            'comision' => 'decimal:6',
            'tipo_cambio' => 'decimal:6',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
