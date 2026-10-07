<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · MU Intelligence</title>
    @include('layouts.partials.tema')
    @include('layouts.partials.styles')
    <style>
        .acceso { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); min-height: 100vh; }
        .portada { position: relative; display: flex; flex-direction: column; justify-content: space-between; padding: 44px 52px; color: #e8ecff; background:
            radial-gradient(900px 500px at 15% 10%, rgba(99, 102, 241, .45), transparent 60%),
            radial-gradient(700px 500px at 90% 90%, rgba(139, 92, 246, .35), transparent 60%),
            linear-gradient(160deg, #0b1124 0%, #111a38 55%, #160f33 100%); overflow: hidden; }
        .portada .marca { display: flex; align-items: center; gap: 12px; font-size: 16px; font-weight: 700; }
        .portada .logo { display: grid; place-items: center; width: 40px; height: 40px; border-radius: 11px; font-size: 14px; font-weight: 800; background: linear-gradient(135deg, #4f6bff, #8b5cf6); box-shadow: 0 8px 24px rgba(99, 102, 241, .45); }
        .portada h2 { margin: 0 0 14px; font-size: 34px; line-height: 1.15; font-weight: 700; letter-spacing: -.025em; color: #fff; max-width: 460px; }
        .portada p { margin: 0; max-width: 420px; color: #b8c2e6; font-size: 15px; }
        .portada ul { list-style: none; padding: 0; margin: 28px 0 0; display: grid; gap: 12px; }
        .portada li { display: flex; gap: 10px; align-items: center; color: #d6dcf5; }
        .portada li::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: linear-gradient(135deg, #7c93ff, #b394ff); box-shadow: 0 0 0 4px rgba(124, 147, 255, .15); }
        .portada .grafica { position: absolute; right: -40px; bottom: 90px; width: 70%; opacity: .55; }
        .portada small { color: #7f8bb3; }
        .panel { display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 24px; background: var(--page); position: relative; }
        .panel > .tema-boton { position: absolute; top: 18px; right: 18px; height: 36px; padding: 0 12px; font-size: 12px; }
        .tarjeta { width: 100%; max-width: 400px; padding: 32px 30px; background: var(--bg); border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow); }
        .tarjeta h1 { font-size: 22px; margin-bottom: 6px; }
        .tarjeta form button[type="submit"] { width: 100%; margin-top: 22px; height: 44px; font-size: 15px; background: linear-gradient(135deg, var(--accent), var(--accent-2)); border: none; }
        .tarjeta form button[type="submit"]:hover { filter: brightness(1.07); }
        .tarjeta .below { margin-top: 18px; text-align: center; font-size: 13px; color: var(--muted); }
        @media (max-width: 900px) {
            .acceso { grid-template-columns: 1fr; }
            .portada { padding: 28px 24px; min-height: auto; }
            .portada h2 { font-size: 24px; margin-top: 24px; }
            .portada ul, .portada .grafica, .portada small { display: none; }
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
        <svg class="grafica" viewBox="0 0 400 140" fill="none" aria-hidden="true">
            <path d="M0 120 C40 110 60 118 90 96 S140 70 170 82 220 40 260 52 320 18 400 10" stroke="url(#g)" stroke-width="3" stroke-linecap="round"/>
            <path d="M0 120 C40 110 60 118 90 96 S140 70 170 82 220 40 260 52 320 18 400 10 V140 H0 Z" fill="url(#f)"/>
            <defs>
                <linearGradient id="g" x1="0" x2="400" y1="0" y2="0" gradientUnits="userSpaceOnUse"><stop stop-color="#7c93ff"/><stop offset="1" stop-color="#c4a5ff"/></linearGradient>
                <linearGradient id="f" x1="0" x2="0" y1="0" y2="140" gradientUnits="userSpaceOnUse"><stop stop-color="#7c93ff" stop-opacity=".35"/><stop offset="1" stop-color="#7c93ff" stop-opacity="0"/></linearGradient>
            </defs>
        </svg>
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
