@extends('layouts.app')

@section('title', 'Editar activo')

@section('content')
    <h1>Editar activo</h1>
    <form method="POST" action="{{ route('assets.update', $asset) }}" class="narrow dos-columnas">
        @method('PUT')
        @include('assets._form', ['asset' => $asset])
        <div class="actions">
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('assets.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
