<?php

namespace App\Modules\Billing\Models;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nombre',
    'max_clientes',
    'max_portafolios_por_cliente',
    'max_holdings_por_portafolio',
    'max_transacciones_por_portafolio',
])]
class Plan extends Model
{
    protected function casts(): array
    {
        return [
            'max_clientes' => 'integer',
            'max_portafolios_por_cliente' => 'integer',
            'max_holdings_por_portafolio' => 'integer',
            'max_transacciones_por_portafolio' => 'integer',
        ];
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }
}
