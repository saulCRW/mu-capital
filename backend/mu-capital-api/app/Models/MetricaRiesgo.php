<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetricaRiesgo extends Model
{
    protected $table = 'metricas_riesgo';
    public $timestamps = false;
    protected $fillable = ['portafolio_id', 'fecha', 'valor_en_riesgo', 'valor_en_riesgo_99', 'volatilidad', 'beta', 'alpha', 'sharpe_ratio', 'sortino_ratio', 'max_drawdown', 'correlacion_btc', 'correlacion_sp500', 'nivel_diversific', 'periodo_calculo'];

    public function portafolio()
    {
        return $this->belongsTo(Portafolio::class, 'portafolio_id');
    }
}
