@extends('layouts.app')

@section('title', 'Mi cuenta')

@section('content')
    <h1>Mi cuenta</h1>
    <form method="POST" action="{{ route('cuenta.update') }}" class="narrow">
        @csrf
        @method('PUT')
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" value="{{ old('nombre', $user->nombre) }}" required maxlength="150">
        @error('nombre') <div class="error">{{ $message }}</div> @enderror

        <label for="email">Correo</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        @unless ($user->isSuperadmin())
            <label for="tasa_libre_riesgo">Tasa libre de riesgo (% anual)</label>
            <input id="tasa_libre_riesgo" name="tasa_libre_riesgo" inputmode="decimal"
                   value="{{ old('tasa_libre_riesgo', rtrim(rtrim(number_format((float) $user->tasa_libre_riesgo * 100, 4, '.', ''), '0'), '.')) }}" required>
            <div class="help">Se usa en Sharpe y Sortino. Por ejemplo, la tasa de CETES a 28 días.</div>
            @error('tasa_libre_riesgo') <div class="error">{{ $message }}</div> @enderror
        @endunless

        <h2>Cambiar contraseña</h2>
        <label for="password_actual">Contraseña actual</label>
        <input id="password_actual" type="password" name="password_actual" autocomplete="current-password">
        @error('password_actual') <div class="error">{{ $message }}</div> @enderror

        <label for="password">Nueva contraseña</label>
        <input id="password" type="password" name="password" autocomplete="new-password">
        <div class="help">Déjala vacía para conservar la actual.</div>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label for="password_confirmation">Confirmar nueva contraseña</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">

        <div class="actions">
            <button type="submit">Guardar cambios</button>
        </div>
    </form>

    @unless ($user->isSuperadmin())
        <h2>Tus datos</h2>
        <div class="panel" style="max-width: 460px">
            <p class="muted" style="margin-top: 0">Un .zip con un archivo CSV por tema: cuenta, portafolios, transacciones, asignación objetivo, lista de Mercado, alertas de precio y dividendos esperados.
                Las filas de un portafolio en transacciones.csv, sin la columna "portafolio", se pueden volver a importar.</p>
            <a class="btn" href="{{ route('cuenta.exportar') }}">Descargar mis datos (.zip)</a>
        </div>
    @endunless

    <h2 id="dos-factores">Doble factor</h2>
    <div class="panel" style="max-width: 460px">
        @if (session('codigos_recuperacion'))
            <div class="alert" role="status">
                <strong>Guarda estos códigos de recuperación ahora: no se vuelven a mostrar.</strong>
                Cada uno sirve una sola vez para entrar si no tienes tu teléfono.
                <pre style="margin: 10px 0 0; font-size: var(--fs-2)">{{ implode("\n", session('codigos_recuperacion')) }}</pre>
            </div>
        @endif

        @if ($user->tieneDosFactores())
            <p><span class="chip abierto">activo</span> desde el {{ $user->dos_factores_confirmado_en->format('d/m/Y') }}. Al iniciar sesión se te pide el código de tu app.
                Te quedan {{ count($user->dos_factores_recuperacion ?? []) }} códigos de recuperación.</p>
            <form method="POST" action="{{ route('cuenta.dos-factores.desactivar') }}">
                @csrf
                @method('DELETE')
                <label for="df_password_actual">Contraseña actual</label>
                <input id="df_password_actual" type="password" name="password_actual" required autocomplete="current-password">
                @error('password_actual', 'dosFactores') <div class="error">{{ $message }}</div> @enderror

                <label for="df_codigo">Código de tu app</label>
                <input id="df_codigo" name="codigo" required inputmode="numeric" autocomplete="one-time-code" maxlength="20">
                @error('codigo', 'dosFactores') <div class="error">{{ $message }}</div> @enderror

                <div class="actions">
                    <button type="submit" class="btn-secondary">Desactivar doble factor</button>
                </div>
            </form>
        @elseif ($clavePendiente)
            <p>1. Escanea este código con tu app de autenticación (Google Authenticator, Microsoft Authenticator, Authy…).</p>
            <div style="display: inline-block; padding: 8px; background: #fff; border-radius: var(--radius-sm)" role="img" aria-label="Código QR para la app de autenticación">{!! $qr !!}</div>
            <p class="help">¿No puedes escanearlo? Escribe esta clave en la app: <code style="user-select: all">{{ trim(chunk_split($clavePendiente, 4, ' ')) }}</code></p>
            <p>2. Escribe el código de 6 dígitos que te muestra.</p>
            <form method="POST" action="{{ route('cuenta.dos-factores.confirmar') }}">
                @csrf
                <label for="codigo">Código</label>
                <input id="codigo" name="codigo" required inputmode="numeric" autocomplete="one-time-code" maxlength="6">
                @error('codigo') <div class="error">{{ $message }}</div> @enderror

                <div class="actions">
                    <button type="submit">Confirmar y activar</button>
                </div>
            </form>
        @else
            <p class="muted">Protege tu cuenta: además de tu contraseña, al entrar se te pedirá un código de 6 dígitos de una app en tu teléfono.</p>
            <form method="POST" action="{{ route('cuenta.dos-factores.iniciar') }}">
                @csrf
                <button type="submit">Activar doble factor</button>
            </form>
        @endif
    </div>
@endsection
