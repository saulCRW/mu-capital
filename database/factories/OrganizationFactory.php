<?php

namespace Database\Factories;

use App\Modules\Billing\Models\Plan;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'plan_id' => Plan::factory(),
            'estado' => Organization::ESTADO_ACTIVA,
            'fecha_alta' => now()->toDateString(),
            'tasa_libre_riesgo' => '0.105000',
        ];
    }

    public function suspendida(): static
    {
        return $this->state(fn () => ['estado' => Organization::ESTADO_SUSPENDIDA]);
    }
}
