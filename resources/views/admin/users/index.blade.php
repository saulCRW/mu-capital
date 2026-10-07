@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <div class="page-head">
        <h1>Usuarios del despacho</h1>
        <a class="btn" href="{{ route('admin.users.create') }}">Nuevo usuario</a>
    </div>

    @if ($users->isEmpty())
        <p class="muted">Todavía no hay usuarios.</p>
    @else
        <table>
            <thead>
                <tr><th>Nombre</th><th>Correo</th><th>Rol</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->nombre }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->rol }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.users.edit', $user) }}">Editar</a>
                                @can('delete', $user)
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-link">Eliminar</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $users->links('pagination::bootstrap-3') }}
    @endif
@endsection
