<?php

namespace App\Modules\Users\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Audit\Observers\AuditObserver;
use App\Modules\Clients\Models\Client;
use App\Modules\Organizations\Models\Concerns\BelongsToOrganization;
use App\Modules\Users\Policies\UserPolicy;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['organization_id', 'client_id', 'nombre', 'email', 'password', 'rol'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
#[UsePolicy(UserPolicy::class)]
#[ObservedBy(AuditObserver::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use BelongsToOrganization, HasFactory, Notifiable;

    public const ROL_SUPERADMIN = 'superadmin';

    public const ROL_ADMIN = 'admin';

    public const ROL_ASESOR = 'asesor';

    public const ROL_CLIENTE = 'cliente';

    /** Roles que un admin puede asignar dentro de su despacho. */
    public const ROLES_DESPACHO = [self::ROL_ADMIN, self::ROL_ASESOR, self::ROL_CLIENTE];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Cuando el rol es cliente: el registro de cliente cuyos portafolios consulta. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Clientes asignados a este usuario cuando su rol es asesor. */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'asesor_id');
    }

    public function isSuperadmin(): bool
    {
        return $this->rol === self::ROL_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }
}
