<?php

namespace App\Modules\Prices\Models;

use App\Modules\Assets\Models\Asset;
use App\Modules\Users\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alerta de precio de un usuario (cada quien ve solo las suyas: BelongsToUser). */
#[Fillable(['user_id', 'asset_id', 'condicion', 'precio', 'disparada_en', 'precio_disparo', 'vista_en'])]
class PriceAlert extends Model
{
    use BelongsToUser;

    public const ARRIBA = 'arriba';

    public const ABAJO = 'abajo';

    public const CONDICIONES = [self::ARRIBA => 'Sube a', self::ABAJO => 'Baja a'];

    /** Máximo de alertas activas por usuario. */
    public const MAX_ACTIVAS = 50;

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:6',
            'precio_disparo' => 'decimal:6',
            'disparada_en' => 'datetime',
            'vista_en' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Las del usuario indicado. Se usa siempre en pantallas: el Global Scope no filtra
     * al superadmin, y las alertas son personales para todos.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDe(Builder $query, int $userId): void
    {
        $query->where($this->qualifyColumn('user_id'), $userId);
    }

    /** @param  Builder<self>  $query */
    public function scopeActivas(Builder $query): void
    {
        $query->whereNull('disparada_en');
    }

    /** Disparadas que el usuario todavía no ha visto. @param  Builder<self>  $query */
    public function scopeSinVer(Builder $query): void
    {
        $query->whereNotNull('disparada_en')->whereNull('vista_en');
    }

    /** ¿El precio cumple la condición? Llegar exactamente al precio también cuenta. */
    public function cumple(float $precio): bool
    {
        return $this->condicion === self::ARRIBA ? $precio >= (float) $this->precio : $precio <= (float) $this->precio;
    }
}
