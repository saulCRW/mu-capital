{{-- Campos compartidos por crear y editar activo (formulario a dos columnas). $asset es null al crear. --}}
@csrf
<div class="campo">
    <label for="simbolo">Símbolo</label>
    <input id="simbolo" name="simbolo" value="{{ old('simbolo', $asset?->simbolo) }}" required maxlength="30" style="text-transform:uppercase">
    @error('simbolo') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="nombre">Nombre</label>
    <input id="nombre" name="nombre" value="{{ old('nombre', $asset?->nombre) }}" required maxlength="150">
    @error('nombre') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="tipo">Tipo</label>
    <select id="tipo" name="tipo" required>
        @foreach (\App\Modules\Assets\Models\Asset::TIPOS as $tipo)
            <option value="{{ $tipo }}" @selected(old('tipo', $asset?->tipo ?? 'accion') === $tipo)>{{ $tipo }}</option>
        @endforeach
    </select>
    @error('tipo') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="sector">Sector</label>
    <input id="sector" name="sector" value="{{ old('sector', $asset?->sector) }}" maxlength="100">
    @error('sector') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="pais">País</label>
    <input id="pais" name="pais" value="{{ old('pais', $asset?->pais) }}" maxlength="100">
    @error('pais') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="moneda">Moneda</label>
    <input id="moneda" name="moneda" value="{{ old('moneda', $asset?->moneda ?? 'MXN') }}" required maxlength="3" style="text-transform:uppercase">
    @error('moneda') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="exchange">Exchange</label>
    <input id="exchange" name="exchange" value="{{ old('exchange', $asset?->exchange) }}" maxlength="50" style="text-transform:uppercase">
    @error('exchange') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="campo">
    <label for="simbolo_proveedor">Símbolo en el proveedor de precios</label>
    <input id="simbolo_proveedor" name="simbolo_proveedor" value="{{ old('simbolo_proveedor', $asset?->simbolo_proveedor) }}" maxlength="40" style="text-transform:uppercase">
    <div class="help">Opcional. Vacío = se deduce: BMV → WALMEX.MX, cripto → BTC-USD, EE. UU. → AAPL.</div>
    @error('simbolo_proveedor') <div class="error">{{ $message }}</div> @enderror
</div>
