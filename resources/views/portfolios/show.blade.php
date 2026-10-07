@extends('layouts.app')

@section('title', $portfolio->nombre)

@php
    // Formato es-MX: miles con coma, decimales con punto.
    $dinero = fn ($v) => number_format((float) $v, 2, '.', ',');
    $cantidad = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    // Movimientos de efectivo que restan: se muestran con signo y en rojo.
    $salidas = ['RETIRO', 'COMISION'];
    $porcentaje = fn (?float $v) => $v === null ? '—' : number_format($v * 100, 2, '.', ',').' %';
    // Clase de color para ganancias (verde) y pérdidas (rojo).
    $signo = fn ($v) => (float) $v > 0 ? 'pos' : ((float) $v < 0 ? 'neg' : '');
@endphp

@section('content')
    <p class="muted"><a href="{{ route('portfolios.index') }}">Mis portafolios</a> /</p>
    <div class="page-head">
        <h1>{{ $portfolio->nombre }}</h1>
        <div class="actions">
            <a class="btn" href="{{ route('portfolios.operar', $portfolio) }}">Comprar o vender</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.transactions.create', $portfolio) }}">Otra transacción</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.importar', $portfolio) }}">Importar</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.rebalanceo', $portfolio) }}">Rebalanceo</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.reporte', $portfolio) }}">Reporte trimestral</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.edit', $portfolio) }}">Editar</a>
            <form method="POST" action="{{ route('portfolios.destroy', $portfolio) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary">Eliminar</button>
            </form>
        </div>
    </div>
    <p class="muted">Moneda base: {{ $portfolio->moneda_base }} · Benchmark: {{ $portfolio->benchmark?->nombre ?? '—' }}</p>

    @unless ($saldo['vacio'])
        @php
            $estado = $saldo['estado'];
            $abiertas = collect($estado['posiciones'])
                ->filter(fn ($p) => (float) $p['cantidad'] != 0)
                ->sortByDesc(fn ($p) => (float) $p['valor_base']);
            $valorTotal = (float) $estado['valor_total'];
        @endphp

        <h2>Saldo actual <span class="muted" style="font-weight:400">al {{ \Carbon\Carbon::parse($saldo['hasta'])->format('d/m/Y') }}</span></h2>
        <div class="tiles">
            <div class="tile">
                <div class="label">Valor total</div>
                <div class="value">{{ $dinero($estado['valor_total']) }}</div>
                <div class="sub muted">{{ $portfolio->moneda_base }}</div>
            </div>
            <div class="tile">
                <div class="label">Efectivo</div>
                <div class="value {{ (float) $estado['efectivo'] < 0 ? 'neg' : '' }}">{{ $dinero($estado['efectivo']) }}</div>
                <div class="sub muted">{{ $porcentaje($valorTotal > 0 ? (float) $estado['efectivo'] / $valorTotal : null) }} del total</div>
            </div>
            <div class="tile">
                <div class="label">Posiciones</div>
                <div class="value">{{ $dinero($estado['valor_posiciones']) }}</div>
                <div class="sub muted">{{ $abiertas->count() }} {{ $abiertas->count() === 1 ? 'activo' : 'activos' }}</div>
            </div>
            <div class="tile">
                <div class="label">Aportaciones netas</div>
                <div class="value">{{ $dinero($estado['aportaciones_netas']) }}</div>
                <div class="sub muted">depósitos − retiros</div>
            </div>
            <div class="tile">
                <div class="label">Ganancia / pérdida total</div>
                <div class="value {{ $signo($estado['gp_total']) }}">{{ $dinero($estado['gp_total']) }}</div>
                <div class="sub {{ $signo($saldo['rendimiento_total']) }}">{{ $porcentaje($saldo['rendimiento_total']) }}</div>
            </div>
        </div>
        <p class="muted">
            G/P no realizada: <span class="{{ $signo($estado['gp_no_realizada']) }}">{{ $dinero($estado['gp_no_realizada']) }}</span> ·
            G/P realizada: <span class="{{ $signo($estado['gp_realizada']) }}">{{ $dinero($estado['gp_realizada']) }}</span> ·
            Dividendos e intereses: {{ $dinero($estado['ingresos']) }}
        </p>

        @if ($abiertas->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Activo</th><th class="num">Cantidad</th><th class="num">Costo prom.</th><th class="num">Precio</th><th>Moneda</th>
                        <th class="num">Valor ({{ $portfolio->moneda_base }})</th><th class="num">G/P no realizada</th><th class="num">%</th><th class="num">Peso</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($abiertas as $p)
                        <tr>
                            <td><strong>{{ $p['simbolo'] }}</strong> <span class="muted">{{ \Illuminate\Support\Str::limit($p['nombre'], 30) }}</span></td>
                            <td class="num">{{ $cantidad($p['cantidad']) }}</td>
                            <td class="num">{{ $dinero($p['costo_promedio_local']) }}</td>
                            <td class="num">{{ $p['precio'] === null ? '—' : $dinero($p['precio']) }}</td>
                            <td>{{ $p['moneda'] }}</td>
                            <td class="num">{{ $dinero($p['valor_base']) }}</td>
                            <td class="num {{ $signo($p['gp_no_realizada']) }}">{{ $dinero($p['gp_no_realizada']) }}</td>
                            <td class="num {{ $signo($p['gp_no_realizada_pct']) }}">{{ $porcentaje($p['gp_no_realizada_pct']) }}</td>
                            <td class="num">{{ $porcentaje($valorTotal > 0 ? (float) $p['valor_base'] / $valorTotal : null) }}</td>
                            <td><a href="{{ route('portfolios.operar', [$portfolio, 'tipo' => 'VENTA', 'asset_id' => $p['asset_id']]) }}">Vender</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="help">Valuado con el último cierre registrado de cada activo; si no hay, con el precio de su última operación.</p>
        @endif
    @endunless

    <h2>Transacciones</h2>

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
