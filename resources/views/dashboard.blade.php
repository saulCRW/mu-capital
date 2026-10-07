@extends('layouts.app')

@section('title', 'Inicio')

@php
    $dinero = fn ($v) => $v === null ? '—' : number_format((float) $v, 2, '.', ',');
    $porcentaje = fn (?float $v) => $v === null ? '—' : number_format($v * 100, 2, '.', ',').' %';
    $signo = fn ($v) => $v === null ? '' : ((float) $v > 0 ? 'pos' : ((float) $v < 0 ? 'neg' : ''));
@endphp

@section('content')
    <div class="page-head">
        <h1>Inicio</h1>
        @unless ($dashboard['vacio'])
            <span class="muted">Al {{ \Carbon\Carbon::parse($dashboard['hoy'])->format('d/m/Y') }}</span>
        @endunless
    </div>

    @if ($dashboard['vacio'])
        <p class="muted">Todavía no tienes portafolios. Crea uno y registra tu primer depósito, o importa tus movimientos desde un CSV.</p>
        <a class="btn" href="{{ route('portfolios.create') }}">Nuevo portafolio</a>
    @else
        @php
            $total = $dashboard['total'];
            $moneda = $dashboard['moneda'];
            $evolucion = $dashboard['evolucion'];
            $grafica = \App\Modules\Reports\Services\SvgLineChart::render([
                ['nombre' => 'Tu patrimonio', 'puntos' => $evolucion['valor']],
                ['nombre' => 'Capital aportado', 'puntos' => $evolucion['aportado']],
            ], 'dinero', 'Tu patrimonio y capital aportado en '.$dashboard['anio']);
        @endphp

        <div class="tiles">
            <div class="tile">
                <div class="label">Tu patrimonio</div>
                <div class="value">{{ $dinero($total['valor_final'] ?? null) }}</div>
                <div class="sub muted">{{ $moneda }} · {{ count($dashboard['portafolios']) }} {{ count($dashboard['portafolios']) === 1 ? 'portafolio' : 'portafolios' }}</div>
            </div>
            <div class="tile">
                <div class="label">Aportaciones netas {{ $dashboard['anio'] }}</div>
                <div class="value">{{ $dinero($total['flujo_neto'] ?? null) }}</div>
                <div class="sub muted">depósitos − retiros</div>
            </div>
            <div class="tile">
                <div class="label">Ganancia {{ $dashboard['anio'] }}</div>
                <div class="value {{ $signo($total['ganancia'] ?? null) }}">{{ $dinero($total['ganancia'] ?? null) }}</div>
                <div class="sub muted">{{ $moneda }}</div>
            </div>
            <div class="tile">
                <div class="label">Rendimiento {{ $dashboard['anio'] }} (TWR)</div>
                <div class="value {{ $signo($total['twr'] ?? null) }}">{{ $porcentaje($total['twr'] ?? null) }}</div>
                <div class="sub muted">todos los portafolios juntos</div>
            </div>
        </div>
        @if ($dashboard['varias_monedas'])
            <p class="help">Totales en {{ $moneda }}: los portafolios en otra moneda se convierten al tipo de cambio de cada día, así que la ganancia incluye el efecto cambiario.</p>
        @endif
        @if ($dashboard['sin_conversion'] !== [])
            <p class="help">Sin tipo de cambio a {{ $moneda }} para {{ implode(', ', $dashboard['sin_conversion']) }}: esos portafolios no entran en los totales.</p>
        @endif

        @if ($grafica !== '')
            <h2>Evolución {{ $dashboard['anio'] }} <span class="muted" style="font-weight:400">en {{ $moneda }}</span></h2>
            <div class="chart" style="max-width:820px">{!! $grafica !!}</div>
            @include('layouts.partials.chart-hover')
        @endif

        <h2>Portafolios</h2>
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
        <th>Portafolio</th><th class="num">Valor</th><th>Moneda</th><th class="num">Peso</th>
                        <th class="num">Rend. {{ $dashboard['anio'] }}</th><th class="num">Benchmark</th><th class="num">Diferencia</th>
                        <th class="num">Ganancia {{ $dashboard['anio'] }}</th><th>Último movimiento</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dashboard['portafolios'] as $f)
                        @php
                            $p = $f['portfolio'];
                            $diferencia = $f['twr'] !== null && $f['benchmark'] !== null ? $f['twr'] - $f['benchmark'] : null;
                        @endphp
                        <tr>
                            <td><a href="{{ route('portfolios.show', $p) }}">{{ $p->nombre }}</a></td>
                            <td class="num">{{ $dinero($f['valor']) }}</td>
                            <td>{{ $p->moneda_base }}</td>
                            <td class="num">{{ $porcentaje($f['valor_referencia'] !== null && (float) ($total['valor_final'] ?? 0) > 0 ? (float) $f['valor_referencia'] / (float) $total['valor_final'] : null) }}</td>
                            <td class="num {{ $signo($f['twr']) }}">{{ $porcentaje($f['twr']) }}</td>
                            <td class="num">{{ $porcentaje($f['benchmark']) }}</td>
                            <td class="num {{ $signo($diferencia) }}">{{ $porcentaje($diferencia) }}</td>
                            <td class="num {{ $signo($f['ganancia']) }}">{{ $dinero($f['ganancia']) }}</td>
                            <td>{{ $f['ultimo_movimiento'] ? \Carbon\Carbon::parse($f['ultimo_movimiento'])->format('d/m/Y') : '—' }}</td>
                            <td><a href="{{ route('portfolios.reporte', $p) }}">Reporte</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
