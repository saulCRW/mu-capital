@extends('layouts.app')

@section('title', 'Editar cliente')

@section('content')
    <h1>Editar cliente</h1>
    <form method="POST" action="{{ route('clients.update', $client) }}" class="narrow">
        @method('PUT')
        @include('clients._form', ['client' => $client])
        <div class="actions">
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('clients.show', $client) }}">Cancelar</a>
        </div>
    </form>
@endsection
