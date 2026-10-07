@extends('layouts.guest')

@section('title', 'Recuperar contraseña')

@section('content')
    <h1>Recuperar contraseña</h1>
    <p class="muted">Escribe tu correo y te enviaremos un enlace para crear una nueva contraseña.</p>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <label for="email">Correo</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <button type="submit">Enviar enlace</button>
    </form>
    <div class="below"><a href="{{ route('login') }}">Volver a iniciar sesión</a></div>
@endsection
