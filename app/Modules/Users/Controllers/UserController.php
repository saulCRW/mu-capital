<?php

namespace App\Modules\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\DosFactores;
use App\Modules\Billing\Models\Plan;
use App\Modules\Billing\Services\PlanLimits;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Services\TransactionService;
use App\Modules\Users\Models\User;
use App\Modules\Users\Requests\AdminUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Cuentas de la plataforma (solo superadmin): plan, estado y uso del plan. No da acceso a sus portafolios. */
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

    public function edit(User $user, PlanLimits $limites, TransactionService $transacciones): View
    {
        Gate::authorize('update', $user);

        // Solo cuántos activos tiene en cartera cada portafolio (para el uso del plan), no su contenido.
        $posiciones = Portfolio::where('user_id', $user->id)->get()
            ->mapWithKeys(fn (Portfolio $p) => [$p->id => $transacciones->posicionesAbiertas($p)])->all();

        return view('superadmin.users.edit', [
            'user' => $user->load('plan'),
            'plans' => Plan::orderBy('max_portafolios')->get(),
            'uso' => $limites->uso($user, $posiciones),
        ]);
    }

    public function update(AdminUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('superadmin.users.index')->with('status', "Cuenta de {$user->email} actualizada.");
    }

    /** Para quien perdió su teléfono y sus códigos de recuperación: vuelve a entrar solo con su contraseña. */
    public function quitarDosFactores(User $user, DosFactores $dosFactores): RedirectResponse
    {
        Gate::authorize('update', $user);

        $dosFactores->desactivar($user);

        return redirect()->route('superadmin.users.edit', $user)->with('status', "Se quitó el doble factor de {$user->email}.");
    }
}
