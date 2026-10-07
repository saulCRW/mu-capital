@php
    $usuario = auth()->user();
    $menu = $usuario->isSuperadmin()
        ? [
            ['superadmin.users.index', 'superadmin.users.*', 'usuarios', 'Usuarios'],
            ['mercado', 'mercado', 'mercado', 'Mercado'],
            ['assets.index', 'assets.*', 'activos', 'Activos'],
        ]
        : [
            ['dashboard', 'dashboard', 'inicio', 'Inicio'],
            ['portfolios.index', 'portfolios.*', 'portafolios', 'Mis portafolios'],
            ['mercado', 'mercado', 'mercado', 'Mercado'],
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

        /* ── Barra lateral ──────────────────────────────────── */
        .sidebar { position: sticky; top: 0; height: 100vh; width: 248px; flex-shrink: 0; display: flex; flex-direction: column; padding: 22px 14px 18px; background: var(--side-bg); color: var(--side-text); }
        .marca { display: flex; align-items: center; gap: 10px; padding: 0 8px 22px; color: #fff; text-decoration: none; }
        .marca:hover { color: #fff; }
        .marca .logo { display: grid; place-items: center; width: 36px; height: 36px; border-radius: 10px; font-size: 13px; font-weight: 800; letter-spacing: -.02em; color: #fff; background: linear-gradient(135deg, #4f6bff, #8b5cf6); box-shadow: 0 6px 18px rgba(99, 102, 241, .35); }
        .marca .nombre { font-size: 15px; font-weight: 700; letter-spacing: -.01em; line-height: 1.15; }
        .marca .lema { display: block; font-size: 11px; font-weight: 500; color: var(--side-muted); }
        .sidebar nav { display: flex; flex-direction: column; gap: 2px; }
        .sidebar nav a { position: relative; display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 9px; color: var(--side-text); font-weight: 500; text-decoration: none; transition: background-color .15s, color .15s; }
        .sidebar nav a svg { opacity: .8; }
        .sidebar nav a:hover { background: var(--side-hover); color: #fff; }
        .sidebar nav a.activo { background: var(--side-active); color: #fff; }
        .sidebar nav a.activo svg { opacity: 1; color: #a5b4ff; }
        .sidebar nav a.activo::before { content: ""; position: absolute; left: -14px; top: 8px; bottom: 8px; width: 3px; border-radius: 0 3px 3px 0; background: linear-gradient(180deg, #6d84ff, #a78bfa); }
        .sidebar .pie { margin-top: auto; display: flex; flex-direction: column; gap: 10px; }
        .persona { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 11px; background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .06); color: inherit; text-decoration: none; }
        .persona:hover { background: rgba(255, 255, 255, .07); color: inherit; }
        .persona .avatar { display: grid; place-items: center; width: 34px; height: 34px; flex-shrink: 0; border-radius: 50%; font-size: 12px; font-weight: 700; color: #fff; background: linear-gradient(135deg, #3b82f6, #8b5cf6); }
        .persona .datos { min-width: 0; line-height: 1.25; }
        .persona .datos strong { display: block; color: #fff; font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .persona .datos span { font-size: 11px; color: var(--side-muted); }
        .persona.activa { border-color: rgba(165, 180, 255, .35); }
        .botones-pie { display: flex; gap: 6px; }
        .boton-lateral { flex: 1; height: 34px; padding: 0 10px; font-size: 12px; font-weight: 500; border-radius: 9px; background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .08); color: var(--side-text); box-shadow: none; }
        .boton-lateral:hover { background: rgba(255, 255, 255, .1); color: #fff; }
        .boton-lateral svg { width: 16px; height: 16px; }

        /* ── Contenido ──────────────────────────────────────── */
        .content { flex: 1; min-width: 0; padding: 32px 40px 48px; max-width: 1240px; }
        .page-head { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .page-head h1 { margin: 0; }
        form.narrow { max-width: 460px; padding: 22px 24px 24px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); }
        form.narrow > label:first-of-type { margin-top: 0; }
        form.narrow .actions { margin-top: 22px; }
        /* Tablas de la aplicación: tarjetas con encabezado suave. */
        .content table { border-collapse: separate; border-spacing: 0; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); overflow: hidden; }
        .content thead th { background: var(--bg-soft); }
        .content tbody tr { transition: background-color .12s; }
        .content tbody tr:hover { background: var(--bg-hover); }
        .content tbody tr:last-child td { border-bottom: none; }
        .content tfoot td { font-weight: 600; background: var(--bg-soft); border-top: 1px solid var(--border); border-bottom: none; }
        .content table table { box-shadow: none; }

        @media (max-width: 900px) {
            .shell { flex-direction: column; }
            .sidebar { z-index: 10; height: auto; width: auto; flex-direction: row; flex-wrap: wrap; align-items: center; gap: 8px 12px; padding: 10px 14px; }
            .marca { padding: 0; }
            .marca .lema { display: none; }
            .sidebar nav { order: 3; flex-direction: row; width: 100%; overflow-x: auto; gap: 4px; padding-bottom: 2px; }
            .sidebar nav a { white-space: nowrap; padding: 7px 10px; }
            .sidebar nav a.activo::before { display: none; }
            .sidebar .pie { margin: 0 0 0 auto; flex-direction: row; align-items: center; }
            .persona { padding: 4px; background: none; border: none; }
            .persona .datos { display: none; }
            .boton-lateral > span > span, .boton-lateral > span:not(.oscuro):not(.claro) { display: none; }
            .content { padding: 22px 16px 40px; }
        }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <a class="marca" href="{{ route('dashboard') }}">
            <span class="logo">MU</span>
            <span class="nombre">MU Intelligence<span class="lema">Tus inversiones, claras</span></span>
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
                <span class="datos"><strong>{{ $usuario->nombre }}</strong><span>{{ $usuario->isSuperadmin() ? 'Superadmin' : 'Plan '.$usuario->plan?->nombre }}</span></span>
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
