@extends('layouts.guest')

@section('title', 'Crear cuenta')

@section('content')
    <h1>Crear cuenta</h1>
    <p class="muted">Gestiona tus portafolios, tu rendimiento y tus reportes. Empiezas en el plan Gratis.</p>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="150" autofocus autocomplete="name">
        @error('nombre') <div class="error">{{ $message }}</div> @enderror

        <label for="email">Correo</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button type="submit">Crear cuenta</button>
    </form>
    <div class="below">¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a></div>
@endsection
