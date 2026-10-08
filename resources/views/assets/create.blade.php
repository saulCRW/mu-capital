@extends('layouts.app')

@section('title', 'Nuevo activo')

@section('content')
    <h1>Nuevo activo</h1>
    <form method="POST" action="{{ route('assets.store') }}" class="narrow dos-columnas">
        @include('assets._form', ['asset' => null])
        <div class="actions">
            <button type="submit">Crear activo</button>
            <a class="btn btn-secondary" href="{{ route('assets.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
