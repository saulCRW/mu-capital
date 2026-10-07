<?php

namespace App\Modules\Users\Models\Concerns;

use App\Modules\Users\Models\Scopes\OwnerScope;
use App\Modules\Users\Models\User;
use App\Modules\Users\Services\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Para todo modelo con columna user_id (su dueño):
 * - filtra las consultas por el usuario autenticado (Global Scope);
 * - al crear, llena user_id automáticamente si no se indicó.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope(new OwnerScope);

        static::creating(function ($model) {
            if (empty($model->user_id) && ($userId = TenantContext::userId()) !== null) {
                $model->user_id = $userId;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
