<?php

namespace App\Modules\Assets\Models;

use App\Modules\Prices\Models\Price;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['simbolo', 'nombre', 'tipo', 'sector', 'pais', 'moneda', 'exchange'])]
class Asset extends Model
{
    public const TIPOS = ['accion', 'etf', 'fondo', 'bono', 'cripto', 'divisa', 'efectivo'];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
