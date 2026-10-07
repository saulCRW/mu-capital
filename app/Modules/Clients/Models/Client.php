<?php

namespace App\Modules\Clients\Models;

use App\Modules\Audit\Observers\AuditObserver;
use App\Modules\Clients\Policies\ClientPolicy;
use App\Modules\Organizations\Models\Concerns\BelongsToOrganization;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['organization_id', 'asesor_id', 'nombre', 'contacto', 'perfil_riesgo'])]
#[ObservedBy(AuditObserver::class)]
#[UsePolicy(ClientPolicy::class)]
class Client extends Model
{
    use BelongsToOrganization;

    public const PERFILES = ['conservador', 'moderado', 'agresivo'];

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }
}
