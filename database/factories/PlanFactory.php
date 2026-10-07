<?php

namespace Database\Factories;

use App\Modules\Billing\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Plan '.fake()->unique()->word(),
            'max_clientes' => 50,
            'max_portafolios_por_cliente' => 5,
            'max_holdings_por_portafolio' => 100,
            'max_transacciones_por_portafolio' => 5000,
        ];
    }
}
