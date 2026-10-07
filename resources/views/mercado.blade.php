@extends('layouts.app')

@section('title', 'Mercado')

@section('content')
    <style>
        .estado-vivo { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--muted); }
        .estado-vivo .punto { width: 8px; height: 8px; border-radius: 4px; background: var(--muted); }
        .estado-vivo.ok .punto { background: var(--ok); }
        .estado-vivo.error .punto { background: var(--danger); }
        .tile .chispa { margin-top: 6px; }
        svg.chispa { display: block; }
    </style>

    <div class="page-head">
        <h1>Mercado</h1>
        <span class="estado-vivo" id="estado" role="status" aria-live="polite"><span class="punto"></span><span id="estado-texto">Conectando…</span></span>
    </div>
    <p class="muted">Precios del momento de Yahoo Finance; se actualizan solos cada {{ \App\Modules\Prices\Services\QuoteService::VIGENCIA }} segundos.
        Las bolsas pueden publicar con retraso (la BMV, unos 20 minutos): la columna "Hora" dice de cuándo es cada precio.</p>

    <div class="tiles" id="indices">
        <div class="tile muted" id="indices-cargando">Cargando índices…</div>
    </div>

    <div style="overflow-x:auto">
        <table>
            <thead>
                <tr><th>Activo</th><th class="num">Precio</th><th class="num">Cambio</th><th class="num">Cambio %</th><th>Hoy</th><th>Hora</th><th>Mercado</th></tr>
            </thead>
            <tbody>
                @foreach ($activos as $a)
                    <tr data-activo="{{ $a->id }}">
                        <td>
                            <strong>{{ $a->simbolo }}</strong> <span class="muted">{{ \Illuminate\Support\Str::limit($a->nombre, 28) }}</span>
                            @if (in_array($a->id, $mios, true)) <span class="chip">en tu cartera</span> @endif
                        </td>
                        <td class="num cotizacion" data-campo="precio">—</td>
                        <td class="num" data-campo="cambio">—</td>
                        <td class="num" data-campo="cambio_pct">—</td>
                        <td data-campo="puntos"></td>
                        <td class="muted" data-campo="hora">—</td>
                        <td data-campo="abierto"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="help">¿Falta un activo? Dalo de alta en <a href="{{ route('assets.index') }}">Activos</a>.</p>

    <script>
        (() => {
            const URL = @json(route('mercado.cotizaciones'));
            const CADA = {{ \App\Modules\Prices\Services\QuoteService::VIGENCIA }} * 1000;
            const anteriores = {};

            const num = (v, d = 2) => v === null || v === undefined ? '—' : v.toLocaleString('es-MX', { minimumFractionDigits: d, maximumFractionDigits: d });
            const decimales = (precio) => precio < 1 ? 4 : 2;
            const signo = (v) => v === null ? '' : (v > 0 ? 'pos' : (v < 0 ? 'neg' : ''));
            const hora = (ts) => new Date(ts * 1000).toLocaleString('es-MX', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });

            // Mini gráfica del día: línea del precio y referencia tenue en el cierre anterior.
            function chispa(puntos, anterior, ancho = 96, alto = 24) {
                const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('class', 'chispa');
                svg.setAttribute('width', ancho);
                svg.setAttribute('height', alto);
                svg.setAttribute('aria-hidden', 'true');
                if (!puntos || puntos.length < 2) return svg;
                const valores = anterior ? [...puntos, anterior] : puntos;
                const min = Math.min(...valores), max = Math.max(...valores), rango = max - min || 1;
                const y = (v) => (alto - 2) - (v - min) / rango * (alto - 4);
                if (anterior) {
                    const ref = document.createElementNS(svg.namespaceURI, 'line');
                    Object.entries({ x1: 0, x2: ancho, y1: y(anterior), y2: y(anterior), class: 'c-grid', stroke: '#d1d9e0', 'stroke-width': 1 }).forEach(([k, v]) => ref.setAttribute(k, v));
                    svg.appendChild(ref);
                }
                const linea = document.createElementNS(svg.namespaceURI, 'polyline');
                linea.setAttribute('points', puntos.map((v, i) => `${(i / (puntos.length - 1) * (ancho - 2) + 1).toFixed(1)},${y(v).toFixed(1)}`).join(' '));
                Object.entries({ fill: 'none', class: 'c-s0', stroke: '#2a78d6', 'stroke-width': 1.5, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }).forEach(([k, v]) => linea.setAttribute(k, v));
                svg.appendChild(linea);
                return svg;
            }

            function destellar(celda, clave, precio) {
                if (anteriores[clave] !== undefined && anteriores[clave] !== precio) {
                    celda.classList.remove('sube', 'baja');
                    void celda.offsetWidth; // reinicia la animación
                    celda.classList.add(precio > anteriores[clave] ? 'sube' : 'baja');
                    setTimeout(() => celda.classList.remove('sube', 'baja'), 600);
                }
                anteriores[clave] = precio;
            }

            function pintarFila(fila, c) {
                const campo = (n) => fila.querySelector(`[data-campo="${n}"]`);
                if (!c) {
                    campo('precio').textContent = 'sin dato';
                    return;
                }
                const d = decimales(c.precio);
                campo('precio').textContent = `${num(c.precio, d)} ${c.moneda ?? ''}`;
                destellar(campo('precio'), 'a' + fila.dataset.activo, c.precio);
                campo('cambio').textContent = c.cambio === null ? '—' : (c.cambio > 0 ? '+' : '') + num(c.cambio, d);
                campo('cambio').className = 'num ' + signo(c.cambio);
                campo('cambio_pct').textContent = c.cambio_pct === null ? '—' : (c.cambio_pct > 0 ? '+' : '') + num(c.cambio_pct * 100) + ' %';
                campo('cambio_pct').className = 'num ' + signo(c.cambio_pct);
                campo('puntos').replaceChildren(chispa(c.puntos, c.anterior));
                campo('hora').textContent = hora(c.hora);
                const chip = document.createElement('span');
                chip.className = 'chip' + (c.abierto ? ' abierto' : '');
                chip.textContent = c.abierto ? 'abierto' : 'cerrado';
                campo('abierto').replaceChildren(chip);
            }

            function pintarIndices(datos) {
                const tarjetas = [
                    ...datos.indices.map((c) => ({ nombre: c.nombre, c, d: 2 })),
                    ...Object.entries(datos.fx).filter(([, c]) => c).map(([moneda, c]) => ({ nombre: `${moneda}/MXN`, c, d: 4 })),
                ];
                document.getElementById('indices').replaceChildren(...tarjetas.map(({ nombre, c, d }) => {
                    const tile = document.createElement('div');
                    tile.className = 'tile';
                    const etiqueta = document.createElement('div');
                    etiqueta.className = 'label';
                    etiqueta.textContent = nombre;
                    const valor = document.createElement('div');
                    valor.className = 'value cotizacion';
                    valor.textContent = num(c.precio, d);
                    destellar(valor, 'i' + nombre, c.precio);
                    const sub = document.createElement('div');
                    sub.className = 'sub ' + signo(c.cambio_pct);
                    sub.textContent = c.cambio_pct === null ? '—' : `${c.cambio_pct > 0 ? '+' : ''}${num(c.cambio_pct * 100)} % hoy`;
                    tile.append(etiqueta, valor, sub, chispa(c.puntos, c.anterior, 150, 28));
                    return tile;
                }));
            }

            async function actualizar() {
                const estado = document.getElementById('estado');
                try {
                    const respuesta = await fetch(URL, { headers: { Accept: 'application/json' } });
                    if (!respuesta.ok) throw new Error(respuesta.status);
                    const datos = await respuesta.json();
                    document.querySelectorAll('tr[data-activo]').forEach((fila) => pintarFila(fila, datos.activos[fila.dataset.activo]));
                    pintarIndices(datos);
                    estado.className = 'estado-vivo ok';
                    document.getElementById('estado-texto').textContent = 'En vivo · ' + new Date().toLocaleTimeString('es-MX');
                } catch (e) {
                    estado.className = 'estado-vivo error';
                    document.getElementById('estado-texto').textContent = 'Sin conexión con el proveedor; reintentando…';
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
@endsection
