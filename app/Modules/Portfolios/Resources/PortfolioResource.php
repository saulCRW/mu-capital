<?php

namespace App\Modules\Portfolios\Resources;

use App\Modules\Portfolios\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Portafolio en la API v1. Con el saldo (->conSaldo()) agrega los KPI a hoy.
 * Dinero como texto con 6 decimales (DECIMAL(20,6), sin perder precisión); porcentajes como fracción (0.0978 = 9.78 %).
 *
 * @mixin Portfolio
 */
class PortfolioResource extends JsonResource
{
    /** @var array<string, mixed>|null */
    private ?array $saldo = null;

    /** @param  array<string, mixed>  $saldo  el de AnalyticsService::saldo() */
    public function conSaldo(array $saldo): self
    {
        $this->saldo = $saldo;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $datos = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'moneda_base' => $this->moneda_base,
            'benchmark' => $this->whenLoaded('benchmark', fn () => $this->benchmark?->nombre),
        ];

        if ($this->saldo !== null) {
            $e = $this->saldo['vacio'] ? null : $this->saldo['estado'];
            $datos['saldo'] = $e === null ? null : [
                'fecha' => $this->saldo['hasta'],
                'valor_total' => $e['valor_total'],
                'efectivo' => $e['efectivo'],
                'valor_posiciones' => $e['valor_posiciones'],
                'aportaciones_netas' => $e['aportaciones_netas'],
                'gp_total' => $e['gp_total'],
                'gp_no_realizada' => $e['gp_no_realizada'],
                'gp_realizada' => $e['gp_realizada'],
                'ingresos' => $e['ingresos'],
                'rendimiento_total' => $this->saldo['rendimiento_total'],
                'rendimiento_anualizado' => $this->saldo['rendimiento_anualizado'],
                'holdings' => count(array_filter($e['posiciones'], fn ($p) => bccomp((string) $p['cantidad'], '0', 6) !== 0)),
            ];
        }

        return $datos;
    }
}
