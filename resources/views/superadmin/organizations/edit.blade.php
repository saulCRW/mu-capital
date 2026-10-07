@extends('layouts.app')

@section('title', 'Editar despacho')

@section('content')
    <h1>Editar despacho</h1>
    <form method="POST" action="{{ route('superadmin.organizations.update', $organization) }}" class="narrow">
        @csrf
        @method('PUT')
        <label for="nombre">Nombre del despacho</label>
        <input id="nombre" name="nombre" value="{{ old('nombre', $organization->nombre) }}" required maxlength="150">
        @error('nombre') <div class="error">{{ $message }}</div> @enderror

        <label for="plan_id">Plan</label>
        <select id="plan_id" name="plan_id" required>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((int) old('plan_id', $organization->plan_id) === $plan->id)>{{ $plan->nombre }}</option>
            @endforeach
        </select>
        @error('plan_id') <div class="error">{{ $message }}</div> @enderror

        <label for="estado">Estado</label>
        <select id="estado" name="estado" required>
            @foreach (\App\Modules\Organizations\Models\Organization::ESTADOS as $estado)
                <option value="{{ $estado }}" @selected(old('estado', $organization->estado) === $estado)>{{ $estado }}</option>
            @endforeach
        </select>
        @error('estado') <div class="error">{{ $message }}</div> @enderror

        <label for="tasa_libre_riesgo_pct">Tasa libre de riesgo anual (%)</label>
        <input id="tasa_libre_riesgo_pct" name="tasa_libre_riesgo_pct" inputmode="decimal" value="{{ old('tasa_libre_riesgo_pct', $tasaPct) }}" required>
        @error('tasa_libre_riesgo_pct') <div class="error">{{ $message }}</div> @enderror

        <div class="actions">
            <button type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('superadmin.organizations.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
