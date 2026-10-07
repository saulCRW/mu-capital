@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <h1>Inicio</h1>
    {{-- Pantalla provisional: el dashboard del asesor se construye en la Fase 6. --}}
    <p class="muted">Sesión iniciada como <strong>{{ auth()->user()->nombre }}</strong>
        ({{ auth()->user()->rol }}) en {{ auth()->user()->organization->nombre }}.</p>
@endsection
