{{-- Tooltip de las gráficas SVG (App\Modules\Reports\Services\SvgLineChart): cursor vertical y valores de todas las series en la fecha más cercana. --}}
<style>
    .chart { position: relative; }
    .chart svg { display: block; touch-action: pan-y; }
    .chart-tip { position: absolute; top: 28px; z-index: 2; min-width: 150px; padding: 8px 10px; background: var(--bg); border: 1px solid var(--border); border-radius: 6px; box-shadow: 0 2px 8px rgba(0, 0, 0, .08); font-size: 12px; pointer-events: none; }
    .chart-tip .fecha { color: var(--muted); margin-bottom: 4px; }
    .chart-tip .fila { display: flex; align-items: center; gap: 6px; white-space: nowrap; }
    .chart-tip .clave { width: 12px; height: 2px; border-radius: 1px; }
    .chart-tip strong { font-variant-numeric: tabular-nums; }
    .chart-tip .nombre { color: var(--muted); }
    @media print { .chart-tip { display: none !important; } }
</style>
<script>
    document.querySelectorAll('svg[data-chart]').forEach((svg) => {
        const datos = JSON.parse(svg.dataset.chart);
        const caja = svg.parentElement;
        const cursor = svg.querySelector('.chart-cursor');
        const ancho = svg.viewBox.baseVal.width;
        const tip = document.createElement('div');
        tip.className = 'chart-tip';
        tip.hidden = true;
        caja.appendChild(tip);

        const mostrar = (evento) => {
            const punto = new DOMPoint(evento.clientX, evento.clientY).matrixTransform(svg.getScreenCTM().inverse());
            let i = 0;
            datos.x.forEach((x, k) => { if (Math.abs(x - punto.x) < Math.abs(datos.x[i] - punto.x)) i = k; });

            cursor.setAttribute('x1', datos.x[i]);
            cursor.setAttribute('x2', datos.x[i]);
            cursor.setAttribute('visibility', 'visible');

            // Los nombres vienen de la base de datos: siempre textContent, nunca innerHTML.
            const fecha = document.createElement('div');
            fecha.className = 'fecha';
            fecha.textContent = datos.fechas[i];
            const filas = datos.series.filter((s) => s.valores[i] !== null).map((s) => {
                const fila = document.createElement('div');
                fila.className = 'fila';
                const clave = document.createElement('span');
                clave.className = 'clave';
                clave.style.background = s.color;
                const valor = document.createElement('strong');
                valor.textContent = s.valores[i];
                const nombre = document.createElement('span');
                nombre.className = 'nombre';
                nombre.textContent = s.nombre;
                fila.append(clave, valor, nombre);
                return fila;
            });
            tip.replaceChildren(fecha, ...filas);
            tip.hidden = false;

            const escala = svg.clientWidth / ancho;
            const x = datos.x[i] * escala;
            tip.style.left = (x + 12 + tip.offsetWidth > caja.clientWidth ? x - 12 - tip.offsetWidth : x + 12) + 'px';
        };

        svg.addEventListener('pointermove', mostrar);
        svg.addEventListener('pointerleave', () => {
            cursor.setAttribute('visibility', 'hidden');
            tip.hidden = true;
        });
    });
</script>
