<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortafolioActivo extends Model
{
    protected $table = 'portafolio_activos';
    protected $fillable = ['portafolio_id', 'activo_id', 'cantidad', 'precio_promedio', 'peso_objetivo'];

    public function portafolio()
    {
        return $this->belongsTo(Portafolio::class, 'portafolio_id');
    }

    public function activo()
    {
        return $this->belongsTo(Activo::class, 'activo_id');
    }
}
