<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaccion extends Model
{
    protected $table = 'transacciones';
    protected $fillable = ['portafolio_id', 'activo_id', 'usuario_id', 'tipo', 'estado', 'cantidad', 'precio_unitario', 'monto_total', 'comision', 'moneda', 'exchange', 'tx_hash', 'notas', 'ejecutada_en'];

    public function portafolio()
    {
        return $this->belongsTo(Portafolio::class, 'portafolio_id');
    }

    public function activo()
    {
        return $this->belongsTo(Activo::class, 'activo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
