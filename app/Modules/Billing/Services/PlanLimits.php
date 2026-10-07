<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Plan;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Límites del plan del usuario. Solo frenan el crecimiento: si un usuario
 * baja de plan, lo que ya tiene se conserva y se puede editar, pero no se
 * puede agregar más hasta quedar dentro del límite.
 *
 * Los conteos ignoran el filtro por dueño y filtran por user_id
 * explícitamente, para que el resultado no dependa de quién esté conectado.
 */
class PlanLimits
{
    public function asegurarPortafolio(User $user): void
    {
        $plan = $user->plan;
        $portafolios = Portfolio::withoutGlobalScopes()->where('user_id', $user->id)->count();

        if ($portafolios >= $plan->max_portafolios) {
            $this->rechazar("Tu plan {$plan->nombre} permite hasta {$plan->max_portafolios} portafolios.");
        }
    }

    public function asegurarTransaccion(Portfolio $portfolio, int $nuevas = 1): void
    {
        $plan = $this->plan($portfolio);
        $transacciones = Transaction::withoutGlobalScopes()->where('portfolio_id', $portfolio->id)->count();

        if ($transacciones + $nuevas > $plan->max_transacciones_por_portafolio) {
            $detalle = $nuevas > 1 ? " Este portafolio tiene {$transacciones} y se intentan agregar {$nuevas}." : '';
            $this->rechazar("Tu plan {$plan->nombre} permite hasta {$plan->max_transacciones_por_portafolio} transacciones por portafolio.{$detalle}");
        }
    }

    /** Una operación que abre una posición nueva no puede pasar del máximo de activos distintos en cartera. */
    public function asegurarPosiciones(Portfolio $portfolio, int $antes, int $despues): void
    {
        $plan = $this->plan($portfolio);

        if ($despues > $antes && $despues > $plan->max_holdings_por_portafolio) {
            $this->rechazar("Tu plan {$plan->nombre} permite hasta {$plan->max_holdings_por_portafolio} activos distintos en cartera por portafolio.");
        }
    }

    /**
     * Uso del plan: para cada límite, el máximo permitido y el uso más alto
     * (el total de portafolios, y el portafolio más cargado en los demás).
     *
     * @param  array<int, int>  $posicionesPorPortafolio  portfolio_id => activos distintos en cartera
     * @return list<array{limite:string, usado:int, maximo:int}>
     */
    public function uso(User $user, array $posicionesPorPortafolio): array
    {
        $plan = $user->plan;
        $transacciones = Transaction::withoutGlobalScopes()->where('user_id', $user->id)
            ->groupBy('portfolio_id')->selectRaw('COUNT(*) AS n')->orderByDesc('n')->value('n');

        return [
            ['limite' => 'Portafolios', 'usado' => Portfolio::withoutGlobalScopes()->where('user_id', $user->id)->count(), 'maximo' => $plan->max_portafolios],
            ['limite' => 'Activos en cartera por portafolio', 'usado' => $posicionesPorPortafolio === [] ? 0 : max($posicionesPorPortafolio), 'maximo' => $plan->max_holdings_por_portafolio],
            ['limite' => 'Transacciones por portafolio', 'usado' => (int) ($transacciones ?? 0), 'maximo' => $plan->max_transacciones_por_portafolio],
        ];
    }

    private function plan(Portfolio $portfolio): Plan
    {
        return User::findOrFail($portfolio->user_id)->plan;
    }

    private function rechazar(string $mensaje): never
    {
        throw ValidationException::withMessages(['general' => $mensaje.' Cambia de plan para agregar más.']);
    }
}
