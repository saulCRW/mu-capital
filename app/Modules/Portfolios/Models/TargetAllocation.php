<?php

namespace App\Modules\Portfolios\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'portfolio_id', 'dimension', 'clave', 'porcentaje_objetivo'])]
class TargetAllocation extends Model
{
    public const DIMENSION_CLASE = 'clase';
    public const DIMENSION_SECTOR = 'sector';
    public const DIMENSION_MONEDA = 'moneda';
    public const DIMENSION_PAIS = 'pais';

    protected function casts(): array
    {
        return [
            'porcentaje_objetivo' => 'decimal:4',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
