@extends('layouts.app')

@section('title', 'Nuevo portafolio')

@section('content')
    <p class="muted"><a href="{{ route('clients.show', $client) }}">{{ $client->nombre }}</a> /</p>
    <h1>Nuevo portafolio</h1>
    <form method="POST" action="{{ route('clients.portfolios.store', $client) }}" class="narrow">
        @include('portfolios._form', ['portfolio' => null])
        <div class="actions">
            <button type="submit">Crear portafolio</button>
            <a class="btn btn-secondary" href="{{ route('clients.show', $client) }}">Cancelar</a>
        </div>
    </form>
@endsection
