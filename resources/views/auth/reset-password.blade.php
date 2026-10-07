@extends('layouts.guest')

@section('title', 'Nueva contraseña')

@section('content')
    <h1>Nueva contraseña</h1>
    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email">Correo</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username">
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label for="password">Nueva contraseña</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label for="password_confirmation">Confirmar contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

        <button type="submit">Guardar contraseña</button>
    </form>
@endsection
