@extends('layouts.app')

@section('title', 'Activos')

@section('content')
    <div class="page-head">
        <h1>Activos</h1>
        @can('create', \App\Modules\Assets\Models\Asset::class)
            <a class="btn" href="{{ route('assets.create') }}">Nuevo activo</a>
        @endcan
    </div>
    <p class="muted">Catálogo compartido por todos los despachos.</p>

    @php $ultima = \Illuminate\Support\Facades\Cache::get(\App\Modules\Prices\Services\MarketSyncService::CACHE_ULTIMA); @endphp
    <div class="actions" style="margin-bottom:16px">
        <span class="muted">
            Precios: Yahoo Finance · Tipos de cambio: Banxico ·
            {{ $ultima ? 'última actualización '.\Carbon\Carbon::parse($ultima['momento'])->timezone('America/Mexico_City')->format('d/m/Y H:i') : 'aún sin sincronizar' }}
        </span>
        @if (auth()->user()->isSuperadmin())
            <form method="POST" action="{{ route('superadmin.mercado.sincronizar') }}">
                @csrf
                <button type="submit" class="btn btn-secondary">Actualizar precios ahora</button>
            </form>
        @endif
    </div>

    <form method="GET" class="filters">
        <div style="flex:1">
            <label for="q">Buscar por símbolo o nombre</label>
            <input id="q" name="q" value="{{ $busqueda }}">
        </div>
        <button type="submit" class="btn btn-secondary">Buscar</button>
    </form>

    @if ($assets->isEmpty())
        <p class="muted">{{ $busqueda ? 'Ningún activo coincide con la búsqueda.' : 'El catálogo está vacío.' }}</p>
    @else
        <table>
            <thead>
                <tr><th>Símbolo</th><th>Nombre</th><th>Tipo</th><th>Sector</th><th>País</th><th>Moneda</th><th>Exchange</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($assets as $asset)
                    <tr>
                        <td><strong>{{ $asset->simbolo }}</strong></td>
                        <td>{{ $asset->nombre }}</td>
                        <td>{{ $asset->tipo }}</td>
                        <td>{{ $asset->sector ?? '—' }}</td>
                        <td>{{ $asset->pais ?? '—' }}</td>
                        <td>{{ $asset->moneda }}</td>
                        <td>{{ $asset->exchange ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                @can('update', $asset)
                                    <a href="{{ route('assets.edit', $asset) }}">Editar</a>
                                @endcan
                                @can('delete', $asset)
                                    <form method="POST" action="{{ route('assets.destroy', $asset) }}">
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
        {{ $assets->links('pagination::bootstrap-3') }}
    @endif
@endsection
