<?php

namespace App\Modules\Billing\Models;

use App\Modules\Organizations\Models\Organization;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nombre',
    'max_clientes',
    'max_portafolios_por_cliente',
    'max_holdings_por_portafolio',
    'max_transacciones_por_portafolio',
])]
#[UseFactory(PlanFactory::class)]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

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
