{{-- Campos compartidos por crear y editar portafolio. $portfolio es null al crear. --}}
@csrf
<label for="nombre">Nombre</label>
<input id="nombre" name="nombre" value="{{ old('nombre', $portfolio?->nombre) }}" required maxlength="150">
@error('nombre') <div class="error">{{ $message }}</div> @enderror

<label for="moneda_base">Moneda base</label>
<input id="moneda_base" name="moneda_base" value="{{ old('moneda_base', $portfolio?->moneda_base ?? 'MXN') }}" required maxlength="3" style="text-transform:uppercase">
<div class="help">Código de 3 letras. Todo el portafolio se valúa en esta moneda.</div>
@error('moneda_base') <div class="error">{{ $message }}</div> @enderror

<label for="benchmark_id">Benchmark</label>
<select id="benchmark_id" name="benchmark_id">
    <option value="">Sin benchmark</option>
    @foreach ($benchmarks as $benchmark)
        <option value="{{ $benchmark->id }}" @selected((int) old('benchmark_id', $portfolio?->benchmark_id) === $benchmark->id)>{{ $benchmark->nombre }}</option>
    @endforeach
</select>
@error('benchmark_id') <div class="error">{{ $message }}</div> @enderror
