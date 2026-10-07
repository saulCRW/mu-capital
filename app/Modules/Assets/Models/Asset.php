<?php

namespace App\Modules\Assets\Models;

use App\Modules\Assets\Policies\AssetPolicy;
use App\Modules\Audit\Observers\AuditObserver;
use App\Modules\Prices\Models\Price;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Catálogo GLOBAL de activos: lo comparten todos los despachos (no lleva organization_id). */
#[Fillable(['simbolo', 'nombre', 'tipo', 'sector', 'pais', 'moneda', 'exchange', 'simbolo_proveedor'])]
#[ObservedBy(AuditObserver::class)]
#[UsePolicy(AssetPolicy::class)]
class Asset extends Model
{
    public const TIPOS = ['accion', 'etf', 'fondo', 'bono', 'cripto', 'divisa', 'efectivo'];

    /** Nombre para mostrar de cada tipo (clase de activo). */
    public const ETIQUETAS = [
        'accion' => 'Acciones', 'etf' => 'ETF', 'fondo' => 'Fondos', 'bono' => 'Bonos',
        'cripto' => 'Cripto', 'divisa' => 'Divisas', 'efectivo' => 'Efectivo',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
