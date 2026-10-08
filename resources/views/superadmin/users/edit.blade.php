@extends('layouts.app')

@section('title', 'Editar cuenta')

@section('content')
    <p class="muted"><a href="{{ route('superadmin.users.index') }}">Usuarios</a> /</p>
    <h1>{{ $user->nombre }}</h1>
    <p class="muted">{{ $user->email }}</p>
    <form method="POST" action="{{ route('superadmin.users.update', $user) }}" class="narrow">
        @csrf
        @method('PUT')
        <label for="plan_id">Plan</label>
        <select id="plan_id" name="plan_id" required>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((int) old('plan_id', $user->plan_id) === $plan->id)>
                    {{ $plan->nombre }} — {{ $plan->max_portafolios }} portafolios, {{ number_format($plan->max_transacciones_por_portafolio) }} transacciones c/u
                </option>
            @endforeach
        </select>
        @error('plan_id') <div class="error">{{ $message }}</div> @enderror

        <label for="estado">Estado</label>
        <select id="estado" name="estado" required>
            @foreach (\App\Modules\Users\Models\User::ESTADOS as $estado)
                <option value="{{ $estado }}" @selected(old('estado', $user->estado) === $estado)>{{ $estado }}</option>
            @endforeach
        </select>
        <div class="help">Una cuenta suspendida no puede entrar; sus datos se conservan.</div>
        @error('estado') <div class="error">{{ $message }}</div> @enderror

        <div class="actions">
            <button type="submit">Guardar</button>
            <a class="btn btn-secondary" href="{{ route('superadmin.users.index') }}">Cancelar</a>
        </div>
    </form>

    <h2>Uso del plan {{ $user->plan->nombre }}</h2>
    @include('billing.partials.uso')
@endsection
