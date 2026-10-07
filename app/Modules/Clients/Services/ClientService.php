<?php

namespace App\Modules\Clients\Services;

use App\Modules\Billing\Services\PlanLimits;
use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ClientService
{
    public function __construct(private PlanLimits $limites) {}

    /** Clientes visibles para el usuario: el admin ve todos los del despacho; el asesor, los suyos; el usuario cliente, el suyo. */
    public function visiblesPara(User $user, ?string $busqueda = null): Builder
    {
        return Client::query()
            ->with('asesor:id,nombre')
            ->withCount('portfolios')
            ->when($user->rol === User::ROL_ASESOR, fn (Builder $q) => $q->where('asesor_id', $user->id))
            ->when($user->rol === User::ROL_CLIENTE, fn (Builder $q) => $q->whereKey($user->client_id ?? 0))
            ->when($busqueda, fn (Builder $q) => $q->where('nombre', 'like', '%'.$busqueda.'%'))
            ->orderBy('nombre');
    }

    /** @param  array{nombre:string, contacto:?string, perfil_riesgo:string, asesor_id?:int}  $data */
    public function create(User $actor, array $data): Client
    {
        $this->limites->asegurarCliente($actor->organization);

        // El asesor siempre crea clientes a su nombre; el admin elige el asesor.
        if (! $actor->isAdmin()) {
            $data['asesor_id'] = $actor->id;
        }

        return Client::create($data);
    }

    /** @param  array{nombre:string, contacto:?string, perfil_riesgo:string, asesor_id?:int}  $data */
    public function update(User $actor, Client $client, array $data): Client
    {
        // Solo el admin puede reasignar el cliente a otro asesor.
        if (! $actor->isAdmin()) {
            unset($data['asesor_id']);
        }

        $client->update($data);

        return $client;
    }

    public function delete(Client $client): void
    {
        if ($client->portfolios()->exists()) {
            throw ValidationException::withMessages([
                'general' => 'Este cliente tiene portafolios. Elimínalos antes de eliminar al cliente.',
            ]);
        }

        $client->delete();
    }
}
