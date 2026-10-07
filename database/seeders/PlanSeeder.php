<?php

namespace Database\Seeders;

use App\Modules\Billing\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Planes de demostración. Los límites son valores de ejemplo para probar
 * el sistema; los definitivos los decide el negocio.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['nombre' => 'Básico',      'max_clientes' => 10,  'max_portafolios_por_cliente' => 2,  'max_holdings_por_portafolio' => 25,  'max_transacciones_por_portafolio' => 500],
            ['nombre' => 'Profesional', 'max_clientes' => 50,  'max_portafolios_por_cliente' => 5,  'max_holdings_por_portafolio' => 100, 'max_transacciones_por_portafolio' => 5000],
            ['nombre' => 'Despacho',    'max_clientes' => 500, 'max_portafolios_por_cliente' => 20, 'max_holdings_por_portafolio' => 500, 'max_transacciones_por_portafolio' => 50000],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }
    }
}
