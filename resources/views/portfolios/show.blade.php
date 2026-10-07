@extends('layouts.app')

@section('title', $portfolio->nombre)

@php
    // Formato es-MX: miles con coma, decimales con punto.
    $dinero = fn ($v) => number_format((float) $v, 2, '.', ',');
    $cantidad = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    // Movimientos de efectivo que restan: se muestran con signo y en rojo.
    $salidas = ['RETIRO', 'COMISION'];
@endphp

@section('content')
    <p class="muted"><a href="{{ route('clients.show', $portfolio->client_id) }}">{{ $portfolio->client->nombre }}</a> /</p>
    <div class="page-head">
        <h1>{{ $portfolio->nombre }}</h1>
        <div class="actions">
            <a class="btn" href="{{ route('portfolios.transactions.create', $portfolio) }}">Nueva transacción</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.edit', $portfolio) }}">Editar</a>
            <form method="POST" action="{{ route('portfolios.destroy', $portfolio) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">Eliminar</button>
            </form>
        </div>
    </div>
    <p class="muted">Moneda base: {{ $portfolio->moneda_base }} · Benchmark: {{ $portfolio->benchmark?->nombre ?? '—' }}</p>

    <form method="GET" class="filters">
        <div>
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                @foreach (\App\Modules\Transactions\Models\Transaction::TIPOS as $tipo)
                    <option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? null) === $tipo)>{{ $tipo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="asset_id">Activo</label>
            <select id="asset_id" name="asset_id">
                <option value="">Todos</option>
                @foreach ($activos as $activo)
                    <option value="{{ $activo->id }}" @selected((int) ($filtros['asset_id'] ?? 0) === $activo->id)>{{ $activo->simbolo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="desde">Desde</label>
            <input id="desde" type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}">
        </div>
        <div>
            <label for="hasta">Hasta</label>
            <input id="hasta" type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}">
        </div>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
        <a href="{{ route('portfolios.show', $portfolio) }}" class="muted">Limpiar</a>
    </form>

    @if ($transactions->isEmpty())
        <p class="muted">{{ $filtros ? 'Ninguna transacción coincide con los filtros.' : 'Este portafolio todavía no tiene transacciones. Empieza con un DEPOSITO.' }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Fecha</th><th>Tipo</th><th>Activo</th>
                    <th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Comisión</th>
                    <th>Moneda</th><th class="num">Tipo cambio</th><th>Nota</th><th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $t)
                    <tr>
                        <td class="num" style="text-align:left">{{ $t->fecha->format('d/m/Y') }}</td>
                        <td>{{ $t->tipo }}</td>
                        <td>{{ $t->asset?->simbolo ?? '—' }}</td>
                        @if (in_array($t->tipo, $salidas, true))
                            <td class="num neg">-{{ $cantidad($t->cantidad) }}</td>
                        @else
                            <td class="num">{{ $cantidad($t->cantidad) }}</td>
                        @endif
                        <td class="num">{{ in_array($t->tipo, ['COMPRA', 'VENTA'], true) ? $dinero($t->precio) : '—' }}</td>
                        <td class="num">{{ (float) $t->comision ? $dinero($t->comision) : '—' }}</td>
                        <td>{{ $t->moneda }}</td>
                        <td class="num">{{ $t->moneda === $portfolio->moneda_base ? '—' : $cantidad($t->tipo_cambio) }}</td>
                        <td class="muted">{{ \Illuminate\Support\Str::limit($t->nota, 40) }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('transactions.edit', $t) }}">Editar</a>
                                <form method="POST" action="{{ route('transactions.destroy', $t) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-link">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $transactions->links('pagination::bootstrap-3') }}
    @endif
@endsection
