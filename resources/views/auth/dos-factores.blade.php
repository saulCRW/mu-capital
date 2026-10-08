@extends('layouts.guest')

@section('title', 'Código de verificación')

@section('content')
    <h1>Código de verificación</h1>
    <p class="muted">Escribe el código de 6 dígitos que muestra tu app de autenticación. Si no tienes tu teléfono, usa uno de tus códigos de recuperación.</p>
    <form method="POST" action="{{ route('login.dos-factores') }}">
        @csrf
        <label for="codigo">Código</label>
        <input id="codigo" name="codigo" required autofocus autocomplete="one-time-code" maxlength="20" spellcheck="false">
        @error('codigo') <div class="error">{{ $message }}</div> @enderror

        <button type="submit">Verificar</button>
    </form>
    <div class="below"><a href="{{ route('login') }}">Volver al inicio de sesión</a></div>
@endsection
