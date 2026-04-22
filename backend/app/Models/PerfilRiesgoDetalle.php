<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilRiesgoDetalle extends Model
{
    protected $table = 'perfil_riesgo_detalle';
    protected $fillable = ['usuario_id', 'nivel_riesgo', 'horizonte_temporal', 'tolerancia_perdida', 'objetivo_retorno', 'cuestionario_resp', 'puntuacion'];
    protected $casts = ['cuestionario_resp' => 'array'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
