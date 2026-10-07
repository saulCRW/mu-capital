<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Plan;
use App\Modules\Users\Models\User;
use App\Modules\Users\Requests\AdminUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Cuentas de la plataforma (solo superadmin): plan y estado. No da acceso a sus portafolios. */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $busqueda = trim((string) $request->query('q')) ?: null;
        $users = User::where('rol', User::ROL_USUARIO)
            ->with('plan:id,nombre')
            ->withCount('portfolios')
            ->when($busqueda, fn ($q) => $q->where(fn ($w) => $w->where('nombre', 'like', "%{$busqueda}%")->orWhere('email', 'like', "%{$busqueda}%")))
            ->orderBy('nombre')
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.users.index', compact('users', 'busqueda'));
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('superadmin.users.edit', ['user' => $user, 'plans' => Plan::orderBy('max_portafolios')->get()]);
    }

    public function update(AdminUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('superadmin.users.index')->with('status', "Cuenta de {$user->email} actualizada.");
    }
}
