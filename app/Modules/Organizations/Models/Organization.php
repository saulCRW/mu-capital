<?php

namespace App\Modules\Organizations\Models;

use App\Modules\Billing\Models\Plan;
use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'plan_id', 'estado', 'fecha_alta', 'tasa_libre_riesgo'])]
class Organization extends Model
{
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_SUSPENDIDA = 'suspendida';

    protected function casts(): array
    {
        return [
            'fecha_alta' => 'date',
            'tasa_libre_riesgo' => 'decimal:6',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }
}
