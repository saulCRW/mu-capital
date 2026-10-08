@extends('layouts.app')

@section('title', 'Rebalanceo · '.$portfolio->nombre)

@php
    $dinero = fn ($v) => number_format(abs((float) $v), 2, '.', ',');
    $porcentaje = fn (?float $v) => $v === null ? '—' : number_format($v * 100, 2, '.', ',').' %';
    $puntos = fn (?float $v) => $v === null ? '—' : ($v > 0 ? '+' : '').number_format($v * 100, 2, '.', ',').' pp';
    $dimensiones = ['clase' => 'Clase de activo', 'sector' => 'Sector', 'moneda' => 'Moneda', 'pais' => 'País'];
    $etiqueta = fn (string $clave) => $analisis['dimension'] === 'clase' ? (\App\Modules\Assets\Models\Asset::ETIQUETAS[$clave] ?? $clave) : $clave;
    $hayObjetivo = $analisis['objetivos'] !== [];
    $fuera = collect($analisis['comparacion'])->where('fuera', true)->count();
@endphp

@section('content')
    <style>
        .tabs .punto { display: inline-block; width: 6px; height: 6px; border-radius: 3px; background: var(--muted); margin-left: 6px; vertical-align: middle; }
        .comparar { position: relative; height: 10px; min-width: 140px; background: var(--bg-soft); border-radius: var(--radius-sm); }
        .comparar .actual { position: absolute; left: 0; top: 0; bottom: 0; background: var(--serie-0); border-radius: var(--radius-sm); }
        .comparar .meta { position: absolute; top: -3px; bottom: -3px; width: 2px; background: var(--text); }
        .objetivos td.num input { width: 110px; text-align: right; }
        .suma { font-weight: 600; }
    </style>

    <p class="muted"><a href="{{ route('portfolios.show', $portfolio) }}">{{ $portfolio->nombre }}</a> /</p>
    <h1>Rebalanceo</h1>

    <x-tabs etiqueta="Dimensión">
        @foreach ($dimensiones as $clave => $titulo)
            <a href="{{ route('portfolios.rebalanceo', [$portfolio, 'dimension' => $clave]) }}" @if ($analisis['dimension'] === $clave) aria-current="page" @endif>
                {{ $titulo }}@if (in_array($clave, $analisis['con_objetivo'], true))<span class="punto" title="Tiene asignación objetivo"></span>@endif
            </a>
        @endforeach
    </x-tabs>

    @if ($analisis['vacio'])
        <p class="muted">El portafolio todavía no tiene posiciones ni efectivo.</p>
    @else
        <p class="muted">
            Valuado hoy en {{ $dinero($analisis['total']) }} {{ $portfolio->moneda_base }}. Banda de tolerancia: ±{{ $tolerancia * 100 }} puntos.
            @if (! $hayObjetivo)
                Este portafolio no tiene asignación objetivo por {{ mb_strtolower($dimensiones[$analisis['dimension']]) }}: defínela abajo.
            @elseif ($fuera === 0)
                <strong>Todos los grupos están dentro de la banda.</strong>
            @else
                <strong class="warn">{{ $fuera }} {{ $fuera === 1 ? 'grupo está' : 'grupos están' }} fuera de la banda.</strong>
            @endif
        </p>

        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        <th>{{ $dimensiones[$analisis['dimension']] }}</th><th class="num">Valor</th><th class="num">Actual</th><th class="num">Objetivo</th>
                        <th style="width:180px">Actual vs. objetivo</th><th class="num">Desviación</th><th class="num">Para volver al objetivo</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($analisis['comparacion'] as $f)
                        <tr>
                            <td>{{ $etiqueta($f['clave']) }}</td>
                            <td class="num">{{ $dinero($f['valor']) }}</td>
                            <td class="num">{{ $porcentaje($f['actual']) }}</td>
                            <td class="num">{{ $porcentaje($f['objetivo']) }}</td>
                            <td>
                                <div class="comparar" role="img" aria-label="Actual {{ $porcentaje($f['actual']) }}, objetivo {{ $porcentaje($f['objetivo']) }}">
                                    <div class="actual" style="width: {{ min(100, round($f['actual'] * 100, 1)) }}%"></div>
                                    @if ($f['objetivo'] !== null)
                                        <div class="meta" style="left: calc({{ min(100, round($f['objetivo'] * 100, 1)) }}% - 1px)"></div>
                                    @endif
                                </div>
                            </td>
                            <td class="num {{ $f['fuera'] ? 'neg' : '' }}">{{ $puntos($f['desviacion']) }}</td>
                            <td class="num">
                                @if ($f['ajuste'] === null || abs((float) $f['ajuste']) < 0.01)
                                    —
                                @else
                                    {{ (float) $f['ajuste'] > 0 ? 'Comprar' : 'Vender' }} {{ $dinero($f['ajuste']) }}
                                @endif
                            </td>
                            <td>@if ($f['fuera'])<span class="chip alerta">Fuera de banda</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="help">Barra azul: peso actual. Línea negra: objetivo. Montos en {{ $portfolio->moneda_base }}.</p>

        @if ($analisis['operaciones'] !== [] || $analisis['sin_activos'] !== [])
            <h2>Operaciones sugeridas</h2>
            <p class="muted">Para volver todos los grupos al objetivo. Los títulos se calculan con el último precio y se redondean hacia abajo; revisa el precio real al operar.</p>
            @if ($analisis['operaciones'] !== [])
                <table>
                    <thead>
                        <tr><th>Operación</th><th>Activo</th><th>Grupo</th><th class="num">Títulos</th><th class="num">Monto aprox. ({{ $portfolio->moneda_base }})</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($analisis['operaciones'] as $o)
                            <tr>
                                <td class="{{ $o['tipo'] === 'VENTA' ? 'neg' : 'pos' }}">{{ $o['tipo'] === 'VENTA' ? 'Vender' : 'Comprar' }}</td>
                                <td><strong>{{ $o['simbolo'] }}</strong></td>
                                <td>{{ $etiqueta($o['grupo']) }}</td>
                                <td class="num">{{ rtrim(rtrim(number_format((float) $o['titulos'], 6, '.', ','), '0'), '.') }}</td>
                                <td class="num">{{ $dinero($o['monto']) }}</td>
                                <td><a href="{{ route('portfolios.operar', [$portfolio, 'tipo' => $o['tipo'], 'asset_id' => $o['asset_id'], 'cantidad' => $o['titulos']]) }}">Operar</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            @foreach ($analisis['sin_activos'] as $s)
                <p class="help">
                    {{ $etiqueta($s['clave']) }}: {{ (float) $s['ajuste'] > 0 ? 'faltan' : 'sobran' }} {{ $dinero($s['ajuste']) }} {{ $portfolio->moneda_base }}
                    {{ (float) $s['ajuste'] > 0 ? 'y el portafolio no tiene activos de este grupo: elige cuál comprar.' : '(se ajusta con las demás operaciones).' }}
                </p>
            @endforeach
        @endif
    @endif

    <h2>Asignación objetivo por {{ mb_strtolower($dimensiones[$analisis['dimension']]) }}</h2>
    <form method="POST" action="{{ route('portfolios.rebalanceo.update', $portfolio) }}" id="objetivos" style="max-width:520px">
        @csrf
        @method('PUT')
        <input type="hidden" name="dimension" value="{{ $analisis['dimension'] }}">
        <table class="objetivos">
            <tbody>
                @foreach ($analisis['claves'] as $clave)
                    <tr>
                        <td><label for="obj-{{ $loop->index }}" style="margin:0;font-weight:400;font-size:14px">{{ $etiqueta($clave) }}</label></td>
                        <td class="num">
                            <input id="obj-{{ $loop->index }}" name="objetivos[{{ $clave }}]" inputmode="decimal" placeholder="0"
                                   value="{{ old('objetivos.'.$clave, isset($analisis['objetivos'][$clave]) ? rtrim(rtrim($analisis['objetivos'][$clave], '0'), '.') : '') }}"> %
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td><input name="nueva_clave" placeholder="Otro grupo (opcional)" value="{{ old('nueva_clave') }}" maxlength="100" aria-label="Nombre de otro grupo"></td>
                    <td class="num"><input name="nuevo_porcentaje" inputmode="decimal" placeholder="0" value="{{ old('nuevo_porcentaje') }}" aria-label="Porcentaje del otro grupo"> %</td>
                </tr>
            </tbody>
            <tfoot>
                <tr><td>Suma</td><td class="num suma" id="suma">—</td></tr>
            </tfoot>
        </table>
        @error('objetivos') <div class="error">{{ $message }}</div> @enderror
        @foreach ($errors->get('objetivos.*') as $mensajes)
            <div class="error">{{ $mensajes[0] }}</div>
        @endforeach
        <p class="help">Deben sumar 100 %. Deja todo en blanco y guarda para quitar el objetivo de esta dimensión.</p>
        <div class="actions" style="margin-top:12px">
            <button type="submit">Guardar objetivo</button>
        </div>
    </form>

    <script>
        (() => {
            const form = document.getElementById('objetivos');
            const suma = document.getElementById('suma');
            const calcular = () => {
                const total = [...form.querySelectorAll('input[name^="objetivos"], input[name="nuevo_porcentaje"]')]
                    .reduce((s, i) => s + (parseFloat(i.value) || 0), 0);
                suma.textContent = total.toLocaleString('es-MX', { maximumFractionDigits: 4 }) + ' %';
                suma.classList.toggle('neg', total > 0 && Math.abs(total - 100) > 0.0001);
            };
            form.addEventListener('input', calcular);
            calcular();
        })();
    </script>
@endsection
