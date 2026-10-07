<?php

namespace Database\Seeders;

use App\Modules\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes por usuario. Toda cuenta nueva empieza en "Gratis"
 * (RegisterController::PLAN_INICIAL). Los límites son de ejemplo; los
 * definitivos los decide el negocio.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['nombre' => 'Gratis',  'max_portafolios' => 2,  'max_holdings_por_portafolio' => 25,  'max_transacciones_por_portafolio' => 500],
            ['nombre' => 'Pro',     'max_portafolios' => 10, 'max_holdings_por_portafolio' => 100, 'max_transacciones_por_portafolio' => 5000],
            ['nombre' => 'Premium', 'max_portafolios' => 50, 'max_holdings_por_portafolio' => 500, 'max_transacciones_por_portafolio' => 50000],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
