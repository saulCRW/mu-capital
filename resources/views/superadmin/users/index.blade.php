@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
    <h1>Usuarios</h1>
    <p class="muted">Cuentas de la plataforma. Desde aquí cambias su plan o las suspendes; sus portafolios son privados.</p>

    <form method="GET" class="filters">
        <div style="flex:1">
            <label for="q">Buscar por nombre o correo</label>
            <input id="q" name="q" value="{{ $busqueda }}">
        </div>
        <button type="submit" class="btn btn-secondary">Buscar</button>
    </form>

    @if ($users->isEmpty())
        <p class="muted">{{ $busqueda ? 'Ninguna cuenta coincide con la búsqueda.' : 'Todavía no hay cuentas.' }}</p>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr><th>Nombre</th><th>Correo</th><th>Plan</th><th class="num">Portafolios</th><th>Estado</th><th>Alta</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->nombre }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->plan?->nombre ?? '—' }}</td>
                            <td class="num">{{ $user->portfolios_count }}</td>
                            <td class="{{ $user->isActivo() ? '' : 'neg' }}">{{ $user->estado }}</td>
                            <td>{{ $user->created_at?->format('d/m/Y') }}</td>
                            <td><a href="{{ route('superadmin.users.edit', $user) }}">Editar</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('pagination::bootstrap-3') }}
    @endif
@endsection
