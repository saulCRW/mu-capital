<?php

namespace App\Modules\Organizations\Models;

use App\Modules\Audit\Observers\AuditObserver;
use App\Modules\Billing\Models\Plan;
use App\Modules\Clients\Models\Client;
use App\Modules\Organizations\Policies\OrganizationPolicy;
use App\Modules\Users\Models\User;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre', 'plan_id', 'estado', 'fecha_alta', 'tasa_libre_riesgo'])]
#[UseFactory(OrganizationFactory::class)]
#[UsePolicy(OrganizationPolicy::class)]
#[ObservedBy(AuditObserver::class)]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_SUSPENDIDA = 'suspendida';

    public const ESTADOS = [self::ESTADO_ACTIVA, self::ESTADO_SUSPENDIDA];

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
