<?php

namespace App\Modules\Clients\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clients\Models\Client;
use App\Modules\Clients\Requests\ClientRequest;
use App\Modules\Clients\Services\ClientService;
use App\Modules\Users\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private ClientService $service)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Client::class);

        $busqueda = trim((string) $request->query('q')) ?: null;
        $clients = $this->service->visiblesPara($request->user(), $busqueda)->paginate(25)->withQueryString();

        return view('clients.index', compact('clients', 'busqueda'));
    }

    public function create(): View
    {
        Gate::authorize('create', Client::class);

        return view('clients.create', ['asesores' => $this->asesores()]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = $this->service->create($request->user(), $request->validated());

        return redirect()->route('clients.show', $client)->with('status', "Cliente \"{$client->nombre}\" creado.");
    }

    public function show(Client $client): View
    {
        Gate::authorize('view', $client);

        $client->load(['asesor:id,nombre', 'portfolios' => fn ($q) => $q->with('benchmark:id,nombre')->withCount('transactions')->orderBy('nombre')]);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        Gate::authorize('update', $client);

        return view('clients.edit', ['client' => $client, 'asesores' => $this->asesores()]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $this->service->update($request->user(), $client, $request->validated());

        return redirect()->route('clients.show', $client)->with('status', 'Cliente actualizado.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        Gate::authorize('delete', $client);

        $this->service->delete($client);

        return redirect()->route('clients.index')->with('status', "Cliente \"{$client->nombre}\" eliminado.");
    }

    /** Usuarios que pueden ser asesor de un cliente (el Global Scope ya limita al despacho). */
    private function asesores()
    {
        return User::whereIn('rol', [User::ROL_ADMIN, User::ROL_ASESOR])->orderBy('nombre')->get(['id', 'nombre']);
    }
}
