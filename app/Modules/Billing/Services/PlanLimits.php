<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Plan;
use App\Modules\Clients\Models\Client;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Validation\ValidationException;

/**
 * Límites del plan del despacho. Solo frenan el crecimiento: si un despacho
 * baja de plan, lo que ya tiene se conserva y se puede editar, pero no se
 * puede agregar más hasta quedar dentro del límite.
 *
 * Los conteos ignoran el filtro de despacho y filtran por organization_id
 * explícitamente, para que el resultado no dependa de quién esté conectado.
 */
class PlanLimits
{
    public function asegurarCliente(Organization $organization): void
    {
        $plan = $this->plan($organization);
        $clientes = Client::withoutGlobalScopes()->where('organization_id', $organization->id)->count();

        if ($clientes >= $plan->max_clientes) {
            $this->rechazar("Tu plan {$plan->nombre} permite hasta {$plan->max_clientes} clientes.");
        }
    }

    public function asegurarPortafolio(Client $client): void
    {
        $plan = $this->plan($client->organization);
        $portafolios = Portfolio::withoutGlobalScopes()->where('client_id', $client->id)->count();

        if ($portafolios >= $plan->max_portafolios_por_cliente) {
            $this->rechazar("Tu plan {$plan->nombre} permite hasta {$plan->max_portafolios_por_cliente} portafolios por cliente.");
        }
    }

    public function asegurarTransaccion(Portfolio $portfolio): void
    {
        $plan = $this->plan($portfolio->organization);
        $transacciones = Transaction::withoutGlobalScopes()->where('portfolio_id', $portfolio->id)->count();

        if ($transacciones >= $plan->max_transacciones_por_portafolio) {
            $this->rechazar("Tu plan {$plan->nombre} permite hasta {$plan->max_transacciones_por_portafolio} transacciones por portafolio.");
        }
    }

    /** Una operación que abre una posición nueva no puede pasar del máximo de activos distintos en cartera. */
    public function asegurarPosiciones(Portfolio $portfolio, int $antes, int $despues): void
    {
        $plan = $this->plan($portfolio->organization);

        if ($despues > $antes && $despues > $plan->max_holdings_por_portafolio) {
            $this->rechazar("Tu plan {$plan->nombre} permite hasta {$plan->max_holdings_por_portafolio} activos distintos en cartera por portafolio.");
        }
    }

    /**
     * Uso del plan: para cada límite, el máximo permitido y el uso más alto
     * (el total de clientes, y el cliente o portafolio más cargado en los demás).
     *
     * @param  array<int, int>  $posicionesPorPortafolio  portfolio_id => activos distintos en cartera
     * @return list<array{limite:string, usado:int, maximo:int}>
     */
    public function uso(Organization $organization, array $posicionesPorPortafolio): array
    {
        $plan = $this->plan($organization);
        $maximo = fn ($consulta) => (int) ($consulta->selectRaw('COUNT(*) AS n')->orderByDesc('n')->value('n') ?? 0);

        return [
            ['limite' => 'Clientes', 'usado' => Client::withoutGlobalScopes()->where('organization_id', $organization->id)->count(), 'maximo' => $plan->max_clientes],
            ['limite' => 'Portafolios por cliente', 'usado' => $maximo(Portfolio::withoutGlobalScopes()->where('organization_id', $organization->id)->groupBy('client_id')), 'maximo' => $plan->max_portafolios_por_cliente],
            ['limite' => 'Activos en cartera por portafolio', 'usado' => $posicionesPorPortafolio === [] ? 0 : max($posicionesPorPortafolio), 'maximo' => $plan->max_holdings_por_portafolio],
            ['limite' => 'Transacciones por portafolio', 'usado' => $maximo(Transaction::withoutGlobalScopes()->where('organization_id', $organization->id)->groupBy('portfolio_id')), 'maximo' => $plan->max_transacciones_por_portafolio],
        ];
    }

    private function plan(Organization $organization): Plan
    {
        return $organization->plan;
    }

    private function rechazar(string $mensaje): never
    {
        throw ValidationException::withMessages(['general' => $mensaje.' Para agregar más, pide a tu proveedor un cambio de plan.']);
    }
}
