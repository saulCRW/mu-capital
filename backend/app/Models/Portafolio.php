<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portafolio extends Model
{
    protected $table = 'portafolios';
    protected $fillable = ['usuario_id', 'nombre', 'descripcion', 'estrategia', 'moneda', 'valor_inicial', 'valor_actual', 'rendimiento_total', 'benchmark', 'activo', 'es_publico', 'color'];
    protected $casts = ['activo' => 'boolean', 'es_publico' => 'boolean'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function activos()
    {
        return $this->belongsToMany(Activo::class, 'portafolio_activos', 'portafolio_id', 'activo_id')
                    ->withPivot(['cantidad', 'precio_promedio', 'peso_objetivo'])
                    ->withTimestamps();
    }

    public function transacciones()
    {
        return $this->hasMany(Transaccion::class, 'portafolio_id');
    }

    public function metricas()
    {
        return $this->hasMany(MetricaRiesgo::class, 'portafolio_id');
    }
}
