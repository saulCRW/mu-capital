<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · MU Intelligence</title>
    @include('layouts.partials.styles')
    <style>
        .shell { display: flex; min-height: 100vh; }
        .sidebar { width: 200px; flex-shrink: 0; padding: 20px 16px; border-right: 1px solid var(--border); background: var(--bg-soft); }
        .sidebar .brand { font-weight: 700; margin-bottom: 24px; }
        .sidebar nav a { display: block; padding: 6px 8px; border-radius: 6px; color: var(--text); text-decoration: none; }
        .sidebar nav a:hover { background: var(--border); }
        .sidebar .who { margin-top: 32px; font-size: 12px; color: var(--muted); }
        .content { flex: 1; padding: 32px 40px; max-width: 1100px; }
        .page-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .page-head h1 { margin: 0; }
        form.narrow { max-width: 420px; }
        form.narrow .actions { margin-top: 20px; }
    </style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand">MU Intelligence</div>
        <nav>
            @if (auth()->user()->isSuperadmin())
                <a href="{{ route('superadmin.users.index') }}">Usuarios</a>
                <a href="{{ route('mercado') }}">Mercado</a>
                <a href="{{ route('assets.index') }}">Activos</a>
            @else
                <a href="{{ route('dashboard') }}">Inicio</a>
                <a href="{{ route('portfolios.index') }}">Mis portafolios</a>
                <a href="{{ route('mercado') }}">Mercado</a>
                <a href="{{ route('assets.index') }}">Activos</a>
                <a href="{{ route('plan') }}">Mi plan</a>
            @endif
            <a href="{{ route('cuenta.edit') }}">Mi cuenta</a>
        </nav>
        <div class="who">
            <div>{{ auth()->user()->nombre }}</div>
            <div>{{ auth()->user()->isSuperadmin() ? 'Superadmin' : 'Plan '.auth()->user()->plan?->nombre }}</div>
            <form method="POST" action="{{ route('logout') }}" style="margin-top:8px">
                @csrf
                <button type="submit" class="btn-link">Cerrar sesión</button>
            </form>
        </div>
    </aside>
    <main class="content">
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif
        @if ($errors->has('general'))
            <div class="alert alert-error">{{ $errors->first('general') }}</div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
