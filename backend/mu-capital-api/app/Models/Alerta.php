<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $table = 'alertas';
    protected $fillable = ['usuario_id', 'activo_id', 'portafolio_id', 'tipo', 'nombre', 'descripcion', 'condicion', 'canal', 'webhook_url', 'activa', 'disparada', 'ultima_disparo', 'veces_disparada', 'expira_en'];
    protected $casts = ['condicion' => 'array', 'activa' => 'boolean', 'disparada' => 'boolean'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function activo()
    {
        return $this->belongsTo(Activo::class, 'activo_id');
    }
}
