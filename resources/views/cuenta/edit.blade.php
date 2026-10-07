@extends('layouts.app')

@section('title', 'Mi cuenta')

@section('content')
    <h1>Mi cuenta</h1>
    <form method="POST" action="{{ route('cuenta.update') }}" class="narrow">
        @csrf
        @method('PUT')
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" value="{{ old('nombre', $user->nombre) }}" required maxlength="150">
        @error('nombre') <div class="error">{{ $message }}</div> @enderror

        <label for="email">Correo</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        @unless ($user->isSuperadmin())
            <label for="tasa_libre_riesgo">Tasa libre de riesgo (% anual)</label>
            <input id="tasa_libre_riesgo" name="tasa_libre_riesgo" inputmode="decimal"
                   value="{{ old('tasa_libre_riesgo', rtrim(rtrim(number_format((float) $user->tasa_libre_riesgo * 100, 4, '.', ''), '0'), '.')) }}" required>
            <div class="help">Se usa en Sharpe y Sortino. Por ejemplo, la tasa de CETES a 28 días.</div>
            @error('tasa_libre_riesgo') <div class="error">{{ $message }}</div> @enderror
        @endunless

        <h2>Cambiar contraseña</h2>
        <label for="password_actual">Contraseña actual</label>
        <input id="password_actual" type="password" name="password_actual" autocomplete="current-password">
        @error('password_actual') <div class="error">{{ $message }}</div> @enderror

        <label for="password">Nueva contraseña</label>
        <input id="password" type="password" name="password" autocomplete="new-password">
        <div class="help">Déjala vacía para conservar la actual.</div>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label for="password_confirmation">Confirmar nueva contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">

        <div class="actions">
            <button type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection
