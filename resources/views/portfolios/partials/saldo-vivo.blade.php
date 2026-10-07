{{--
    Saldo en vivo: cada pocos segundos pide las cotizaciones del momento de los activos
    del portafolio y revalúa las posiciones y los totales en pantalla (no guarda nada;
    los cierres los guarda la tarea programada). Activos en otra moneda se convierten
    con el tipo de cambio del momento.
--}}
<style>
    .cotizacion { transition: background-color 1.2s ease; }
    .cotizacion.sube { background-color: rgba(26, 127, 55, .14); transition: none; }
    .cotizacion.baja { background-color: rgba(180, 35, 24, .14); transition: none; }
</style>
<script>
    (() => {
        const caja = document.getElementById('saldo-vivo');
        const filas = [...document.querySelectorAll('tr[data-posicion]')];
        if (!caja || filas.length === 0) return;

        const base = caja.dataset.base;
        const efectivo = parseFloat(caja.dataset.efectivo);
        const aportaciones = parseFloat(caja.dataset.aportaciones);
        const CADA = {{ \App\Modules\Prices\Services\QuoteService::VIGENCIA }} * 1000;
        const dinero = (v) => v.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const pct = (v) => v === null || !isFinite(v) ? '—' : dinero(v * 100) + ' %';
        const pintar = (el, texto, valor) => {
            if (!el) return;
            el.textContent = texto;
            el.classList.remove('pos', 'neg');
            if (valor > 0) el.classList.add('pos');
            if (valor < 0) el.classList.add('neg');
        };

        // Pesos (o la moneda base) por 1 unidad de la moneda del activo, con el tipo de cambio del momento.
        function tasa(moneda, fx, guardada) {
            if (moneda === base) return 1;
            if (base === 'MXN' && fx[moneda]) return fx[moneda].precio;
            if (moneda === 'MXN' && fx[base]) return 1 / fx[base].precio;
            return guardada;
        }

        async function actualizar() {
            let datos;
            try {
                const r = await fetch(caja.dataset.url, { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                datos = await r.json();
            } catch (e) {
                return;
            }

            let posiciones = 0;
            let ultima = 0;
            const valores = new Map();
            for (const fila of filas) {
                const c = datos.activos[fila.dataset.posicion];
                const t = tasa(fila.dataset.moneda, datos.fx, parseFloat(fila.dataset.tasa));
                let valor = parseFloat(fila.dataset.valor);
                if (c && isFinite(t)) {
                    const celda = fila.querySelector('[data-vivo="precio"]');
                    const antes = parseFloat(celda.dataset.ultimo ?? 'NaN');
                    if (isFinite(antes) && antes !== c.precio) {
                        celda.classList.add(c.precio > antes ? 'sube' : 'baja');
                        setTimeout(() => celda.classList.remove('sube', 'baja'), 600);
                    }
                    celda.dataset.ultimo = c.precio;
                    celda.textContent = dinero(c.precio);
                    valor = parseFloat(fila.dataset.cantidad) * c.precio * t;
                    ultima = Math.max(ultima, c.hora);
                }
                const costo = parseFloat(fila.dataset.costo);
                pintar(fila.querySelector('[data-vivo="valor"]'), dinero(valor), 0);
                pintar(fila.querySelector('[data-vivo="gp"]'), dinero(valor - costo), valor - costo);
                pintar(fila.querySelector('[data-vivo="gp-pct"]'), pct(costo > 0 ? (valor - costo) / costo : null), valor - costo);
                valores.set(fila, valor);
                posiciones += valor;
            }

            const total = efectivo + posiciones;
            filas.forEach((fila) => pintar(fila.querySelector('[data-vivo="peso"]'), pct(total > 0 ? valores.get(fila) / total : null), 0));
            pintar(caja.querySelector('[data-vivo="total"]'), dinero(total), 0);
            pintar(caja.querySelector('[data-vivo="posiciones"]'), dinero(posiciones), 0);
            pintar(caja.querySelector('[data-vivo="peso-efectivo"]'), pct(total > 0 ? efectivo / total : null), 0);
            pintar(caja.querySelector('[data-vivo="gp"]'), dinero(total - aportaciones), total - aportaciones);
            pintar(caja.querySelector('[data-vivo="rendimiento"]'), pct(aportaciones > 0 ? (total - aportaciones) / aportaciones : null), total - aportaciones);

            if (ultima > 0) {
                document.getElementById('vivo-fecha').textContent = 'en vivo · último precio ' + new Date(ultima * 1000).toLocaleString('es-MX', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
                const nota = document.getElementById('vivo-nota');
                if (nota) nota.textContent = `Precios del momento (Yahoo Finance; se actualizan cada ${CADA / 1000} s, algunas bolsas con retraso) y tipo de cambio del momento.`;
            }
        }

        let reloj = null;
        const iniciar = () => { if (!reloj) { actualizar(); reloj = setInterval(actualizar, CADA); } };
        const detener = () => { clearInterval(reloj); reloj = null; };
        document.addEventListener('visibilitychange', () => document.hidden ? detener() : iniciar());
        iniciar();
    })();
</script>
