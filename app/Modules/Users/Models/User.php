<?php

namespace App\Modules\Users\Models;

use App\Modules\Assets\Models\Asset;
use App\Modules\Audit\Observers\AuditObserver;
use App\Modules\Billing\Models\Plan;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Users\Policies\UserPolicy;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Cada usuario gestiona sus propios portafolios. El superadmin administra la
 * plataforma (usuarios y planes, catálogo de activos, precios) y no ve
 * portafolios ajenos.
 */
#[Fillable(['plan_id', 'nombre', 'email', 'password', 'rol', 'tasa_libre_riesgo', 'estado'])]
#[Hidden(['password', 'remember_token', 'dos_factores_secreto', 'dos_factores_recuperacion'])]
#[UseFactory(UserFactory::class)]
#[UsePolicy(UserPolicy::class)]
#[ObservedBy(AuditObserver::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_SUPERADMIN = 'superadmin';

    public const ROL_USUARIO = 'usuario';

    public const ESTADO_ACTIVO = 'activo';

    public const ESTADO_SUSPENDIDO = 'suspendido';

    public const ESTADOS = [self::ESTADO_ACTIVO, self::ESTADO_SUSPENDIDO];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tasa_libre_riesgo' => 'decimal:6',
            // Cifrada con APP_KEY; los códigos de recuperación se guardan como hash.
            'dos_factores_secreto' => 'encrypted',
            'dos_factores_confirmado_en' => 'datetime',
            'dos_factores_recuperacion' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    /** Activos que el usuario sigue en Mercado (su watchlist). */
    public function watchlist(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'watchlist')->withTimestamps();
    }

    public function isSuperadmin(): bool
    {
        return $this->rol === self::ROL_SUPERADMIN;
    }

    /** Doble factor activo: ya confirmó un código de su app. */
    public function tieneDosFactores(): bool
    {
        return $this->dos_factores_confirmado_en !== null;
    }

    public function isActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }
}
