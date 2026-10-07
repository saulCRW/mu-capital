<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use App\Modules\Users\Requests\StoreUserRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use App\Modules\Users\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Usuarios del despacho (solo admin). El Global Scope ya limita todo a su organización. */
class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        $users = User::orderBy('nombre')->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.users.create', ['clientes' => $this->clientes()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->service->create($request->validated());

        return redirect()->route('admin.users.index')->with('status', "Usuario {$user->email} creado.");
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('admin.users.edit', ['user' => $user, 'clientes' => $this->clientes()]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated());

        return redirect()->route('admin.users.index')->with('status', "Usuario {$user->email} actualizado.");
    }

    /** Clientes del despacho, para ligar un usuario con rol cliente. */
    private function clientes()
    {
        return Client::orderBy('nombre')->get(['id', 'nombre']);
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $this->service->delete($user);

        return redirect()->route('admin.users.index')->with('status', "Usuario {$user->email} eliminado.");
    }
}
