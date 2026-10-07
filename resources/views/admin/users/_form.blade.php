{{-- Campos compartidos por crear y editar usuario. $user es null al crear. --}}
@csrf
<label for="nombre">Nombre</label>
<input id="nombre" name="nombre" value="{{ old('nombre', $user?->nombre) }}" required maxlength="150">
@error('nombre') <div class="error">{{ $message }}</div> @enderror

<label for="email">Correo</label>
<input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required>
@error('email') <div class="error">{{ $message }}</div> @enderror

<label for="rol">Rol</label>
<select id="rol" name="rol" required>
    @foreach (\App\Modules\Users\Models\User::ROLES_DESPACHO as $rol)
        <option value="{{ $rol }}" @selected(old('rol', $user?->rol ?? 'asesor') === $rol)>{{ $rol }}</option>
    @endforeach
</select>
@error('rol') <div class="error">{{ $message }}</div> @enderror

<div id="campo-cliente">
    <label for="client_id">Cliente</label>
    <select id="client_id" name="client_id">
        <option value="">— Elige el cliente —</option>
        @foreach ($clientes as $c)
            <option value="{{ $c->id }}" @selected((int) old('client_id', $user?->client_id) === $c->id)>{{ $c->nombre }}</option>
        @endforeach
    </select>
    <div class="help">El usuario cliente solo consulta los portafolios y reportes de este cliente.</div>
    @error('client_id') <div class="error">{{ $message }}</div> @enderror
</div>
<script>
    (() => {
        const rol = document.getElementById('rol');
        const mostrar = () => { document.getElementById('campo-cliente').hidden = rol.value !== 'cliente'; };
        rol.addEventListener('change', mostrar);
        mostrar();
    })();
</script>

<label for="password">Contraseña {{ $user ? '(déjala vacía para no cambiarla)' : '' }}</label>
<input id="password" type="password" name="password" autocomplete="new-password" {{ $user ? '' : 'required' }}>
@error('password') <div class="error">{{ $message }}</div> @enderror

<label for="password_confirmation">Confirmar contraseña</label>
<input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
