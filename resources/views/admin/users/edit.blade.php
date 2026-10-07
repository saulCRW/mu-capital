@extends('layouts.app')

@section('title', 'Editar usuario')

@section('content')
    <h1>Editar usuario</h1>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="narrow">
        @method('PUT')
        @include('admin.users._form', ['user' => $user])
        <div class="actions">
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
