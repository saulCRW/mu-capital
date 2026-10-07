<?php

namespace App\Modules\Prices\Models;

use App\Modules\Assets\Models\Asset;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['asset_id', 'fecha', 'cierre'])]
class Price extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'cierre' => 'decimal:6',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
