{{--
    Gráfica de líneas con TradingView Lightweight Charts (public/vendor, Apache 2.0 con atribución).
    Cada serie: ['nombre' => ..., 'puntos' => [[AAAA-MM-DD, valor], ...]]; los colores son los
    tokens --serie-0, --serie-1… y se actualizan al cambiar de tema.
    formato: 'dinero' (1,234.56) o 'porcentaje' (+1.23 %).
    Uso: <x-grafica-lineas id="patrimonio" :series="$series" formato="dinero" etiqueta="..." />
--}}
@props(['id', 'series', 'formato' => 'dinero', 'etiqueta' => '', 'alto' => 300])
<div class="grafica-lineas">
    <div class="leyenda" aria-live="polite">
        @foreach ($series as $i => $serie)
            <span><i style="background: var(--serie-{{ $i }})"></i>{{ $serie['nombre'] }} <strong data-leyenda="{{ $id }}-{{ $i }}">—</strong></span>
        @endforeach
        <span data-leyenda="{{ $id }}-fecha"></span>
    </div>
    <div id="{{ $id }}" style="height: {{ $alto }}px" role="img" aria-label="{{ $etiqueta }}"></div>
    <p class="help">Gráfica: TradingView Lightweight Charts™ · Copyright © TradingView, Inc. · <a href="https://www.tradingview.com/" target="_blank" rel="noopener">tradingview.com</a></p>
</div>
<script type="application/json" id="{{ $id }}-datos">@json(array_map(fn ($s) => $s['puntos'], $series))</script>

@once
    <style>
        .grafica-lineas .leyenda { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 8px; font-size: var(--fs-1); color: var(--muted); }
        .grafica-lineas .leyenda span { display: inline-flex; align-items: center; gap: 6px; }
        .grafica-lineas .leyenda i { display: inline-block; width: 12px; height: 2px; }
        .grafica-lineas .leyenda strong { color: var(--text); font-variant-numeric: tabular-nums; }
    </style>
    <script src="{{ asset('vendor/lightweight-charts-5.2.1/lightweight-charts.standalone.production.js') }}"></script>
    <script>
        /** Dibuja una gráfica de líneas (componente grafica-lineas). */
        window.muGraficaLineas = function (id, formato) {
            const contenedor = document.getElementById(id);
            if (!window.LightweightCharts) {
                contenedor.outerHTML = '<p class="error">No se pudo cargar la gráfica.</p>';
                return;
            }
            const series = JSON.parse(document.getElementById(id + '-datos').textContent);
            const numero = (v, d = 2) => v.toLocaleString('es-MX', { minimumFractionDigits: d, maximumFractionDigits: d });
            const formatear = formato === 'porcentaje' ? (v) => (v > 0 ? '+' : '') + numero(v * 100) + ' %' : (v) => numero(v);
            const fecha = (t) => new Date(t + 'T12:00:00').toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const comoTexto = (t) => typeof t === 'string' ? t : `${t.year}-${String(t.month).padStart(2, '0')}-${String(t.day).padStart(2, '0')}`;
            const token = (nombre) => getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();

            const chart = LightweightCharts.createChart(contenedor, {
                autoSize: true,
                localization: { locale: 'es-MX', priceFormatter: formatear, timeFormatter: (t) => fecha(comoTexto(t)) },
                rightPriceScale: { scaleMargins: { top: 0.1, bottom: 0.1 } },
                timeScale: { fixLeftEdge: true, fixRightEdge: true },
                handleScroll: false,
                handleScale: false,
            });
            const lineas = series.map((puntos) => {
                const linea = chart.addSeries(LightweightCharts.LineSeries, { lineWidth: 2, priceLineVisible: false });
                linea.setData(puntos.map(([time, value]) => ({ time, value })));
                return linea;
            });

            function colores() {
                chart.applyOptions({
                    layout: { background: { type: 'solid', color: token('--bg') }, textColor: token('--muted'), fontFamily: getComputedStyle(document.body).fontFamily, attributionLogo: true },
                    grid: { vertLines: { visible: false }, horzLines: { color: token('--border') } },
                    rightPriceScale: { borderColor: token('--border') },
                    timeScale: { borderColor: token('--border') },
                    crosshair: { vertLine: { color: token('--muted') }, horzLine: { color: token('--muted') } },
                });
                lineas.forEach((linea, i) => linea.applyOptions({ color: token('--serie-' + i) || token('--muted') }));
            }

            // Leyenda: último valor, o el del día bajo el cursor.
            function leyenda(dia) {
                series.forEach((puntos, i) => {
                    const el = document.querySelector(`[data-leyenda="${id}-${i}"]`);
                    const punto = dia ? puntos.find(([t]) => t === dia) : puntos[puntos.length - 1];
                    el.textContent = punto ? formatear(punto[1]) : '—';
                    if (formato === 'porcentaje') {
                        el.className = punto && punto[1] > 0 ? 'pos' : (punto && punto[1] < 0 ? 'neg' : '');
                    }
                });
                const ultimo = series[0]?.at(-1);
                document.querySelector(`[data-leyenda="${id}-fecha"]`).textContent = dia ? fecha(dia) : (ultimo ? 'al ' + fecha(ultimo[0]) : '');
            }

            chart.subscribeCrosshairMove((p) => leyenda(p.time ? comoTexto(p.time) : null));
            new MutationObserver(colores).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
            colores();
            chart.timeScale().fitContent();
            leyenda(null);
        };
    </script>
@endonce
<script>window.muGraficaLineas(@json($id), @json($formato));</script>
