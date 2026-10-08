@extends('layouts.app')

@section('title', $asset->simbolo.' · '.$portfolio->nombre)

@php
    // Formato es-MX: miles con coma, decimales con punto.
    $dinero = fn ($v) => number_format((float) $v, 2, '.', ',');
    $cantidad = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    $porcentaje = fn (?float $v) => $v === null ? '—' : number_format($v * 100, 2, '.', ',').' %';
    // Clase de color para ganancias (verde) y pérdidas (rojo).
    $signo = fn ($v) => (float) $v > 0 ? 'pos' : ((float) $v < 0 ? 'neg' : '');
    $p = $posicion;
    $abierta = (float) $p['cantidad'] != 0;
@endphp

@section('content')
    <p class="muted">
        <a href="{{ route('portfolios.index') }}">Mis portafolios</a> /
        <a href="{{ route('portfolios.show', [$portfolio, 'vista' => 'holdings']) }}">{{ $portfolio->nombre }}</a> /
    </p>
    <div class="page-head">
        <h1>{{ $asset->simbolo }} <span class="muted" style="font-weight:400">{{ $asset->nombre }}</span></h1>
        <div class="actions">
            <a class="btn" href="{{ route('portfolios.operar', [$portfolio, 'tipo' => 'COMPRA', 'asset_id' => $asset->id]) }}">Comprar</a>
            @if ($abierta)
                <a class="btn btn-secondary" href="{{ route('portfolios.operar', [$portfolio, 'tipo' => 'VENTA', 'asset_id' => $asset->id]) }}">Vender</a>
            @endif
        </div>
    </div>
    <p class="muted">
        {{ \App\Modules\Assets\Models\Asset::ETIQUETAS[$asset->tipo] ?? $asset->tipo }} · {{ $asset->moneda }}
        @if ($asset->exchange) · {{ $asset->exchange }} @endif
        @if ($asset->sector) · {{ $asset->sector }} @endif
        · al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}
    </p>

    <div class="tiles">
        <div class="tile">
            <div class="label">Títulos</div>
            <div class="value">{{ $cantidad($p['cantidad']) }}</div>
            <div class="sub muted">{{ $abierta ? 'posición abierta' : 'posición vendida' }}</div>
        </div>
        <div class="tile">
            <div class="label">Costo promedio</div>
            <div class="value">{{ $p['costo_promedio_local'] === null ? '—' : $dinero($p['costo_promedio_local']) }}</div>
            <div class="sub muted">{{ $p['moneda'] }} por título, con comisiones</div>
        </div>
        <div class="tile">
            <div class="label">Último precio</div>
            <div class="value">{{ $p['precio'] === null ? '—' : $dinero($p['precio']) }}</div>
            <div class="sub muted">{{ $p['moneda'] }}</div>
        </div>
        <div class="tile">
            <div class="label">Valor de mercado</div>
            <div class="value">{{ $dinero($p['valor_base']) }}</div>
            <div class="sub muted">{{ $portfolio->moneda_base }} · {{ $porcentaje($peso) }} del portafolio</div>
        </div>
        <div class="tile">
            <div class="label">G/P no realizada</div>
            <div class="value {{ $signo($p['gp_no_realizada']) }}">{{ $dinero($p['gp_no_realizada']) }}</div>
            <div class="sub {{ $signo($p['gp_no_realizada_pct']) }}">{{ $porcentaje($p['gp_no_realizada_pct']) }}</div>
        </div>
        <div class="tile">
            <div class="label">G/P realizada</div>
            <div class="value {{ $signo($p['gp_realizada']) }}">{{ $dinero($p['gp_realizada']) }}</div>
            <div class="sub muted">{{ $portfolio->moneda_base }}</div>
        </div>
        <div class="tile">
            <div class="label">Dividendos e intereses</div>
            <div class="value">{{ $dinero($p['ingresos']) }}</div>
            <div class="sub muted">{{ $portfolio->moneda_base }} cobrados</div>
        </div>
        <div class="tile">
            <div class="label">Rendimiento</div>
            <div class="value {{ $signo($p['rendimiento_pct']) }}">{{ $porcentaje($p['rendimiento_pct']) }}</div>
            <div class="sub muted">sobre {{ $dinero($p['invertido_base']) }} invertido</div>
        </div>
    </div>
    <p class="help">Valuado con el último cierre registrado; si no hay, con el precio de la última operación. Rendimiento = (G/P no realizada + realizada + dividendos e intereses) ÷ todo lo invertido en el activo.</p>

    <h2>Precio</h2>
    <style>
        .grafica-cabeza { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .rangos { display: inline-flex; border: 1px solid var(--border-strong); border-radius: var(--radius-sm); overflow: hidden; }
        .rangos button { height: 28px; padding: 0 10px; border: none; border-radius: 0; background: var(--bg); color: var(--muted); font-weight: 500; font-size: var(--fs-1); }
        .rangos button + button { border-left: 1px solid var(--border-strong); }
        .rangos button:hover { background: var(--bg-hover); color: var(--text); }
        .rangos button[aria-pressed="true"] { background: var(--accent); color: var(--on-accent); }
        .leyenda { display: flex; flex-wrap: wrap; gap: 16px; font-size: var(--fs-1); color: var(--muted); }
        .leyenda span { display: inline-flex; align-items: center; gap: 6px; }
        .leyenda i { display: inline-block; width: 12px; height: 2px; }
        .leyenda strong { color: var(--text); font-variant-numeric: tabular-nums; }
        #grafica-activo { height: 340px; }
    </style>
    <div class="panel">
        @if ($grafica['cierres'] === [])
            <p class="muted">Sin cierres registrados para {{ $asset->simbolo }} desde su primera operación. Se agregan con la sincronización de mercado o en la pantalla Precios.</p>
        @else
            <div class="grafica-cabeza">
                <div class="leyenda" aria-live="polite">
                    <span><i style="background: var(--serie-0)"></i>Cierre <strong data-leyenda="precio">—</strong> {{ $asset->moneda }}</span>
                    @if ($grafica['costo'] !== null)
                        <span><i style="background: var(--muted)"></i>Costo promedio <strong>{{ $dinero($grafica['costo']) }}</strong></span>
                    @endif
                    <span data-leyenda="fecha"></span>
                    <span data-leyenda="operaciones"></span>
                </div>
                <div class="rangos" role="group" aria-label="Periodo">
                    @foreach (\App\Modules\Reports\Services\PerformanceChart::RANGOS as $clave => $texto)
                        <button type="button" data-rango="{{ $clave }}" aria-pressed="{{ $clave === 'TODO' ? 'true' : 'false' }}">{{ $texto }}</button>
                    @endforeach
                </div>
            </div>
            <div id="grafica-activo" role="img" aria-label="Precio de cierre de {{ $asset->simbolo }} con sus compras, ventas y dividendos"></div>
            <p class="help">
                ▲ compra · ▼ venta · ● dividendo. Pasa el cursor por un día para ver sus operaciones.
                Gráfica: TradingView Lightweight Charts™ · Copyright © TradingView, Inc. · <a href="https://www.tradingview.com/" target="_blank" rel="noopener">tradingview.com</a>
            </p>
        @endif
    </div>

    <h2>Transacciones</h2>
    <table>
        <thead>
            <tr>
                <th>Fecha</th><th>Tipo</th><th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Comisión</th>
                <th>Moneda</th><th class="num">Tipo cambio</th><th>Nota</th><th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transacciones as $t)
                <tr>
                    <td class="num" style="text-align:left">{{ $t->fecha->format('d/m/Y') }}</td>
                    <td>{{ $t->tipo }}</td>
                    <td class="num">{{ $cantidad($t->cantidad) }}</td>
                    <td class="num">{{ in_array($t->tipo, ['COMPRA', 'VENTA'], true) ? $dinero($t->precio) : '—' }}</td>
                    <td class="num">{{ (float) $t->comision ? $dinero($t->comision) : '—' }}</td>
                    <td>{{ $t->moneda }}</td>
                    <td class="num">{{ $t->moneda === $portfolio->moneda_base ? '—' : $cantidad($t->tipo_cambio) }}</td>
                    <td class="muted">{{ \Illuminate\Support\Str::limit($t->nota, 40) }}</td>
                    <td><a href="{{ route('transactions.edit', $t) }}">Editar</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Dividendos esperados</h2>
    @if ($dividendos === [])
        <p class="muted">No hay dividendos de {{ $asset->simbolo }} capturados del último mes en adelante. Se capturan en <a href="{{ route('dividendos') }}">Dividendos</a>.</p>
    @else
        <table>
            <thead>
                <tr><th>Fecha ex</th><th>Fecha de pago</th><th class="num">Por título</th><th class="num">Títulos al corte</th><th class="num">Esperado</th><th></th></tr>
            </thead>
            <tbody>
                @foreach ($dividendos as $d)
                    @php
                        $div = $d['dividendo'];
                        $fila = $d['portafolios'][0] ?? null;
                    @endphp
                    <tr>
                        <td class="num" style="text-align:left">{{ $div->fecha_ex?->format('d/m/Y') ?? '—' }}</td>
                        <td class="num" style="text-align:left">{{ $div->fecha_pago->format('d/m/Y') }}</td>
                        <td class="num">{{ $cantidad($div->monto_por_titulo) }} {{ $div->moneda }}</td>
                        <td class="num">{{ $cantidad($d['titulos']) }}</td>
                        <td class="num">{{ $dinero($d['total']) }} {{ $div->moneda }}</td>
                        <td>
                            @if ($fila === null)
                                <span class="muted">sin títulos al corte</span>
                            @elseif ($fila['registrado'])
                                <span class="chip abierto">registrado</span>
                            @elseif ($div->fecha_pago->toDateString() <= $hasta)
                                <a href="{{ route('portfolios.transactions.create', [$portfolio, 'tipo' => 'DIVIDENDO', 'fecha' => $div->fecha_pago->toDateString(), 'asset_id' => $asset->id, 'cantidad' => rtrim(rtrim($fila['total'], '0'), '.'), 'nota' => 'Dividendo de '.$cantidad($div->monto_por_titulo).' '.$div->moneda.' por título']) }}">Registrar cobro</a>
                            @else
                                <span class="muted">por cobrar</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="help">Esperado = títulos al corte en este portafolio × dividendo por título; el corte es el día anterior a la fecha ex (o la fecha de pago si no la capturaste).</p>
    @endif

    @if ($grafica['cierres'] !== [])
        <script type="application/json" id="grafica-activo-datos">@json($grafica)</script>
        <script src="{{ asset('vendor/lightweight-charts-5.2.1/lightweight-charts.standalone.production.js') }}"></script>
        <script>
            (() => {
                const contenedor = document.getElementById('grafica-activo');
                if (!window.LightweightCharts) {
                    contenedor.outerHTML = '<p class="error">No se pudo cargar la gráfica.</p>';
                    return;
                }
                const datos = JSON.parse(document.getElementById('grafica-activo-datos').textContent);
                const numero = (v) => v.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
                const fecha = (t) => new Date(t + 'T12:00:00').toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
                const comoTexto = (t) => typeof t === 'string' ? t : `${t.year}-${String(t.month).padStart(2, '0')}-${String(t.day).padStart(2, '0')}`;
                const token = (nombre) => getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
                const leyenda = (nombre) => document.querySelector(`[data-leyenda="${nombre}"]`);

                const chart = LightweightCharts.createChart(contenedor, {
                    autoSize: true,
                    localization: { locale: 'es-MX', priceFormatter: numero, timeFormatter: (t) => fecha(comoTexto(t)) },
                    rightPriceScale: { scaleMargins: { top: 0.12, bottom: 0.12 } },
                    timeScale: { fixLeftEdge: true, fixRightEdge: true },
                    handleScroll: false,
                    handleScale: false,
                });
                const linea = chart.addSeries(LightweightCharts.LineSeries, { lineWidth: 2, priceLineVisible: false });
                const marcas = LightweightCharts.createSeriesMarkers(linea, []);
                const costo = datos.costo === null ? null : linea.createPriceLine({
                    price: datos.costo, lineWidth: 1, lineStyle: LightweightCharts.LineStyle.Dashed, axisLabelVisible: true, title: 'Costo prom.',
                });

                // Una operación en día sin cierre (fin de semana, feriado) se marca en el siguiente cierre; si no lo hay, en el último.
                let puntos = [];
                let operaciones = {};
                function colocar(desde) {
                    puntos = datos.cierres.filter(([t]) => desde === null || t >= desde);
                    operaciones = {};
                    for (const m of datos.marcadores) {
                        if (desde !== null && m.fecha < desde) continue;
                        const dia = (puntos.find(([t]) => t >= m.fecha) ?? puntos.at(-1))?.[0];
                        if (dia) (operaciones[dia] ??= []).push(m);
                    }
                }

                function marcadores() {
                    const estilos = {
                        COMPRA: { position: 'belowBar', shape: 'arrowUp', color: token('--ok') },
                        VENTA: { position: 'aboveBar', shape: 'arrowDown', color: token('--danger') },
                        DIVIDENDO: { position: 'aboveBar', shape: 'circle', color: token('--serie-1') },
                    };
                    return Object.keys(operaciones).sort().flatMap((dia) => operaciones[dia].map((m) => ({ time: dia, ...estilos[m.tipo] })));
                }

                function colores() {
                    chart.applyOptions({
                        layout: { background: { type: 'solid', color: token('--bg') }, textColor: token('--muted'), fontFamily: getComputedStyle(document.body).fontFamily, attributionLogo: true },
                        grid: { vertLines: { visible: false }, horzLines: { color: token('--border') } },
                        rightPriceScale: { borderColor: token('--border') },
                        timeScale: { borderColor: token('--border') },
                        crosshair: { vertLine: { color: token('--muted') }, horzLine: { color: token('--muted') } },
                    });
                    linea.applyOptions({ color: token('--serie-0') });
                    costo?.applyOptions({ color: token('--muted') });
                    marcas.setMarkers(marcadores());
                }

                // Leyenda: último cierre del rango, o el del día bajo el cursor con sus operaciones.
                function pintarLeyenda(dia) {
                    const punto = dia ? puntos.find(([t]) => t === dia) : puntos.at(-1);
                    leyenda('precio').textContent = punto ? numero(punto[1]) : '—';
                    leyenda('fecha').textContent = dia ? fecha(dia) : (punto ? 'al ' + fecha(punto[0]) : '');
                    leyenda('operaciones').textContent = dia && operaciones[dia]
                        ? operaciones[dia].map((m) => m.texto + (m.fecha !== dia ? ' (' + fecha(m.fecha) + ')' : '')).join(' · ')
                        : '';
                }

                function mostrar(rango) {
                    colocar(datos.cortes[rango]);
                    linea.setData(puntos.map(([time, value]) => ({ time, value })));
                    marcas.setMarkers(marcadores());
                    chart.timeScale().fitContent();
                    document.querySelectorAll('[data-rango]').forEach((b) => b.setAttribute('aria-pressed', b.dataset.rango === rango ? 'true' : 'false'));
                    pintarLeyenda(null);
                }

                chart.subscribeCrosshairMove((p) => pintarLeyenda(p.time ? comoTexto(p.time) : null));
                document.querySelectorAll('[data-rango]').forEach((b) => b.addEventListener('click', () => mostrar(b.dataset.rango)));
                new MutationObserver(colores).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

                mostrar('TODO');
                colores();
            })();
        </script>
    @endif
@endsection
