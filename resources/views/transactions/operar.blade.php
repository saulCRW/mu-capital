@extends('layouts.app')

@section('title', 'Comprar o vender')

@php
    $dinero = fn ($v) => number_format((float) $v, 2, '.', ',');
    // Quita ceros decimales sobrantes ("55.500000" → "55.5") sin tocar enteros ("100").
    $numero = fn ($v) => $v === null ? '' : (str_contains((string) $v, '.') ? rtrim(rtrim((string) $v, '0'), '.') : (string) $v);
    $tipo = old('tipo', $tipo);
    $assetId = (int) old('asset_id', $assetId);
@endphp

@section('content')
    <style>
        .operar { display: grid; grid-template-columns: minmax(0, 420px) minmax(0, 340px); gap: 32px; align-items: start; }
        @media (max-width: 860px) { .operar { grid-template-columns: minmax(0, 1fr); } }
        .segmented { display: inline-flex; border: 1px solid var(--border); border-radius: 6px; overflow: hidden; }
        .segmented input { position: absolute; opacity: 0; width: 1px; height: 1px; }
        .segmented label { margin: 0; padding: 8px 18px; font-size: 14px; cursor: pointer; }
        .segmented input:checked + label { background: var(--accent); color: #fff; }
        .segmented input:focus-visible + label { outline: 2px solid var(--accent); outline-offset: -4px; }
        .inline-action { display: flex; gap: 8px; }
        .inline-action input { flex: 1; }
        .resumen { position: sticky; top: 24px; padding: 16px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-soft); }
        .resumen h2 { margin: 0 0 12px; }
        .resumen dl { display: grid; grid-template-columns: 1fr auto; gap: 6px 12px; margin: 0; }
        .resumen dt { color: var(--muted); }
        .resumen dd { margin: 0; text-align: right; font-variant-numeric: tabular-nums; }
        .resumen .total { font-weight: 600; border-top: 1px solid var(--border); padding-top: 6px; }
    </style>

    <p class="muted"><a href="{{ route('portfolios.show', $portfolio) }}">{{ $portfolio->nombre }}</a> /</p>
    <h1>Comprar o vender</h1>
    <p class="muted">Efectivo disponible hoy: <strong>{{ $dinero($efectivo) }} {{ $portfolio->moneda_base }}</strong></p>

    <div class="operar">
        <form method="POST" action="{{ route('portfolios.transactions.store', $portfolio) }}" id="operar">
            @csrf
            <div class="segmented" role="radiogroup" aria-label="Operación">
                <input type="radio" id="tipo-compra" name="tipo" value="COMPRA" @checked($tipo === 'COMPRA')>
                <label for="tipo-compra">Comprar</label>
                <input type="radio" id="tipo-venta" name="tipo" value="VENTA" @checked($tipo === 'VENTA')>
                <label for="tipo-venta">Vender</label>
            </div>
            @error('tipo') <div class="error">{{ $message }}</div> @enderror

            <label for="asset_id">Activo</label>
            <select id="asset_id" name="asset_id" required>
                <option value="">— Elige un activo —</option>
                @foreach ($activos as $a)
                    <option value="{{ $a['id'] }}" @selected($assetId === $a['id'])
                            data-simbolo="{{ $a['simbolo'] }}" data-moneda="{{ $a['moneda'] }}"
                            data-tenencia="{{ $numero($a['tenencia']) ?: '0' }}" data-precio="{{ $numero($a['precio']) }}" data-tasa="{{ $numero($a['tasa']) }}">
                        {{ $a['simbolo'] }} · {{ $a['nombre'] }} ({{ $a['moneda'] }}){{ (float) $a['tenencia'] ? ' — tienes '.$numero($a['tenencia']) : '' }}
                    </option>
                @endforeach
            </select>
            <div class="help" id="activo-info"></div>
            @error('asset_id') <div class="error">{{ $message }}</div> @enderror

            <label for="fecha">Fecha</label>
            <input id="fecha" type="date" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
            @error('fecha') <div class="error">{{ $message }}</div> @enderror

            <label for="cantidad">Títulos</label>
            <div class="inline-action">
                <input id="cantidad" name="cantidad" inputmode="decimal" value="{{ old('cantidad', $cantidad) }}" required>
                <button type="button" class="btn btn-secondary" id="vender-todo" hidden>Vender todo</button>
            </div>
            @error('cantidad') <div class="error">{{ $message }}</div> @enderror

            <label for="precio">Precio por título <span id="moneda-precio" class="muted"></span></label>
            <input id="precio" name="precio" inputmode="decimal" value="{{ old('precio') }}" required>
            <div class="help">Se propone el último cierre registrado; ajústalo al precio real de la operación.</div>
            @error('precio') <div class="error">{{ $message }}</div> @enderror

            <label for="comision">Comisión <span id="moneda-comision" class="muted"></span></label>
            <input id="comision" name="comision" inputmode="decimal" value="{{ old('comision') }}" placeholder="0">
            @error('comision') <div class="error">{{ $message }}</div> @enderror

            <div id="fila-tasa" hidden>
                <label for="tipo_cambio">Tipo de cambio <span id="par-tasa" class="muted"></span></label>
                <input id="tipo_cambio" name="tipo_cambio" inputmode="decimal" value="{{ old('tipo_cambio') }}">
                <div class="help" id="tasa-info">Vacío = el último registrado en o antes de la fecha.</div>
                @error('tipo_cambio') <div class="error">{{ $message }}</div> @enderror
            </div>

            <label for="nota">Nota</label>
            <textarea id="nota" name="nota" rows="2" maxlength="1000">{{ old('nota') }}</textarea>
            @error('nota') <div class="error">{{ $message }}</div> @enderror

            <div class="actions" style="margin-top:20px">
                <button type="submit" id="registrar">Registrar compra</button>
                <a class="btn btn-secondary" href="{{ route('portfolios.show', $portfolio) }}">Cancelar</a>
            </div>
        </form>

        <aside class="resumen" aria-live="polite">
            <h2>Resumen</h2>
            <dl>
                <dt>Importe</dt><dd id="r-importe">—</dd>
                <dt>Comisión</dt><dd id="r-comision">—</dd>
                <dt class="total" id="r-total-label">Total a pagar</dt><dd class="total" id="r-total">—</dd>
                <dt id="r-base-label" hidden>En {{ $portfolio->moneda_base }}</dt><dd id="r-base" hidden>—</dd>
                <dt>Efectivo después</dt><dd id="r-efectivo">—</dd>
                <dt>Títulos después</dt><dd id="r-titulos">—</dd>
            </dl>
            <div class="error" id="r-aviso" hidden></div>
            <p class="help">Estimado. Al registrar se valida contra todo el libro del portafolio.</p>
        </aside>
    </div>

    <script>
        (() => {
            const base = @json($portfolio->moneda_base);
            const efectivo = {{ (float) $efectivo }};
            const $ = (id) => document.getElementById(id);
            const form = $('operar'), activo = $('asset_id');
            const fmt = (v, d = 2) => v.toLocaleString('es-MX', { minimumFractionDigits: d, maximumFractionDigits: d });
            const fmtTitulos = (v) => v.toLocaleString('es-MX', { maximumFractionDigits: 6 });
            const num = (el) => { const v = parseFloat(el.value); return Number.isFinite(v) ? v : 0; };
            let precioSugerido = $('precio').value === '' ? null : undefined;

            const tipo = () => form.querySelector('input[name="tipo"]:checked')?.value ?? 'COMPRA';
            const opcion = () => activo.selectedOptions[0]?.value ? activo.selectedOptions[0].dataset : null;

            function alCambiarActivo() {
                const a = opcion();
                const moneda = a?.moneda ?? '';
                $('moneda-precio').textContent = moneda ? `(${moneda})` : '';
                $('moneda-comision').textContent = moneda ? `(${moneda})` : '';
                $('fila-tasa').hidden = !a || moneda === base;
                $('par-tasa').textContent = a ? `${moneda} → ${base}` : '';
                $('tipo_cambio').placeholder = a?.tasa ?? '';
                $('tasa-info').textContent = a?.tasa
                    ? `Vacío = el último registrado en o antes de la fecha (hoy: ${a.tasa}).`
                    : 'No hay tipo de cambio registrado: escríbelo.';
                // El precio sugerido solo reemplaza lo que no escribió el usuario.
                if (a && ($('precio').value === '' || $('precio').value === precioSugerido)) {
                    precioSugerido = a.precio;
                    $('precio').value = a.precio;
                }
                $('activo-info').textContent = a
                    ? `Tienes ${fmtTitulos(parseFloat(a.tenencia))} títulos · último cierre: ${a.precio ? fmt(parseFloat(a.precio)) + ' ' + moneda : 'sin registro'}`
                    : '';
                calcular();
            }

            function alCambiarTipo() {
                const venta = tipo() === 'VENTA';
                // Al vender solo se ofrecen los activos que se tienen.
                for (const o of activo.options) {
                    if (o.value) {
                        o.hidden = venta && parseFloat(o.dataset.tenencia) <= 0;
                    }
                }
                if (activo.selectedOptions[0]?.hidden) {
                    activo.value = '';
                    alCambiarActivo();
                }
                $('registrar').textContent = venta ? 'Registrar venta' : 'Registrar compra';
                $('r-total-label').textContent = venta ? 'Total a recibir' : 'Total a pagar';
                calcular();
            }

            function calcular() {
                const a = opcion(), venta = tipo() === 'VENTA';
                $('vender-todo').hidden = !(venta && a && parseFloat(a.tenencia) > 0);
                if (!a) {
                    ['r-importe', 'r-comision', 'r-total', 'r-base', 'r-efectivo', 'r-titulos'].forEach((id) => $(id).textContent = '—');
                    $('r-aviso').hidden = true;
                    return;
                }

                const cantidad = num($('cantidad')), precio = num($('precio')), comision = num($('comision'));
                const tasa = a.moneda === base ? 1 : (num($('tipo_cambio')) || parseFloat(a.tasa) || NaN);
                const importe = cantidad * precio;
                const total = venta ? importe - comision : importe + comision;
                const enBase = total * tasa;
                const efectivoDespues = efectivo + (venta ? enBase : -enBase);
                const titulosDespues = parseFloat(a.tenencia) + (venta ? -cantidad : cantidad);

                $('r-importe').textContent = `${fmt(importe)} ${a.moneda}`;
                $('r-comision').textContent = `${fmt(comision)} ${a.moneda}`;
                $('r-total').textContent = `${fmt(total)} ${a.moneda}`;
                $('r-base-label').hidden = $('r-base').hidden = a.moneda === base;
                $('r-base').textContent = Number.isFinite(enBase) ? `≈ ${fmt(enBase)}` : 'sin tipo de cambio';
                $('r-efectivo').textContent = Number.isFinite(efectivoDespues) ? `${fmt(efectivoDespues)} ${base}` : '—';
                $('r-efectivo').classList.toggle('neg', efectivoDespues < 0);
                $('r-titulos').textContent = fmtTitulos(titulosDespues);
                $('r-titulos').classList.toggle('neg', titulosDespues < 0);

                const aviso = titulosDespues < 0 ? `Solo tienes ${fmtTitulos(parseFloat(a.tenencia))} ${a.simbolo}.`
                    : efectivoDespues < 0 ? 'No alcanza el efectivo disponible.' : '';
                $('r-aviso').textContent = aviso;
                $('r-aviso').hidden = aviso === '';
            }

            form.querySelectorAll('input[name="tipo"]').forEach((r) => r.addEventListener('change', alCambiarTipo));
            activo.addEventListener('change', alCambiarActivo);
            ['cantidad', 'precio', 'comision', 'tipo_cambio'].forEach((id) => $(id).addEventListener('input', calcular));
            $('vender-todo').addEventListener('click', () => { $('cantidad').value = opcion().tenencia; calcular(); });

            alCambiarTipo();
            alCambiarActivo();
        })();
    </script>
@endsection
