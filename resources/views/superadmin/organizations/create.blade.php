@extends('layouts.app')

@section('title', 'Nuevo despacho')

@section('content')
    <h1>Nuevo despacho</h1>
    <form method="POST" action="{{ route('superadmin.organizations.store') }}" class="narrow">
        @csrf
        <label for="nombre">Nombre del despacho</label>
        <input id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="150">
        @error('nombre') <div class="error">{{ $message }}</div> @enderror

        <label for="plan_id">Plan</label>
        <select id="plan_id" name="plan_id" required>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((int) old('plan_id') === $plan->id)>{{ $plan->nombre }}</option>
            @endforeach
        </select>
        @error('plan_id') <div class="error">{{ $message }}</div> @enderror

        <label for="tasa_libre_riesgo_pct">Tasa libre de riesgo anual (%)</label>
        <input id="tasa_libre_riesgo_pct" name="tasa_libre_riesgo_pct" inputmode="decimal" value="{{ old('tasa_libre_riesgo_pct', '0') }}" required>
        @error('tasa_libre_riesgo_pct') <div class="error">{{ $message }}</div> @enderror

        <h1 style="margin-top:28px">Administrador del despacho</h1>

        <label for="admin_nombre">Nombre</label>
        <input id="admin_nombre" name="admin_nombre" value="{{ old('admin_nombre') }}" required maxlength="150">
        @error('admin_nombre') <div class="error">{{ $message }}</div> @enderror

        <label for="admin_email">Correo</label>
        <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required>
        @error('admin_email') <div class="error">{{ $message }}</div> @enderror

        <label for="admin_password">Contraseña</label>
        <input id="admin_password" type="password" name="admin_password" required autocomplete="new-password">
        @error('admin_password') <div class="error">{{ $message }}</div> @enderror

        <label for="admin_password_confirmation">Confirmar contraseña</label>
        <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" required autocomplete="new-password">

        <div class="actions">
            <button type="submit">Crear despacho</button>
            <a class="btn btn-secondary" href="{{ route('superadmin.organizations.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
