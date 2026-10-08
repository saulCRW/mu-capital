@extends('layouts.app')

@section('title', 'Precios')

@php
    $numero = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
@endphp

@section('content')
    <style>
        .captura { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 12px; margin-bottom: 8px; }
        .captura form.narrow { max-width: none; }
        .captura h2 { margin: 0 0 4px; }
        .fila-campos { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 0 10px; }
    </style>

    <h1>Precios</h1>
    <p class="muted">Cierres de los activos, tipos de cambio y valores de índices de toda la plataforma. Lo que captures o importes reemplaza el dato de la misma fecha; precios y tipos de cambio recalculan las valuaciones de los portafolios desde esa fecha. La sincronización automática (Activos → Sincronizar mercado) sigue funcionando.</p>

    <x-tabs etiqueta="Tipo de dato">
        <a href="{{ route('superadmin.precios.index') }}" @if ($tab === 'precios') aria-current="page" @endif>Precios de activos</a>
        <a href="{{ route('superadmin.precios.index', ['tab' => 'tipos']) }}" @if ($tab === 'tipos') aria-current="page" @endif>Tipos de cambio</a>
        <a href="{{ route('superadmin.precios.index', ['tab' => 'indices']) }}" @if ($tab === 'indices') aria-current="page" @endif>Índices</a>
    </x-tabs>

    @error('importacion')
        <div class="alert alert-error" role="alert">
            <div>
                <strong>No se importó nada; corrige el archivo y vuelve a subirlo.</strong>
                <ul style="margin:6px 0 0;padding-left:18px">
                    @foreach ($errors->get('importacion') as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @enderror

    <div class="captura">
        @if ($tab === 'precios')
            <form method="POST" action="{{ route('superadmin.precios.store') }}" class="narrow">
                @csrf
                <h2>Capturar cierre</h2>
                <label for="asset_id">Activo</label>
                <select id="asset_id" name="asset_id" required>
                    <option value="">Elige un activo…</option>
                    @foreach ($activos as $a)
                        <option value="{{ $a->id }}" @selected((int) old('asset_id', $activo) === $a->id)>{{ $a->simbolo }}{{ $a->exchange ? ' ('.$a->exchange.')' : '' }} · {{ $a->moneda }} · {{ \Illuminate\Support\Str::limit($a->nombre, 30) }}</option>
                    @endforeach
                </select>
                @error('asset_id') <div class="error">{{ $message }}</div> @enderror
                <div class="fila-campos">
                    <div>
                        <label for="fecha">Fecha</label>
                        <input id="fecha" type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                        @error('fecha') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="cierre">Cierre (moneda del activo)</label>
                        <input id="cierre" name="cierre" inputmode="decimal" value="{{ old('cierre') }}" required>
                        @error('cierre') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="actions"><button type="submit">Guardar cierre</button></div>
            </form>
        @elseif ($tab === 'indices')
            <form method="POST" action="{{ route('superadmin.precios.indices.valores.store') }}" class="narrow">
                @csrf
                <h2>Capturar valor de un índice</h2>
                @if ($indices->isEmpty())
                    <p class="muted">Primero crea un índice.</p>
                @else
                    <label for="benchmark_id">Índice</label>
                    <select id="benchmark_id" name="benchmark_id" required>
                        <option value="">Elige un índice…</option>
                        @foreach ($indices as $b)
                            <option value="{{ $b->id }}" @selected((int) old('benchmark_id', $indice) === $b->id)>{{ $b->nombre }}{{ $b->simbolo_proveedor ? ' · se sincroniza ('.$b->simbolo_proveedor.')' : ' · captura manual' }}</option>
                        @endforeach
                    </select>
                    @error('benchmark_id') <div class="error">{{ $message }}</div> @enderror
                    <div class="fila-campos">
                        <div>
                            <label for="fecha">Fecha</label>
                            <input id="fecha" type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                            @error('fecha') <div class="error">{{ $message }}</div> @enderror
                        </div>
                        <div>
                            <label for="valor">Valor</label>
                            <input id="valor" name="valor" inputmode="decimal" value="{{ old('valor') }}" required>
                            @error('valor') <div class="error">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <p class="help">Para una tasa como CETES captura un índice acumulado: por ejemplo, empieza en 100 y multiplícalo por (1 + rendimiento del periodo) en cada fecha.</p>
                    <div class="actions"><button type="submit">Guardar valor</button></div>
                @endif
            </form>

            <form method="POST" action="{{ route('superadmin.precios.indices.store') }}" class="narrow">
                @csrf
                <h2>Nuevo índice</h2>
                <label for="nombre">Nombre</label>
                <input id="nombre" name="nombre" maxlength="100" value="{{ old('nombre') }}" placeholder="CETES 28" required>
                @error('nombre') <div class="error">{{ $message }}</div> @enderror
                <label for="simbolo_proveedor">Símbolo en Yahoo Finance (opcional)</label>
                <input id="simbolo_proveedor" name="simbolo_proveedor" maxlength="40" value="{{ old('simbolo_proveedor') }}" placeholder="^MXX">
                @error('simbolo_proveedor') <div class="error">{{ $message }}</div> @enderror
                <p class="help">Con símbolo, la sincronización automática trae sus valores; sin símbolo, se capturan aquí.</p>
                <div class="actions"><button type="submit" class="btn btn-secondary">Crear índice</button></div>
            </form>
        @else
            <form method="POST" action="{{ route('superadmin.precios.tasas.store') }}" class="narrow">
                @csrf
                <h2>Capturar tipo de cambio</h2>
                <div class="fila-campos">
                    <div>
                        <label for="moneda_origen">De (1 unidad)</label>
                        <input id="moneda_origen" name="moneda_origen" maxlength="3" value="{{ old('moneda_origen', 'USD') }}" required>
                        @error('moneda_origen') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="moneda_destino">A</label>
                        <input id="moneda_destino" name="moneda_destino" maxlength="3" value="{{ old('moneda_destino', 'MXN') }}" required>
                        @error('moneda_destino') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="fecha">Fecha</label>
                        <input id="fecha" type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required>
                        @error('fecha') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label for="tasa">Tipo de cambio</label>
                        <input id="tasa" name="tasa" inputmode="decimal" value="{{ old('tasa') }}" required>
                        @error('tasa') <div class="error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <p class="help">Ejemplo: de USD a MXN 18.25 significa que 1 dólar vale 18.25 pesos.</p>
                <div class="actions"><button type="submit">Guardar tipo de cambio</button></div>
            </form>
        @endif

        <form method="POST" action="{{ route('superadmin.precios.importar') }}" enctype="multipart/form-data" class="narrow">
            @csrf
            <h2>Importar archivo</h2>
            <label for="archivo">CSV o Excel (.xlsx)</label>
            <input id="archivo" type="file" name="archivo" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
            @error('archivo') <div class="error">{{ $message }}</div> @enderror
            <p class="help">
                Precios: columnas <strong>simbolo, fecha, cierre</strong> (y <strong>exchange</strong> si hay símbolos repetidos).<br>
                Tipos de cambio: <strong>moneda_origen, moneda_destino, fecha, tasa</strong>.<br>
                Índices: <strong>indice, fecha, valor</strong> (el nombre del índice tal como aparece aquí).<br>
                Fechas AAAA-MM-DD o DD/MM/AAAA. Si una fila tiene error no se guarda nada. Hasta {{ number_format(\App\Modules\Prices\Services\PriceCaptureService::MAX_FILAS) }} filas.
            </p>
            <div class="actions"><button type="submit" class="btn btn-secondary">Importar</button></div>
        </form>
    </div>

    @if ($tab === 'precios')
        <h2>Cierres registrados</h2>
        <form method="GET" class="filters">
            <div style="flex:1;max-width:360px">
                <label for="filtro_activo">Activo</label>
                <select id="filtro_activo" name="asset_id">
                    <option value="">Todos</option>
                    @foreach ($activos as $a)
                        <option value="{{ $a->id }}" @selected($activo === $a->id)>{{ $a->simbolo }}{{ $a->exchange ? ' ('.$a->exchange.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
        @if ($precios->isEmpty())
            <p class="muted">No hay cierres registrados{{ $activo ? ' para este activo' : '' }}.</p>
        @else
            <table style="max-width:640px">
                <thead><tr><th>Fecha</th><th>Activo</th><th class="num">Cierre</th><th>Moneda</th></tr></thead>
                <tbody>
                    @foreach ($precios as $p)
                        <tr>
                            <td>{{ $p->fecha->format('d/m/Y') }}</td>
                            <td><strong>{{ $p->asset->simbolo }}</strong></td>
                            <td class="num">{{ $numero($p->cierre) }}</td>
                            <td>{{ $p->asset->moneda }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $precios->links('pagination::bootstrap-3') }}
        @endif
    @elseif ($tab === 'indices')
        <h2>Valores registrados</h2>
        <form method="GET" class="filters">
            <input type="hidden" name="tab" value="indices">
            <div style="flex:1;max-width:360px">
                <label for="filtro_indice">Índice</label>
                <select id="filtro_indice" name="benchmark_id">
                    <option value="">Todos</option>
                    @foreach ($indices as $b)
                        <option value="{{ $b->id }}" @selected($indice === $b->id)>{{ $b->nombre }} ({{ number_format($b->values_count) }} valores)</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
        </form>
        @if ($valores->isEmpty())
            <p class="muted">No hay valores registrados{{ $indice ? ' para este índice' : '' }}.</p>
        @else
            <table style="max-width:640px">
                <thead><tr><th>Fecha</th><th>Índice</th><th class="num">Valor</th></tr></thead>
                <tbody>
                    @foreach ($valores as $v)
                        <tr>
                            <td>{{ $v->fecha->format('d/m/Y') }}</td>
                            <td>{{ $v->benchmark->nombre }}</td>
                            <td class="num">{{ $numero($v->valor) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $valores->links('pagination::bootstrap-3') }}
        @endif
    @else
        <h2>Tipos de cambio registrados</h2>
        @if ($tasas->isEmpty())
            <p class="muted">No hay tipos de cambio registrados.</p>
        @else
            <table style="max-width:640px">
                <thead><tr><th>Fecha</th><th>De</th><th>A</th><th class="num">Tipo de cambio</th></tr></thead>
                <tbody>
                    @foreach ($tasas as $t)
                        <tr>
                            <td>{{ $t->fecha->format('d/m/Y') }}</td>
                            <td>{{ $t->moneda_origen }}</td>
                            <td>{{ $t->moneda_destino }}</td>
                            <td class="num">{{ $numero($t->tasa) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $tasas->links('pagination::bootstrap-3') }}
        @endif
    @endif
@endsection
