<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialPrecio extends Model
{
    protected $table = 'historial_precios';
    public $timestamps = false;
    protected $fillable = ['activo_id', 'precio', 'precio_max', 'precio_min', 'volumen', 'market_cap', 'variacion_24h', 'fuente', 'intervalo', 'timestamp'];

    public function activo()
    {
        return $this->belongsTo(Activo::class, 'activo_id');
    }
}
