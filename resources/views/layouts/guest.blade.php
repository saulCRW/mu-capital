<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · MU Intelligence</title>
    @include('layouts.partials.tema')
    @include('layouts.partials.styles')
    <style>
        .acceso { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); min-height: 100vh; }
        .portada { display: flex; flex-direction: column; justify-content: space-between; padding: 40px 48px; background: var(--bg); border-right: 1px solid var(--border); }
        .portada .marca { display: flex; align-items: center; gap: 10px; font-size: var(--fs-3); font-weight: 600; }
        .portada .logo { display: grid; place-items: center; width: 32px; height: 32px; border-radius: var(--radius-sm); font-size: var(--fs-1); font-weight: 700; color: var(--on-accent); background: var(--accent); }
        .portada h2 { margin: 0 0 12px; font-size: var(--fs-5); line-height: 1.2; font-weight: 600; letter-spacing: -.015em; max-width: 460px; }
        .portada p { margin: 0; max-width: 420px; color: var(--muted); font-size: var(--fs-3); }
        .portada ul { list-style: none; padding: 0; margin: 24px 0 0; display: grid; gap: 8px; }
        .portada li { padding-left: 14px; border-left: 2px solid var(--border-strong); }
        .portada small { color: var(--muted); }
        .panel { display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 24px; background: var(--page); position: relative; border: none; border-radius: 0; }
        .panel > .tema-boton { position: absolute; top: 16px; right: 16px; height: 32px; padding: 0 10px; font-size: var(--fs-1); }
        .tarjeta { width: 100%; max-width: 380px; padding: 28px 26px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); }
        .tarjeta h1 { margin-bottom: 6px; }
        .tarjeta form button[type="submit"] { width: 100%; margin-top: 20px; height: 40px; }
        .tarjeta .below { margin-top: 16px; text-align: center; font-size: var(--fs-1); color: var(--muted); }
        @media (max-width: 900px) {
            .acceso { grid-template-columns: 1fr; }
            .portada { padding: 24px; border-right: none; border-bottom: 1px solid var(--border); }
            .portada h2 { font-size: var(--fs-4); margin-top: 20px; }
            .portada ul, .portada small { display: none; }
        }
    </style>
</head>
<body>
<div class="acceso">
    <section class="portada">
        <div class="marca"><span class="logo">MU</span> MU Intelligence</div>
        <div>
            <h2>Tus inversiones, claras y en un solo lugar.</h2>
            <p>Registra tus portafolios, sigue el mercado en vivo y mide tu rendimiento real contra los índices.</p>
            <ul>
                <li>Precios y tipo de cambio en vivo</li>
                <li>Rendimiento, riesgo y rebalanceo</li>
                <li>Reportes trimestrales en PDF</li>
            </ul>
        </div>
        <small>© {{ now()->year }} MU Intelligence</small>
    </section>
    <main class="panel">
        <button type="button" class="btn btn-secondary tema-boton" data-cambiar-tema title="Cambiar tema">
            <span class="oscuro tema-boton">@include('layouts.partials.icono', ['nombre' => 'luna']) Oscuro</span>
            <span class="claro tema-boton">@include('layouts.partials.icono', ['nombre' => 'sol']) Claro</span>
        </button>
        <div class="tarjeta">
            @if (session('status'))
                <div class="alert" role="status">{{ session('status') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
