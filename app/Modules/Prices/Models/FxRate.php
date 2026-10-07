<?php

namespace App\Modules\Prices\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['moneda_origen', 'moneda_destino', 'fecha', 'tasa'])]
class FxRate extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'tasa' => 'decimal:6',
        ];
    }
}
