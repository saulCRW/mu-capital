<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';
    protected $fillable = [
        'rol_id', 'nombre', 'apellido', 'email', 'password_hash',
        'telefono', 'pais', 'foto_perfil_url', 'activo', 'email_verificado'
    ];
    protected $hidden = ['password_hash'];
    protected $casts = ['activo' => 'boolean', 'email_verificado' => 'boolean'];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function rol()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function portafolios()
    {
        return $this->hasMany(Portafolio::class, 'usuario_id');
    }

    public function transacciones()
    {
        return $this->hasMany(Transaccion::class, 'usuario_id');
    }

    public function alertas()
    {
        return $this->hasMany(Alerta::class, 'usuario_id');
    }

    public function perfilRiesgo()
    {
        return $this->hasOne(PerfilRiesgoDetalle::class, 'usuario_id');
    }
}
