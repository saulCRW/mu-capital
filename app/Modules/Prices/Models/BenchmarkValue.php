<?php

namespace App\Modules\Prices\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['benchmark_id', 'fecha', 'valor'])]
class BenchmarkValue extends Model
{
    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'valor' => 'decimal:6',
        ];
    }

    public function benchmark(): BelongsTo
    {
        return $this->belongsTo(Benchmark::class);
    }
}
