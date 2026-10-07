@extends('layouts.app')

@section('title', 'Nuevo cliente')

@section('content')
    <h1>Nuevo cliente</h1>
    <form method="POST" action="{{ route('clients.store') }}" class="narrow">
        @include('clients._form', ['client' => null])
        <div class="actions">
            <button type="submit">Crear cliente</button>
            <a class="btn btn-secondary" href="{{ route('clients.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
