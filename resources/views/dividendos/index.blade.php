@extends('layouts.app')

@section('title', 'Dividendos')

@php
    $dinero = fn ($v) => number_format((float) $v, 2, '.', ',');
    $cantidad = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    $hoy = today()->toDateString();
    $anterior = $mes->subMonth()->format('Y-m');
    $siguiente = $mes->addMonth()->format('Y-m');
    // Total esperado del mes por moneda (los dividendos pueden venir en monedas distintas).
    $totales = collect($dividendos)->groupBy(fn ($d) => $d['dividendo']->moneda)
        ->map(fn ($grupo) => $grupo->reduce(fn ($s, $d) => bcadd($s, $d['total'], 6), '0'));
@endphp

@section('content')
    <style>
        .mes-nav { display: flex; align-items: center; gap: 12px; margin-bottom: 16px; }
        .mes-nav h2 { margin: 0; min-width: 160px; text-align: center; }
        .calendario { table-layout: fixed; }
        .calendario th { text-align: center; }
        .calendario td { height: 92px; vertical-align: top; padding: 6px; border-right: 1px solid var(--border); }
        .calendario td:last-child { border-right: none; }
        .calendario .dia { font-size: var(--fs-1); color: var(--muted); }
        .calendario td.hoy .dia { color: var(--text); font-weight: 700; }
        .calendario td.fuera { background: var(--bg-soft); }
        .calendario .pago { display: block; margin-top: 4px; padding: 2px 4px; font-size: var(--fs-1); border-left: 2px solid var(--ok); background: var(--ok-soft); border-radius: var(--radius-sm); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .portafolios-div { list-style: none; margin: 0; padding: 0; display: grid; gap: 4px; }
        /* En teléfono basta la lista: el calendario no cabe. */
        @media (max-width: 700px) { .calendario-caja { display: none; } }
    </style>

    <div class="page-head">
        <h1>Dividendos</h1>
    </div>
    <p class="muted">Captura los dividendos anunciados y ve cuánto esperas cobrar. Monto esperado = títulos que tenías al corte × dividendo por título; el corte es el día anterior a la fecha ex (o la fecha de pago si no la capturas). Cuando lo cobres, regístralo en el portafolio con "Registrar cobro".</p>

    <form method="POST" action="{{ route('dividendos.store') }}" class="filters">
        @csrf
        <div style="flex:1;min-width:180px">
            <label for="asset_id">Activo</label>
            <select id="asset_id" name="asset_id" required>
                <option value="">Elige un activo…</option>
                @foreach ($activos as $a)
                    <option value="{{ $a->id }}" @selected((int) old('asset_id') === $a->id)>{{ $a->simbolo }} · {{ $a->moneda }} · {{ \Illuminate\Support\Str::limit($a->nombre, 28) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="fecha_ex">Fecha ex (opcional)</label>
            <input id="fecha_ex" type="date" name="fecha_ex" value="{{ old('fecha_ex') }}">
        </div>
        <div>
            <label for="fecha_pago">Fecha de pago</label>
            <input id="fecha_pago" type="date" name="fecha_pago" value="{{ old('fecha_pago') }}" required>
        </div>
        <div>
            <label for="monto_por_titulo">Por título</label>
            <input id="monto_por_titulo" name="monto_por_titulo" inputmode="decimal" value="{{ old('monto_por_titulo') }}" required style="width:110px">
        </div>
        <div style="flex:1;min-width:160px">
            <label for="nota">Nota</label>
            <input id="nota" name="nota" maxlength="255" value="{{ old('nota') }}">
        </div>
        <button type="submit" class="btn btn-secondary">Agregar</button>
    </form>
    @foreach (['asset_id', 'fecha_ex', 'fecha_pago', 'monto_por_titulo', 'nota'] as $campo)
        @error($campo) <div class="error">{{ $message }}</div> @enderror
    @endforeach

    <div class="mes-nav">
        <a class="btn btn-secondary" href="{{ route('dividendos', ['mes' => $anterior]) }}" aria-label="Mes anterior">‹</a>
        <h2>{{ ucfirst($mes->locale('es')->translatedFormat('F Y')) }}</h2>
        <a class="btn btn-secondary" href="{{ route('dividendos', ['mes' => $siguiente]) }}" aria-label="Mes siguiente">›</a>
        @if ($mes->format('Y-m') !== now()->format('Y-m'))
            <a href="{{ route('dividendos') }}">Hoy</a>
        @endif
        @if ($totales->isNotEmpty())
            <span class="muted" style="margin-left:auto">Esperado en el mes:
                @foreach ($totales as $moneda => $total)
                    <strong class="pos">{{ $dinero($total) }} {{ $moneda }}</strong>@if (! $loop->last) · @endif
                @endforeach
            </span>
        @endif
    </div>

    <div class="calendario-caja">
        <table class="calendario">
            <thead><tr>@foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $d)<th>{{ $d }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach ($semanas as $semana)
                    <tr>
                        @foreach ($semana as $dia)
                            <td @class(['fuera' => $dia === null, 'hoy' => $dia === $hoy])>
                                @if ($dia !== null)
                                    <span class="dia">{{ (int) substr($dia, 8, 2) }}</span>
                                    @foreach ($por_dia[$dia] ?? [] as $d)
                                        <span class="pago" title="{{ $d['dividendo']->asset->simbolo }}: {{ $dinero($d['total']) }} {{ $d['dividendo']->moneda }}">
                                            <strong>{{ $d['dividendo']->asset->simbolo }}</strong> {{ $dinero($d['total']) }}
                                        </span>
                                    @endforeach
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <h2>Dividendos del mes</h2>
    @if ($dividendos === [])
        <p class="muted">No hay dividendos capturados con pago en este mes.</p>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr><th>Pago</th><th>Fecha ex</th><th>Activo</th><th class="num">Por título</th><th class="num">Títulos</th><th class="num">Esperado</th><th>Por portafolio</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($dividendos as $d)
                        @php $div = $d['dividendo']; @endphp
                        <tr>
                            <td>{{ $div->fecha_pago->format('d/m/Y') }}</td>
                            <td>{{ $div->fecha_ex?->format('d/m/Y') ?? '—' }}</td>
                            <td><strong>{{ $div->asset->simbolo }}</strong>@if ($div->nota) <span class="muted">{{ \Illuminate\Support\Str::limit($div->nota, 30) }}</span>@endif</td>
                            <td class="num">{{ $cantidad($div->monto_por_titulo) }} {{ $div->moneda }}</td>
                            <td class="num">{{ $cantidad($d['titulos']) }}</td>
                            <td class="num pos">{{ $dinero($d['total']) }} {{ $div->moneda }}</td>
                            <td>
                                @if ($d['portafolios'] === [])
                                    <span class="muted">Sin títulos al {{ \Carbon\Carbon::parse($d['corte'])->format('d/m/Y') }}</span>
                                @else
                                    <ul class="portafolios-div">
                                        @foreach ($d['portafolios'] as $p)
                                            <li>
                                                {{ $p['portfolio']->nombre }}: {{ $dinero($p['total']) }}
                                                @if ($p['registrado'])
                                                    <span class="chip abierto">registrado</span>
                                                @elseif ($div->fecha_pago->toDateString() <= $hoy)
                                                    <a href="{{ route('portfolios.transactions.create', [$p['portfolio'], 'tipo' => 'DIVIDENDO', 'fecha' => $div->fecha_pago->toDateString(), 'asset_id' => $div->asset_id, 'cantidad' => rtrim(rtrim($p['total'], '0'), '.'), 'nota' => 'Dividendo de '.$cantidad($div->monto_por_titulo).' '.$div->moneda.' por título']) }}">Registrar cobro</a>
                                                @else
                                                    <span class="muted">por cobrar</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td>
                                <x-confirmar :accion="route('dividendos.destroy', $div)" titulo="Eliminar dividendo"
                                    :mensaje="'Se quitará del calendario el dividendo de '.$div->asset->simbolo.' del '.$div->fecha_pago->format('d/m/Y').'. Las transacciones ya registradas no cambian.'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="help">Títulos al corte en todos tus portafolios. "Registrar cobro" abre el alta de transacción con el dividendo ya llenado; revisa el monto con lo que realmente te pagaron (impuestos retenidos, tipo de cambio).</p>
    @endif
@endsection
