@extends('layouts.app')

@section('title', 'Vista previa de importación')

@php
    $conErrores = collect($filas)->filter(fn ($f) => $f['errores'] !== [])->count();
    $numero = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
@endphp

@section('content')
    <p class="muted"><a href="{{ route('portfolios.show', $portfolio) }}">{{ $portfolio->nombre }}</a> / <a href="{{ route('portfolios.importar', $portfolio) }}">Importar</a> /</p>
    <h1>Vista previa: {{ $archivo }}</h1>

    @if ($valido)
        <div class="alert">Todo listo: se registrarán <strong>{{ count($filas) }}</strong> transacciones. Nada se ha guardado todavía.</div>
        <form method="POST" action="{{ route('portfolios.importar.confirmar', $portfolio) }}" class="actions" style="margin-bottom:16px">
            @csrf
            <button type="submit">Importar {{ count($filas) }} transacciones</button>
            <a class="btn btn-secondary" href="{{ route('portfolios.importar', $portfolio) }}">Subir otro archivo</a>
        </form>
    @else
        @foreach ($errores as $error)
            <div class="alert alert-error">{{ $error }}</div>
        @endforeach
        @if ($conErrores > 0)
            <div class="alert alert-error">{{ $conErrores }} {{ $conErrores === 1 ? 'fila tiene' : 'filas tienen' }} errores. Corrige el archivo y vuelve a subirlo; no se guardó nada.</div>
        @endif
        <p><a class="btn btn-secondary" href="{{ route('portfolios.importar', $portfolio) }}">Subir el archivo corregido</a></p>
    @endif

    @if ($filas !== [])
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th class="num">Línea</th><th>Tipo</th><th>Fecha</th><th>Activo</th><th class="num">Cantidad</th><th class="num">Precio</th>
                        <th class="num">Comisión</th><th>Moneda</th><th class="num">Tipo cambio</th><th>Nota / errores</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $f)
                        @php $d = $f['datos']; @endphp
                        <tr @if ($f['errores'] !== []) style="background: var(--danger-soft)" @endif>
                            <td class="num">{{ $f['linea'] }}</td>
                            <td>{{ $d['tipo'] ?? '—' }}</td>
                            <td>{{ $d['fecha'] ? \Carbon\Carbon::parse($d['fecha'])->format('d/m/Y') : '—' }}</td>
                            <td>{{ $d['simbolo'] ?? '—' }}{{ $d['exchange'] ? ' ('.$d['exchange'].')' : '' }}</td>
                            <td class="num">{{ $numero($d['cantidad']) }}</td>
                            <td class="num">{{ $numero($d['precio']) }}</td>
                            <td class="num">{{ $numero($d['comision']) }}</td>
                            <td>{{ $d['moneda'] ?? '—' }}</td>
                            <td class="num">{{ $numero($d['tipo_cambio']) }}</td>
                            <td>
                                @forelse ($f['errores'] as $e)
                                    <div class="error" style="margin:0">{{ $e }}</div>
                                @empty
                                    <span class="muted">{{ \Illuminate\Support\Str::limit($d['nota'], 40) }}</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
