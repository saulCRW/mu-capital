<?php

namespace App\Modules\Portfolios\Models;

use App\Modules\Audit\Observers\AuditObserver;
use App\Modules\Portfolios\Policies\PortfolioPolicy;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Portafolio de un usuario (su dueño, user_id): solo él lo ve y lo gestiona. */
#[Fillable(['user_id', 'nombre', 'moneda_base', 'benchmark_id'])]
#[ObservedBy(AuditObserver::class)]
#[UsePolicy(PortfolioPolicy::class)]
class Portfolio extends Model
{
    use BelongsToUser;

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
