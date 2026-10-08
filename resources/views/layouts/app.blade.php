@php
    $usuario = auth()->user();
    $menu = $usuario->isSuperadmin()
        ? [
            ['superadmin.users.index', 'superadmin.users.*', 'usuarios', 'Usuarios'],
            ['mercado', 'mercado', 'mercado', 'Mercado'],
            ['superadmin.precios.index', 'superadmin.precios.*', 'precios', 'Precios'],
            ['assets.index', 'assets.*', 'activos', 'Activos'],
        ]
        : [
            ['dashboard', 'dashboard', 'inicio', 'Inicio'],
            ['portfolios.index', 'portfolios.*', 'portafolios', 'Mis portafolios'],
            ['mercado', 'mercado', 'mercado', 'Mercado'],
            ['dividendos', 'dividendos', 'dividendos', 'Dividendos'],
            ['assets.index', 'assets.*', 'activos', 'Activos'],
            ['plan', 'plan', 'plan', 'Mi plan'],
        ];
    $iniciales = collect(preg_split('/\s+/', trim($usuario->nombre)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · MU Intelligence</title>
    @include('layouts.partials.tema')
    @include('layouts.partials.styles')
    <style>
        .shell { display: flex; min-height: 100vh; }

        /* ── Barra lateral estrecha ─────────────────────────── */
        .sidebar { position: sticky; top: 0; height: 100vh; width: 208px; flex-shrink: 0; display: flex; flex-direction: column; padding: 16px 10px 14px; background: var(--bg); border-right: 1px solid var(--border); }
        .marca { display: flex; align-items: center; gap: 8px; padding: 0 6px 18px; color: var(--text); text-decoration: none; }
        .marca .logo { display: grid; place-items: center; width: 28px; height: 28px; border-radius: var(--radius-sm); font-size: var(--fs-1); font-weight: 700; color: var(--on-accent); background: var(--accent); }
        .marca .nombre { font-size: var(--fs-2); font-weight: 600; line-height: 1.2; }
        .sidebar nav { display: flex; flex-direction: column; gap: 2px; }
        .sidebar nav a { display: flex; align-items: center; gap: 10px; padding: 7px 8px; border-radius: var(--radius-sm); color: var(--muted); font-weight: 500; text-decoration: none; }
        .sidebar nav a svg { width: 16px; height: 16px; }
        .sidebar nav a:hover { background: var(--bg-hover); color: var(--text); }
        .sidebar nav a.activo { background: var(--bg-soft); color: var(--text); font-weight: 600; }
        .sidebar .pie { margin-top: auto; display: flex; flex-direction: column; gap: 8px; }
        .persona { display: flex; align-items: center; gap: 8px; padding: 6px; border-radius: var(--radius-sm); color: inherit; text-decoration: none; }
        .persona:hover, .persona.activa { background: var(--bg-hover); color: inherit; }
        .persona .avatar { display: grid; place-items: center; width: 28px; height: 28px; flex-shrink: 0; border-radius: var(--radius-sm); font-size: var(--fs-1); font-weight: 600; color: var(--text); background: var(--bg-soft); border: 1px solid var(--border); }
        .persona .datos { min-width: 0; line-height: 1.25; }
        .persona .datos strong { display: block; font-size: var(--fs-1); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .persona .datos span { font-size: var(--fs-1); color: var(--muted); }
        .botones-pie { display: flex; gap: 6px; }
        .boton-lateral { flex: 1; height: 30px; padding: 0 8px; font-size: var(--fs-1); font-weight: 500; background: var(--bg); border-color: var(--border); color: var(--muted); }
        .boton-lateral:hover { background: var(--bg-hover); border-color: var(--border); color: var(--text); }
        .boton-lateral svg { width: 14px; height: 14px; }

        /* ── Contenido ──────────────────────────────────────── */
        .content { flex: 1; min-width: 0; padding: 28px 36px 48px; max-width: 1240px; }
        .page-head { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .page-head h1 { margin: 0; }
        form.narrow { max-width: 460px; padding: 18px 20px 20px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); }
        form.narrow > label:first-of-type { margin-top: 0; }
        form.narrow .actions { margin-top: 20px; }
        /* Tablas de la aplicación: borde de 1 px, encabezado suave y hover sutil. */
        .content table { border-collapse: separate; border-spacing: 0; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); }
        .content thead th { background: var(--bg-soft); }
        .content thead th:first-child { border-top-left-radius: var(--radius); }
        .content thead th:last-child { border-top-right-radius: var(--radius); }
        .content tbody tr:hover { background: var(--bg-hover); }
        .content tbody tr:last-child td { border-bottom: none; }
        .content tfoot td { font-weight: 600; background: var(--bg-soft); border-top: 1px solid var(--border); border-bottom: none; }
        /* En pantallas anchas las tablas caben: sin el scroll horizontal del contenedor,
           el encabezado (sticky) queda fijo al bajar por la página. */
        @media (min-width: 1280px) {
            .content div[style*="overflow-x:auto"] { overflow: visible !important; }
        }
        /* En tablet y teléfono cada tabla se desplaza de lado dentro de su espacio; la página no. */
        @media (max-width: 1279px) {
            .content table { display: block; max-width: 100%; overflow-x: auto; }
        }

        @media (max-width: 900px) {
            .shell { flex-direction: column; }
            .sidebar { z-index: 10; height: auto; width: auto; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 8px 12px; padding: 8px 12px; border-right: none; border-bottom: 1px solid var(--border); }
            .marca { padding: 0; }
            .sidebar nav { order: 3; flex-direction: row; width: 100%; overflow-x: auto; gap: 4px; padding-bottom: 2px; }
            .sidebar nav a { white-space: nowrap; padding: 6px 8px; }
            .sidebar .pie { margin: 0 0 0 auto; flex-direction: row; align-items: center; }
            .persona .datos { display: none; }
            .boton-lateral > span > span, .boton-lateral > span:not(.oscuro):not(.claro) { display: none; }
            .content { padding: 20px 16px 40px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="marca" href="{{ route('dashboard') }}">
            <span class="logo">MU</span>
            <span class="nombre">MU Intelligence</span>
        </a>
        <nav aria-label="Menú principal">
            @foreach ($menu as [$ruta, $patron, $icono, $texto])
                <a href="{{ route($ruta) }}" @class(['activo' => request()->routeIs($patron)]) @if (request()->routeIs($patron)) aria-current="page" @endif>
                    @include('layouts.partials.icono', ['nombre' => $icono]) {{ $texto }}
                </a>
            @endforeach
        </nav>
        <div class="pie">
            <a href="{{ route('cuenta.edit') }}" @class(['persona', 'activa' => request()->routeIs('cuenta.*')]) title="Mi cuenta">
                <span class="avatar">{{ $iniciales }}</span>
                <span class="datos"><strong>{{ $usuario->nombre }}</strong>@if ($usuario->isSuperadmin())<span>Superadmin</span>@endif</span>
            </a>
            <div class="botones-pie">
                <button type="button" class="boton-lateral tema-boton" data-cambiar-tema title="Cambiar entre tema claro y oscuro">
                    <span class="oscuro tema-boton">@include('layouts.partials.icono', ['nombre' => 'luna'])<span>Oscuro</span></span>
                    <span class="claro tema-boton">@include('layouts.partials.icono', ['nombre' => 'sol'])<span>Claro</span></span>
                </button>
                <form method="POST" action="{{ route('logout') }}" style="flex:1;display:flex">
                    @csrf
                    <button type="submit" class="boton-lateral" title="Cerrar sesión">@include('layouts.partials.icono', ['nombre' => 'salir'])<span>Salir</span></button>
                </form>
            </div>
        </div>
    </aside>
    <main class="content">
        @if (session('status'))
            <div class="alert" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->has('general'))
            <div class="alert alert-error" role="alert">{{ $errors->first('general') }}</div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
