{{--
    Saldo en vivo: cada pocos segundos pide las cotizaciones del momento de los activos
    del portafolio y revalúa los KPI (y, en la pestaña Holdings sin agrupar, cada fila).
    No guarda nada; los cierres los guarda la tarea programada. Activos en otra moneda se
    convierten con el tipo de cambio del momento. Las posiciones abiertas vienen en
    data-posiciones del bloque de KPI.
--}}
<script>
    (() => {
        const caja = document.getElementById('saldo-vivo');
        if (!caja) return;
        const posiciones = JSON.parse(caja.dataset.posiciones || '[]');
        if (posiciones.length === 0) return;

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
        const destellar = (el, antes, ahora) => {
            if (!el || !isFinite(antes) || antes === ahora) return;
            el.classList.remove('sube', 'baja');
            void el.offsetWidth; // reinicia la animación
            el.classList.add(ahora > antes ? 'sube' : 'baja');
            setTimeout(() => el.classList.remove('sube', 'baja'), 600);
        };

        // Moneda base por 1 unidad de la moneda del activo, con el tipo de cambio del momento.
        function tasa(moneda, fx, guardada) {
            if (moneda === base) return 1;
            if (base === 'MXN' && fx[moneda]) return fx[moneda].precio;
            if (moneda === 'MXN' && fx[base]) return 1 / fx[base].precio;
            return guardada;
        }

        const anteriores = new Map();
        let totalAnterior = NaN;

        // Estado de error: si la consulta falla, se avisa (en ámbar) y se conservan los últimos valores.
        function sinConexion() {
            const etiqueta = document.getElementById('vivo-fecha');
            etiqueta.textContent = 'sin conexión con el proveedor; valores de la última actualización, reintentando…';
            etiqueta.classList.add('warn');
        }

        async function actualizar() {
            let datos;
            try {
                const r = await fetch(caja.dataset.url, { headers: { Accept: 'application/json' } });
                if (!r.ok) return sinConexion();
                datos = (await r.json()).data;
            } catch (e) {
                return sinConexion();
            }
            document.getElementById('vivo-fecha').classList.remove('warn');

            let total = efectivo;
            let gp = -aportaciones + efectivo;
            let ultima = 0;
            const vivas = posiciones.map((p) => {
                const c = datos.activos[p.asset_id];
                const t = tasa(p.moneda, datos.fx, parseFloat(p.tasa));
                let valor = parseFloat(p.valor_base);
                if (c && isFinite(t)) {
                    valor = parseFloat(p.cantidad) * c.precio * t;
                    ultima = Math.max(ultima, c.hora);
                }
                total += valor;
                gp += valor;
                return { p, c, valor };
            });

            const celdaTotal = caja.querySelector('[data-vivo="total"]');
            pintar(celdaTotal, dinero(total), 0);
            destellar(celdaTotal, totalAnterior, Math.round(total * 100));
            totalAnterior = Math.round(total * 100);
            pintar(caja.querySelector('[data-vivo="peso-efectivo"]'), pct(total > 0 ? efectivo / total : null), 0);
            pintar(caja.querySelector('[data-vivo="gp"]'), dinero(gp), gp);
            pintar(caja.querySelector('[data-vivo="rendimiento"]'), pct(aportaciones > 0 ? gp / aportaciones : null), gp);

            // Filas de la pestaña Holdings (solo existen sin agrupar).
            for (const { p, c, valor } of vivas) {
                const fila = document.querySelector(`tr[data-posicion="${p.asset_id}"]`);
                if (!fila) continue;
                if (c) {
                    const precio = fila.querySelector('[data-vivo="precio"]');
                    destellar(precio.parentElement, anteriores.get(p.asset_id), c.precio);
                    anteriores.set(p.asset_id, c.precio);
                    precio.textContent = dinero(c.precio);
                }
                const gpNoRealizada = valor - parseFloat(p.costo_base);
                const invertido = parseFloat(p.invertido_base);
                const resultado = gpNoRealizada + parseFloat(p.gp_realizada) + parseFloat(p.ingresos);
                pintar(fila.querySelector('[data-vivo="valor"]'), dinero(valor), 0);
                pintar(fila.querySelector('[data-vivo="peso"]'), pct(total > 0 ? valor / total : null), 0);
                pintar(fila.querySelector('[data-vivo="gp"]'), dinero(gpNoRealizada), gpNoRealizada);
                pintar(fila.querySelector('[data-vivo="rendimiento"]'), pct(invertido > 0 ? resultado / invertido : null), resultado);
            }

            if (ultima > 0) {
                document.getElementById('vivo-fecha').textContent = 'en vivo · último precio ' + new Date(ultima * 1000).toLocaleString('es-MX', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
                document.getElementById('vivo-nota').textContent = `Precios del momento (Yahoo Finance; se actualizan cada ${CADA / 1000} s, algunas bolsas con retraso) y tipo de cambio del momento. Rendimiento anualizado, gráfica y subtotales usan el último cierre.`;
            }
        }

        let reloj = null;
        const iniciar = () => { if (!reloj) { actualizar(); reloj = setInterval(actualizar, CADA); } };
        const detener = () => { clearInterval(reloj); reloj = null; };
        document.addEventListener('visibilitychange', () => document.hidden ? detener() : iniciar());
        iniciar();
    })();
</script>
