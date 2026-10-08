{{--
    Pestaña "Análisis": métricas de riesgo del periodo, heatmap de rendimiento mensual
    (todo el historial) y contribución de cada activo al rendimiento total.
--}}
@if ($saldo['vacio'])
    <p class="muted">Este portafolio todavía no tiene transacciones. Empieza con un DEPOSITO o importa tus movimientos.</p>
@else
    @php
        $m = $analisis['metricas'];
        $diferencia = $m['twr'] !== null && $m['benchmark'] !== null ? $m['twr'] - $m['benchmark'] : null;
        $decimal = fn (?float $v) => $v === null ? '—' : number_format($v, 2, '.', ',');
        $conSigno = fn (?float $v) => $v === null ? '—' : ($v > 0 ? '+' : '').$porcentaje($v);
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        // Intensidad del color proporcional al rendimiento; ±10 % o más es el tono más fuerte.
        $celda = function (?float $r) {
            if ($r === null || abs($r) < 0.00005) {
                return '';
            }
            $intensidad = (int) round(min(abs($r) / 0.10, 1) * 40) + 8;

            return 'background: color-mix(in srgb, var('.($r > 0 ? '--ok' : '--danger').') '.$intensidad.'%, transparent)';
        };
    @endphp
    <style>
        .periodos { display: inline-flex; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); overflow: hidden; margin-bottom: 16px; }
        .periodos a { padding: 5px 10px; font-size: var(--fs-1); color: var(--muted); text-decoration: none; background: var(--bg); }
        .periodos a + a { border-left: 1px solid var(--border-strong); }
        .periodos a:hover { background: var(--bg-hover); color: var(--text); }
        .periodos a[aria-current="page"] { background: var(--accent); color: var(--on-accent); }
        table.heatmap td, table.heatmap th { padding: 0 8px; }
        table.heatmap td.num { min-width: 64px; }
    </style>

    <nav class="periodos" aria-label="Periodo del análisis">
        @foreach (\App\Modules\Reports\Services\PerformanceChart::RANGOS as $clave => $texto)
            <a href="{{ route('portfolios.show', [$portfolio, 'vista' => 'analisis', 'periodo' => $clave]) }}" @if ($analisis['periodo'] === $clave) aria-current="page" @endif>{{ $texto }}</a>
        @endforeach
    </nav>
    <p class="muted" style="margin-top:0">Del {{ \Carbon\Carbon::parse($analisis['desde'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($analisis['hasta'])->format('d/m/Y') }}.</p>

    <h2 style="margin-top:0">Rendimiento</h2>
    <div class="tiles">
        <div class="tile">
            <div class="label">Rendimiento (TWR)</div>
            <div class="value {{ $signo($m['twr']) }}">{{ $conSigno($m['twr']) }}</div>
            <div class="sub muted">desempeño de las inversiones</div>
        </div>
        <div class="tile">
            <div class="label">Rendimiento del dinero (XIRR)</div>
            <div class="value {{ $signo($m['xirr']) }}">{{ $conSigno($m['xirr']) }}</div>
            <div class="sub muted">anual, con tus depósitos y retiros</div>
        </div>
        <div class="tile">
            <div class="label">Benchmark{{ $portfolio->benchmark ? ' ('.$portfolio->benchmark->nombre.')' : '' }}</div>
            <div class="value {{ $signo($m['benchmark']) }}">{{ $conSigno($m['benchmark']) }}</div>
            <div class="sub {{ $signo($diferencia) }}">{{ $diferencia === null ? 'sin benchmark' : $conSigno($diferencia).' de diferencia' }}</div>
        </div>
    </div>

    <h2>Riesgo</h2>
    <div class="tiles">
        <div class="tile">
            <div class="label">Volatilidad anualizada</div>
            <div class="value">{{ $porcentaje($m['volatilidad']) }}</div>
            <div class="sub muted">desv. estándar diaria × √252</div>
        </div>
        <div class="tile">
            <div class="label">Máximo drawdown</div>
            <div class="value {{ $signo($m['max_drawdown']) }}">{{ $porcentaje($m['max_drawdown']) }}</div>
            <div class="sub muted">peor caída desde un máximo</div>
        </div>
        <div class="tile">
            <div class="label">Beta</div>
            <div class="value">{{ $decimal($m['beta']) }}</div>
            <div class="sub muted">1 = se mueve como el benchmark</div>
        </div>
        <div class="tile">
            <div class="label">Sharpe</div>
            <div class="value">{{ $decimal($m['sharpe']) }}</div>
            <div class="sub muted">rendimiento extra por unidad de riesgo</div>
        </div>
        <div class="tile">
            <div class="label">Sortino</div>
            <div class="value">{{ $decimal($m['sortino']) }}</div>
            <div class="sub muted">como Sharpe, solo con caídas</div>
        </div>
    </div>
    <p class="help">
        Tasa libre de riesgo: {{ $porcentaje((float) auth()->user()->tasa_libre_riesgo) }} anual (cámbiala en <a href="{{ route('cuenta.edit') }}">Mi cuenta</a>).
        Las métricas usan los rendimientos diarios del periodo, sin el efecto de depósitos y retiros; con pocos días son poco representativas.
    </p>

    <h2>Rendimiento mensual</h2>
    @if ($analisis['heatmap'] === [])
        <p class="muted">Aparece cuando el portafolio tiene al menos dos días hábiles de historial.</p>
    @else
        <div style="overflow-x:auto">
            <table class="heatmap">
                <thead>
                    <tr><th>Año</th>@foreach ($meses as $mes)<th class="num">{{ $mes }}</th>@endforeach<th class="num">Año</th></tr>
                </thead>
                <tbody>
                    @foreach ($analisis['heatmap'] as $anio => $fila)
                        <tr>
                            <td>{{ $anio }}</td>
                            @for ($mes = 1; $mes <= 12; $mes++)
                                @php $r = $fila['meses'][$mes] ?? null; @endphp
                                <td class="num" style="{{ $celda($r) }}">{{ $r === null ? '' : $conSigno($r) }}</td>
                            @endfor
                            <td class="num" style="{{ $celda($fila['anio']) }}"><strong>{{ $conSigno($fila['anio']) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="help">TWR de cada mes (los días encadenados); verde ganancia, rojo pérdida, más intenso mientras más grande. Celda vacía: sin historial ese mes.</p>
    @endif

    <h2>Contribución por activo <span class="muted" style="font-weight:400">desde el inicio</span></h2>
    @if ($analisis['contribucion'] === [])
        <p class="muted">Todavía no hay posiciones.</p>
    @else
        <table style="max-width:640px">
            <thead><tr><th>Activo</th><th class="num">Aporte ({{ $portfolio->moneda_base }})</th><th class="num">Contribución</th></tr></thead>
            <tbody>
                @foreach ($analisis['contribucion'] as $c)
                    <tr>
                        <td>{!! $c['asset_id'] === null ? '<span class="muted">Otros (intereses y comisiones sin activo)</span>' : '<strong>'.e($c['simbolo']).'</strong>' !!}</td>
                        <td class="num {{ $signo($c['aporte']) }}">{{ $dinero($c['aporte']) }}</td>
                        <td class="num {{ $signo($c['contribucion']) }}">{{ $conSigno($c['contribucion']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="help">Aporte = G/P no realizada + G/P realizada + dividendos e intereses del activo. Contribución = aporte ÷ aportaciones netas; las contribuciones suman el rendimiento total del portafolio.</p>
    @endif
@endif
