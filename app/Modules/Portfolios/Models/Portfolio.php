<?php

namespace App\Modules\Portfolios\Models;

use App\Modules\Clients\Models\Client;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'client_id', 'nombre', 'moneda_base', 'benchmark_id'])]
class Portfolio extends Model
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function benchmark(): BelongsTo
    {
        return $this->belongsTo(Benchmark::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function targetAllocations(): HasMany
    {
        return $this->hasMany(TargetAllocation::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(PortfolioSnapshot::class);
    }
}
