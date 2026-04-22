<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activo extends Model
{
    protected $table = 'activos';
    protected $fillable = ['simbolo', 'nombre', 'tipo', 'descripcion', 'exchange', 'moneda_base', 'decimales', 'activo', 'logo_url'];
    protected $casts = ['activo' => 'boolean'];

    public function historialPrecios()
    {
        return $this->hasMany(HistorialPrecio::class, 'activo_id');
    }

    public function portafolios()
    {
        return $this->belongsToMany(Portafolio::class, 'portafolio_activos', 'activo_id', 'portafolio_id')
                    ->withPivot(['cantidad', 'precio_promedio', 'peso_objetivo'])
                    ->withTimestamps();
    }
}
