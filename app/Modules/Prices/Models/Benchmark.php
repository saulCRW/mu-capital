<?php

namespace App\Modules\Prices\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre'])]
class Benchmark extends Model
{
    public function values(): HasMany
    {
        return $this->hasMany(BenchmarkValue::class);
    }
}
