<?php

namespace App\Modules\Organizations\Models\Concerns;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\Scopes\OrganizationScope;
use App\Modules\Organizations\Services\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Para todo modelo con columna organization_id:
 * - filtra las consultas por la organización del usuario (Global Scope);
 * - al crear, llena organization_id automáticamente si no se indicó.
 */
trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function ($model) {
            if (empty($model->organization_id) && ($organizationId = TenantContext::organizationId()) !== null) {
                $model->organization_id = $organizationId;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
