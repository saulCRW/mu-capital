@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <h1>Iniciar sesión</h1>
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">Correo</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <button type="submit">Entrar</button>
    </form>
    <div class="below"><a href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a> · <a href="{{ route('register') }}">Crear cuenta</a></div>
@endsection
