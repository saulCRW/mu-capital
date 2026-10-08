<?php

namespace App\Modules\Transactions\Resources;

use App\Modules\Transactions\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transacción en la API v1 (montos como texto con 6 decimales).
 *
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'fecha' => $this->fecha->toDateString(),
            'activo' => $this->asset?->simbolo,
            'asset_id' => $this->asset_id,
            'cantidad' => $this->cantidad,
            'precio' => $this->precio,
            'comision' => $this->comision,
            'moneda' => $this->moneda,
            'tipo_cambio' => $this->tipo_cambio,
            'nota' => $this->nota,
        ];
    }
}
