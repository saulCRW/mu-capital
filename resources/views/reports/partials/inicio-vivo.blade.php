{{--
    Inicio en vivo: cada pocos segundos pide a /api/v1/dashboard/vivo los valores revaluados con
    las cotizaciones del momento (patrimonio, ganancia y rendimiento del año, y por
    portafolio valor, peso, rendimiento, diferencia y ganancia). No guarda nada.
--}}
<script>
    (() => {
        const caja = document.getElementById('inicio-vivo');
        if (!caja) return;

        const CADA = {{ \App\Modules\Prices\Services\QuoteService::VIGENCIA }} * 1000;
        const dinero = (v) => v.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const pct = (v) => dinero(v * 100) + ' %';
        const anteriores = new Map();

        // Escribe el valor (si viene) con su signo de color y un destello si cambió.
        function pintar(el, valor, formato, conSigno) {
            if (!el || valor === null || valor === undefined) return;
            el.textContent = formato(valor);
            if (conSigno) {
                el.classList.remove('pos', 'neg');
                if (valor > 0) el.classList.add('pos');
                if (valor < 0) el.classList.add('neg');
            }
            const antes = anteriores.get(el);
            if (el.classList.contains('cotizacion') && antes !== undefined && antes.toFixed(2) !== valor.toFixed(2)) {
                el.classList.remove('sube', 'baja');
                void el.offsetWidth; // reinicia la animación
                el.classList.add(valor > antes ? 'sube' : 'baja');
                setTimeout(() => el.classList.remove('sube', 'baja'), 600);
            }
            anteriores.set(el, valor);
        }

        async function actualizar() {
            let datos;
            try {
                const r = await fetch(caja.dataset.url, { headers: { Accept: 'application/json' } });
                if (!r.ok) return;
                datos = (await r.json()).data;
            } catch (e) {
                return;
            }

            if (datos.total) {
                pintar(caja.querySelector('[data-vivo="patrimonio"]'), datos.total.valor, dinero, false);
                pintar(caja.querySelector('[data-vivo="ganancia"]'), datos.total.ganancia, dinero, true);
                pintar(caja.querySelector('[data-vivo="twr"]'), datos.total.twr, pct, true);
            }
            document.querySelectorAll('tr[data-portafolio]').forEach((fila) => {
                const p = datos.portafolios[fila.dataset.portafolio];
                if (!p) return;
                pintar(fila.querySelector('[data-vivo="valor"]'), p.valor, dinero, false);
                pintar(fila.querySelector('[data-vivo="peso"]'), p.peso, pct, false);
                pintar(fila.querySelector('[data-vivo="twr"]'), p.twr, pct, true);
                pintar(fila.querySelector('[data-vivo="diferencia"]'), p.diferencia, pct, true);
                pintar(fila.querySelector('[data-vivo="ganancia"]'), p.ganancia, dinero, true);
            });

            if (datos.hora) {
                document.getElementById('vivo-fecha').textContent = 'En vivo · último precio ' + new Date(datos.hora * 1000).toLocaleString('es-MX', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
                document.getElementById('vivo-nota').textContent = `Valores con los precios del momento (Yahoo Finance; se actualizan cada ${CADA / 1000} s, algunas bolsas con retraso). La gráfica usa los cierres guardados.`;
            }
        }

        // Solo consulta mientras la pestaña está a la vista.
        let reloj = null;
        const iniciar = () => { if (!reloj) { actualizar(); reloj = setInterval(actualizar, CADA); } };
        const detener = () => { clearInterval(reloj); reloj = null; };
        document.addEventListener('visibilitychange', () => document.hidden ? detener() : iniciar());
        iniciar();
    })();
</script>
