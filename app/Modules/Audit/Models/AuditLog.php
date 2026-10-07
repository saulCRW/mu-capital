<?php

namespace App\Modules\Audit\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// La tabla se llama "audit_log" (singular, como en CLAUDE.md) y usa su propia columna "fecha".
#[Table(name: 'audit_log', timestamps: false)]
#[Fillable([
    'organization_id',
    'user_id',
    'accion',
    'entidad',
    'entidad_id',
    'antes',
    'despues',
    'fecha',
    'ip',
])]
class AuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'antes' => 'array',
            'despues' => 'array',
            'fecha' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
