@extends('layouts.app')

@section('title', $client->nombre)

@section('content')
    <p class="muted"><a href="{{ route('clients.index') }}">Clientes</a> /</p>
    <div class="page-head">
        <h1>{{ $client->nombre }}</h1>
        <div class="actions">
            <a class="btn btn-secondary" href="{{ route('clients.edit', $client) }}">Editar</a>
            <form method="POST" action="{{ route('clients.destroy', $client) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">Eliminar</button>
            </form>
        </div>
    </div>
    <p class="muted">
        Asesor: {{ $client->asesor->nombre }} · Perfil: {{ $client->perfil_riesgo }}
        @if ($client->contacto) · {{ $client->contacto }} @endif
    </p>

    <div class="page-head" style="margin-top:28px">
        <h1>Portafolios</h1>
        <a class="btn" href="{{ route('clients.portfolios.create', $client) }}">Nuevo portafolio</a>
    </div>

    @if ($client->portfolios->isEmpty())
        <p class="muted">Este cliente todavía no tiene portafolios.</p>
    @else
        <table>
            <thead>
                <tr><th>Nombre</th><th>Moneda base</th><th>Benchmark</th><th class="num">Transacciones</th></tr>
            </thead>
            <tbody>
                @foreach ($client->portfolios as $portfolio)
                    <tr>
                        <td><a href="{{ route('portfolios.show', $portfolio) }}">{{ $portfolio->nombre }}</a></td>
                        <td>{{ $portfolio->moneda_base }}</td>
                        <td>{{ $portfolio->benchmark?->nombre ?? '—' }}</td>
                        <td class="num">{{ $portfolio->transactions_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
