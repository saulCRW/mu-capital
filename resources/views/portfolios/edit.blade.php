@extends('layouts.app')

@section('title', 'Editar portafolio')

@section('content')
    <h1>Editar portafolio</h1>
    <form method="POST" action="{{ route('portfolios.update', $portfolio) }}" class="narrow">
        @method('PUT')
        @include('portfolios._form', ['portfolio' => $portfolio])
        <div class="actions">
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('portfolios.show', $portfolio) }}">Cancelar</a>
        </div>
    </form>
@endsection
