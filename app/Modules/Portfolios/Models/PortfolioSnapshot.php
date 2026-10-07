<?php

namespace App\Modules\Portfolios\Models;

use App\Modules\Users\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'portfolio_id', 'fecha', 'valor', 'efectivo', 'flujo_neto'])]
class PortfolioSnapshot extends Model
{
    use BelongsToUser;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'valor' => 'decimal:6',
            'efectivo' => 'decimal:6',
            'flujo_neto' => 'decimal:6',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }
}
