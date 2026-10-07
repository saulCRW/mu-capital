@extends('layouts.app')

@section('title', 'Nuevo usuario')

@section('content')
    <h1>Nuevo usuario</h1>
    <form method="POST" action="{{ route('admin.users.store') }}" class="narrow">
        @include('admin.users._form', ['user' => null])
        <div class="actions">
            <button type="submit">Crear usuario</button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
