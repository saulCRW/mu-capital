<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected $table = 'reportes';
    public $timestamps = false;
    protected $fillable = ['usuario_id', 'portafolio_id', 'tipo', 'nombre', 'periodo_inicio', 'periodo_fin', 'formato', 'estado', 'url_descarga', 'parametros', 'tamanio_bytes', 'generado_en', 'expira_en'];
    protected $casts = ['parametros' => 'array'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
