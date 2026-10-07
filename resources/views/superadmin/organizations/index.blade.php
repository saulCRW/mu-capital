@extends('layouts.app')

@section('title', 'Despachos')

@section('content')
    <div class="page-head">
        <h1>Despachos</h1>
        <a class="btn" href="{{ route('superadmin.organizations.create') }}">Nuevo despacho</a>
    </div>

    @if ($organizations->isEmpty())
        <p class="muted">Todavía no hay despachos registrados.</p>
    @else
        <table>
            <thead>
                <tr><th>Nombre</th><th>Plan</th><th>Estado</th><th>Alta</th><th style="text-align:right">Usuarios</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($organizations as $organization)
                    <tr>
                        <td>{{ $organization->nombre }}</td>
                        <td>{{ $organization->plan->nombre }}</td>
                        <td>{{ $organization->estado }}</td>
                        <td>{{ $organization->fecha_alta->format('d/m/Y') }}</td>
                        <td style="text-align:right; font-variant-numeric: tabular-nums">{{ $organization->users_count }}</td>
                        <td><a href="{{ route('superadmin.organizations.edit', $organization) }}">Editar</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $organizations->links('pagination::bootstrap-3') }}
    @endif
@endsection
