{{-- Campos compartidos por crear y editar cliente. $client es null al crear. --}}
@csrf
<label for="nombre">Nombre</label>
<input id="nombre" name="nombre" value="{{ old('nombre', $client?->nombre) }}" required maxlength="150">
@error('nombre') <div class="error">{{ $message }}</div> @enderror

<label for="contacto">Contacto</label>
<input id="contacto" name="contacto" value="{{ old('contacto', $client?->contacto) }}" maxlength="255">
<div class="help">Correo, teléfono o ambos.</div>
@error('contacto') <div class="error">{{ $message }}</div> @enderror

<label for="perfil_riesgo">Perfil de riesgo</label>
<select id="perfil_riesgo" name="perfil_riesgo" required>
    @foreach (\App\Modules\Clients\Models\Client::PERFILES as $perfil)
        <option value="{{ $perfil }}" @selected(old('perfil_riesgo', $client?->perfil_riesgo ?? 'moderado') === $perfil)>{{ $perfil }}</option>
    @endforeach
</select>
@error('perfil_riesgo') <div class="error">{{ $message }}</div> @enderror

@if (auth()->user()->isAdmin())
    <label for="asesor_id">Asesor</label>
    <select id="asesor_id" name="asesor_id" required>
        @foreach ($asesores as $asesor)
            <option value="{{ $asesor->id }}" @selected((int) old('asesor_id', $client?->asesor_id) === $asesor->id)>{{ $asesor->nombre }}</option>
        @endforeach
    </select>
    @error('asesor_id') <div class="error">{{ $message }}</div> @enderror
@endif
