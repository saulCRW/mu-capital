<?php

namespace App\Modules\Organizations\Models\Scopes;

use App\Modules\Organizations\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope: agrega "WHERE organization_id = ?" a TODA consulta Eloquent
 * del modelo, con la organización del usuario autenticado.
 */
class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::isGlobal()) {
            return;
        }

        $organizationId = TenantContext::organizationId();

        if ($organizationId === null) {
            // Usuario sin despacho (dato inconsistente): no ve nada.
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('organization_id'), $organizationId);
    }
}
