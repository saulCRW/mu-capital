<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Billing\Models\Plan;
use App\Modules\Users\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Registro abierto: cualquiera crea su cuenta y empieza en el plan gratuito. */
class RegisterController extends Controller
{
    /** Plan con el que empieza toda cuenta nueva. */
    public const PLAN_INICIAL = 'Gratis';

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create($request->validated() + [
            'rol' => User::ROL_USUARIO,
            'estado' => User::ESTADO_ACTIVO,
            'plan_id' => Plan::where('nombre', self::PLAN_INICIAL)->value('id') ?? Plan::orderBy('max_portafolios')->value('id'),
        ]);

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Bienvenido. Crea tu primer portafolio para empezar.');
    }
}
