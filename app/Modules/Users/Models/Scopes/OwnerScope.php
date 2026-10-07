<?php

namespace App\Modules\Users\Models\Scopes;

use App\Modules\Users\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global Scope: agrega "WHERE user_id = ?" a TODA consulta Eloquent del
 * modelo, con el usuario autenticado. Así cada quien solo ve lo suyo.
 */
class OwnerScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::isGlobal()) {
            return;
        }

        $builder->where($model->qualifyColumn('user_id'), TenantContext::userId());
    }
}
