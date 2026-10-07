{{-- Campos compartidos por crear y editar activo. $asset es null al crear. --}}
@csrf
<label for="simbolo">Símbolo</label>
<input id="simbolo" name="simbolo" value="{{ old('simbolo', $asset?->simbolo) }}" required maxlength="30" style="text-transform:uppercase">
@error('simbolo') <div class="error">{{ $message }}</div> @enderror

<label for="nombre">Nombre</label>
<input id="nombre" name="nombre" value="{{ old('nombre', $asset?->nombre) }}" required maxlength="150">
@error('nombre') <div class="error">{{ $message }}</div> @enderror

<label for="tipo">Tipo</label>
<select id="tipo" name="tipo" required>
    @foreach (\App\Modules\Assets\Models\Asset::TIPOS as $tipo)
        <option value="{{ $tipo }}" @selected(old('tipo', $asset?->tipo ?? 'accion') === $tipo)>{{ $tipo }}</option>
    @endforeach
</select>
@error('tipo') <div class="error">{{ $message }}</div> @enderror

<label for="sector">Sector</label>
<input id="sector" name="sector" value="{{ old('sector', $asset?->sector) }}" maxlength="100">
@error('sector') <div class="error">{{ $message }}</div> @enderror

<label for="pais">País</label>
<input id="pais" name="pais" value="{{ old('pais', $asset?->pais) }}" maxlength="100">
@error('pais') <div class="error">{{ $message }}</div> @enderror

<label for="moneda">Moneda</label>
<input id="moneda" name="moneda" value="{{ old('moneda', $asset?->moneda ?? 'MXN') }}" required maxlength="3" style="text-transform:uppercase">
@error('moneda') <div class="error">{{ $message }}</div> @enderror

<label for="exchange">Exchange</label>
<input id="exchange" name="exchange" value="{{ old('exchange', $asset?->exchange) }}" maxlength="50" style="text-transform:uppercase">
@error('exchange') <div class="error">{{ $message }}</div> @enderror
