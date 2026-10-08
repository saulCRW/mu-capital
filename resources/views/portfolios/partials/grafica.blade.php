{{--
    Gráfica de rendimiento acumulado (TWR) del portafolio contra su benchmark, con
    TradingView Lightweight Charts (public/vendor, licencia Apache 2.0 con atribución).
    Los datos de cada rango ya vienen calculados de PerformanceChart; aquí solo se dibujan.
    Los colores salen de los tokens CSS y se actualizan al cambiar de tema.
--}}
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
    #grafica { height: 300px; }
</style>

<div class="panel">
    <div class="grafica-cabeza">
        <div class="leyenda" aria-live="polite">
            <span><i style="background: var(--serie-0)"></i>{{ $portfolio->nombre }} <strong data-leyenda="portafolio">—</strong></span>
            @if ($grafica['benchmark'])
                <span><i style="background: var(--serie-1)"></i>{{ $grafica['benchmark'] }} <strong data-leyenda="benchmark">—</strong></span>
            @endif
            <span data-leyenda="fecha"></span>
        </div>
        <div class="rangos" role="group" aria-label="Periodo">
            @foreach (\App\Modules\Reports\Services\PerformanceChart::RANGOS as $clave => $texto)
                <button type="button" data-rango="{{ $clave }}" aria-pressed="{{ $clave === 'YTD' ? 'true' : 'false' }}">{{ $texto }}</button>
            @endforeach
        </div>
    </div>
    <div id="grafica" role="img" aria-label="Rendimiento acumulado del portafolio contra el benchmark"></div>
    <p class="help">
        Rendimiento acumulado desde el inicio del periodo, sin el efecto de depósitos y retiros (TWR).
        Gráfica: TradingView Lightweight Charts™ · Copyright © TradingView, Inc. · <a href="https://www.tradingview.com/" target="_blank" rel="noopener">tradingview.com</a>
    </p>
</div>

<script type="application/json" id="grafica-datos">@json($grafica['rangos'])</script>
<script src="{{ asset('vendor/lightweight-charts-5.2.1/lightweight-charts.standalone.production.js') }}"></script>
<script>
    (() => {
        const contenedor = document.getElementById('grafica');
        if (!window.LightweightCharts) {
            contenedor.outerHTML = '<p class="error">No se pudo cargar la gráfica.</p>';
            return;
        }
        const rangos = JSON.parse(document.getElementById('grafica-datos').textContent);
        const pct = (v) => (v > 0 ? '+' : '') + (v * 100).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' %';
        const fecha = (t) => new Date(t + 'T12:00:00').toLocaleDateString('es-MX', { day: '2-digit', month: '2-digit', year: 'numeric' });
        const token = (nombre) => getComputedStyle(document.documentElement).getPropertyValue(nombre).trim();
        const aPuntos = (serie) => serie.map(([time, value]) => ({ time, value }));

        const chart = LightweightCharts.createChart(contenedor, {
            autoSize: true,
            localization: { locale: 'es-MX', priceFormatter: pct, timeFormatter: (t) => fecha(typeof t === 'string' ? t : `${t.year}-${String(t.month).padStart(2, '0')}-${String(t.day).padStart(2, '0')}`) },
            rightPriceScale: { scaleMargins: { top: 0.1, bottom: 0.1 } },
            timeScale: { fixLeftEdge: true, fixRightEdge: true },
            handleScroll: false,
            handleScale: false,
        });
        const lineas = {
            portafolio: chart.addSeries(LightweightCharts.LineSeries, { lineWidth: 2, priceLineVisible: false }),
            benchmark: chart.addSeries(LightweightCharts.LineSeries, { lineWidth: 2, priceLineVisible: false }),
        };

        function colores() {
            chart.applyOptions({
                layout: { background: { type: 'solid', color: token('--bg') }, textColor: token('--muted'), fontFamily: getComputedStyle(document.body).fontFamily, attributionLogo: true },
                grid: { vertLines: { visible: false }, horzLines: { color: token('--border') } },
                rightPriceScale: { borderColor: token('--border') },
                timeScale: { borderColor: token('--border') },
                crosshair: { vertLine: { color: token('--muted') }, horzLine: { color: token('--muted') } },
            });
            lineas.portafolio.applyOptions({ color: token('--serie-0') });
            lineas.benchmark.applyOptions({ color: token('--serie-1') });
        }

        // Leyenda: valores del último día del rango, o del día bajo el cursor.
        const leyenda = (nombre) => document.querySelector(`[data-leyenda="${nombre}"]`);
        let actual = null;
        function pintarLeyenda(dia) {
            for (const nombre of ['portafolio', 'benchmark']) {
                const el = leyenda(nombre);
                if (!el) continue;
                const serie = rangos[actual][nombre];
                const punto = dia ? serie.find(([t]) => t === dia) : serie[serie.length - 1];
                el.textContent = punto ? pct(punto[1]) : '—';
                el.className = punto && punto[1] > 0 ? 'pos' : (punto && punto[1] < 0 ? 'neg' : '');
            }
            const ultimo = rangos[actual].portafolio.at(-1);
            leyenda('fecha').textContent = dia ? fecha(dia) : (ultimo ? 'al ' + fecha(ultimo[0]) : '');
        }

        function mostrar(rango) {
            actual = rango;
            lineas.portafolio.setData(aPuntos(rangos[rango].portafolio));
            lineas.benchmark.setData(aPuntos(rangos[rango].benchmark));
            chart.timeScale().fitContent();
            document.querySelectorAll('[data-rango]').forEach((b) => b.setAttribute('aria-pressed', b.dataset.rango === rango ? 'true' : 'false'));
            pintarLeyenda(null);
        }

        chart.subscribeCrosshairMove((p) => pintarLeyenda(p.time ? (typeof p.time === 'string' ? p.time : `${p.time.year}-${String(p.time.month).padStart(2, '0')}-${String(p.time.day).padStart(2, '0')}`) : null));
        document.querySelectorAll('[data-rango]').forEach((b) => b.addEventListener('click', () => mostrar(b.dataset.rango)));
        new MutationObserver(colores).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

        colores();
        mostrar('YTD');
    })();
</script>
