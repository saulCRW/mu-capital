@php($sugerido ??= [])
{{-- Campos compartidos por crear y editar transacción. $transaction es null al crear. --}}
@csrf
<label for="tipo">Tipo</label>
<select id="tipo" name="tipo" required>
    @foreach (\App\Modules\Transactions\Models\Transaction::TIPOS as $tipo)
        <option value="{{ $tipo }}" @selected(old('tipo', $transaction?->tipo ?? $sugerido['tipo'] ?? 'COMPRA') === $tipo)>{{ $tipo }}</option>
    @endforeach
</select>
@error('tipo') <div class="error">{{ $message }}</div> @enderror

<label for="fecha">Fecha</label>
<input id="fecha" type="date" name="fecha" value="{{ old('fecha', $transaction?->fecha?->toDateString() ?? $sugerido['fecha'] ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
@error('fecha') <div class="error">{{ $message }}</div> @enderror

<label for="asset_id">Activo</label>
<select id="asset_id" name="asset_id">
    <option value="">— Sin activo —</option>
    @foreach ($activos as $activo)
        <option value="{{ $activo->id }}" @selected((int) old('asset_id', $transaction?->asset_id ?? $sugerido['asset_id'] ?? null) === $activo->id)>{{ $activo->simbolo }} · {{ $activo->nombre }} ({{ $activo->moneda }})</option>
    @endforeach
</select>
<div class="help">Obligatorio en COMPRA, VENTA, DIVIDENDO y SPLIT. Vacío en DEPOSITO y RETIRO.</div>
@error('asset_id') <div class="error">{{ $message }}</div> @enderror

<label for="cantidad">Cantidad</label>
<input id="cantidad" name="cantidad" inputmode="decimal" value="{{ old('cantidad', $transaction ? rtrim(rtrim($transaction->cantidad, '0'), '.') : ($sugerido['cantidad'] ?? '')) }}" required>
<div class="help">COMPRA/VENTA: número de títulos. SPLIT: factor (2 = 2 por 1). Resto: monto.</div>
@error('cantidad') <div class="error">{{ $message }}</div> @enderror

<label for="precio">Precio unitario</label>
<input id="precio" name="precio" inputmode="decimal" value="{{ old('precio', $transaction && in_array($transaction->tipo, ['COMPRA', 'VENTA'], true) ? rtrim(rtrim($transaction->precio, '0'), '.') : '') }}">
<div class="help">Solo COMPRA y VENTA, en la moneda del activo.</div>
@error('precio') <div class="error">{{ $message }}</div> @enderror

<label for="comision">Comisión</label>
<input id="comision" name="comision" inputmode="decimal" value="{{ old('comision', $transaction && (float) $transaction->comision ? rtrim(rtrim($transaction->comision, '0'), '.') : '') }}">
<div class="help">Solo COMPRA y VENTA. Para otras comisiones usa el tipo COMISION.</div>
@error('comision') <div class="error">{{ $message }}</div> @enderror

<label for="moneda">Moneda</label>
<input id="moneda" name="moneda" maxlength="3" style="text-transform:uppercase" value="{{ old('moneda', $transaction?->moneda) }}">
<div class="help">Vacío = la del activo, o {{ $portfolio->moneda_base }} si no hay activo. En COMPRA, VENTA y SPLIT siempre es la del activo.</div>
@error('moneda') <div class="error">{{ $message }}</div> @enderror

<label for="tipo_cambio">Tipo de cambio a {{ $portfolio->moneda_base }}</label>
<input id="tipo_cambio" name="tipo_cambio" inputmode="decimal" value="{{ old('tipo_cambio', $transaction && $transaction->moneda !== $portfolio->moneda_base ? rtrim(rtrim($transaction->tipo_cambio, '0'), '.') : '') }}">
<div class="help">Vacío = el último registrado en Precios en o antes de la fecha.</div>
@error('tipo_cambio') <div class="error">{{ $message }}</div> @enderror

<label for="nota">Nota</label>
<textarea id="nota" name="nota" rows="2" maxlength="1000">{{ old('nota', $transaction?->nota ?? $sugerido['nota'] ?? null) }}</textarea>
@error('nota') <div class="error">{{ $message }}</div> @enderror
