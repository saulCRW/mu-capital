@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="page-head">
        <h1>Clientes</h1>
        <a class="btn" href="{{ route('clients.create') }}">Nuevo cliente</a>
    </div>

    <form method="GET" class="filters">
        <div style="flex:1">
            <label for="q">Buscar por nombre</label>
            <input id="q" name="q" value="{{ $busqueda }}">
        </div>
        <button type="submit" class="btn-secondary btn">Buscar</button>
    </form>

    @if ($clients->isEmpty())
        <p class="muted">{{ $busqueda ? 'Ningún cliente coincide con la búsqueda.' : 'Todavía no hay clientes. Crea el primero.' }}</p>
    @else
        <table>
            <thead>
                <tr><th>Nombre</th><th>Asesor</th><th>Perfil de riesgo</th><th class="num">Portafolios</th></tr>
            </thead>
            <tbody>
                @foreach ($clients as $client)
                    <tr>
                        <td><a href="{{ route('clients.show', $client) }}">{{ $client->nombre }}</a></td>
                        <td>{{ $client->asesor->nombre }}</td>
                        <td>{{ $client->perfil_riesgo }}</td>
                        <td class="num">{{ $client->portfolios_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $clients->links('pagination::bootstrap-3') }}
    @endif
@endsection
