@extends('layouts.app')

@section('title', 'Nuevo portafolio')

@section('content')
    <p class="muted"><a href="{{ route('portfolios.index') }}">Mis portafolios</a> /</p>
    <h1>Nuevo portafolio</h1>
    <form method="POST" action="{{ route('portfolios.store') }}" class="narrow">
        @include('portfolios._form', ['portfolio' => null])
        <div class="actions">
            <button type="submit">Crear portafolio</button>
            <a class="btn btn-secondary" href="{{ route('portfolios.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
