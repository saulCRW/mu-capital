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
    <h1 style="margin-bottom: 4px">{{ $portfolio->nombre }}</h1>
    <p class="muted">
        Moneda base: {{ $portfolio->moneda_base }} · Benchmark: {{ $portfolio->benchmark?->nombre ?? '—' }}
        @unless ($saldo['vacio'])
            · <span id="vivo-fecha">al {{ \Carbon\Carbon::parse($saldo['hasta'])->format('d/m/Y') }}</span>
        @endunless
    </p>
    <div class="barra">
        <a class="btn" href="{{ route('portfolios.operar', $portfolio) }}">Comprar o vender</a>
        <a class="btn btn-secondary" href="{{ route('portfolios.transactions.create', $portfolio) }}">Otra transacción</a>
        <div class="grupo" role="group" aria-label="Herramientas del portafolio">
            <a class="btn btn-secondary" href="{{ route('portfolios.importar', $portfolio) }}">Importar</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.rebalanceo', $portfolio) }}">Rebalanceo</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.reporte', $portfolio) }}">Reporte trimestral</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.estado.csv', $portfolio) }}">Exportar CSV</a>
            <a class="btn btn-secondary" href="{{ route('portfolios.edit', $portfolio) }}">Editar</a>
        </div>
        <span class="empuja">
            <x-confirmar :accion="route('portfolios.destroy', $portfolio)" clase="btn btn-secondary" titulo="Eliminar portafolio"
                :mensaje="'Se borrará «'.$portfolio->nombre.'». No se puede deshacer. Solo se puede eliminar un portafolio sin transacciones.'" />
        </span>
    </div>

    @unless ($saldo['vacio'])
        @php
            $estado = $saldo['estado'];
            $abiertas = collect($estado['posiciones'])->filter(fn ($p) => (float) $p['cantidad'] != 0);
            $valorTotal = (float) $estado['valor_total'];
        @endphp
        <div class="tiles" id="saldo-vivo" data-url="{{ route('api.v1.mercado.cotizaciones', ['portfolio' => $portfolio->id]) }}"
             data-base="{{ $portfolio->moneda_base }}" data-efectivo="{{ $estado['efectivo'] }}" data-aportaciones="{{ $estado['aportaciones_netas'] }}"
             data-posiciones="{{ json_encode($abiertas->map(fn ($p) => array_intersect_key($p, array_flip(['asset_id', 'cantidad', 'costo_base', 'moneda', 'tasa', 'valor_base', 'gp_realizada', 'ingresos', 'invertido_base'])))->values()) }}">
            <div class="tile">
                <div class="label">Valor total</div>
                <div class="value cotizacion" data-vivo="total">{{ $dinero($estado['valor_total']) }}</div>
                <div class="sub muted">{{ $portfolio->moneda_base }}</div>
            </div>
            <div class="tile">
                <div class="label">Efectivo</div>
                <div class="value {{ (float) $estado['efectivo'] < 0 ? 'neg' : '' }}">{{ $dinero($estado['efectivo']) }}</div>
                <div class="sub muted"><span data-vivo="peso-efectivo">{{ $porcentaje($valorTotal > 0 ? (float) $estado['efectivo'] / $valorTotal : null) }}</span> del total</div>
            </div>
            <div class="tile">
                <div class="label">G/P total</div>
                <div class="value {{ $signo($estado['gp_total']) }}" data-vivo="gp">{{ $dinero($estado['gp_total']) }}</div>
                <div class="sub"><span class="{{ $signo($saldo['rendimiento_total']) }}" data-vivo="rendimiento">{{ $porcentaje($saldo['rendimiento_total']) }}</span> <span class="muted">sobre {{ $dinero($estado['aportaciones_netas']) }} aportado</span></div>
            </div>
            <div class="tile">
                <div class="label">Rendimiento anualizado</div>
                <div class="value {{ $signo($saldo['rendimiento_anualizado']) }}">{{ $porcentaje($saldo['rendimiento_anualizado']) }}</div>
                <div class="sub muted">desde el {{ \Carbon\Carbon::parse($saldo['primera'])->format('d/m/Y') }}</div>
            </div>
            <div class="tile">
                <div class="label">Holdings</div>
                <div class="value">{{ $abiertas->count() }}</div>
                <div class="sub muted">{{ $abiertas->count() === 1 ? 'posición abierta' : 'posiciones abiertas' }}</div>
            </div>
        </div>
        <p class="help" id="vivo-nota">Valuado con el último cierre registrado de cada activo; si no hay, con el precio de su última operación.</p>
    @endunless

    <x-tabs etiqueta="Secciones del portafolio">
        @foreach (\App\Modules\Portfolios\Requests\PortfolioShowRequest::VISTAS as $clave => $texto)
            <a href="{{ route('portfolios.show', [$portfolio, 'vista' => $clave]) }}" @if ($vista === $clave) aria-current="page" @endif>{{ $texto }}</a>
        @endforeach
    </x-tabs>

    @include('portfolios.partials.'.$vista)

    @unless ($saldo['vacio'])
        @include('portfolios.partials.saldo-vivo')
    @endunless
@endsection
