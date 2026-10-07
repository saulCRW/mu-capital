<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · MU Intelligence</title>
    @include('layouts.partials.styles')
    <style>
        .guest { max-width: 360px; margin: 12vh auto 0; padding: 0 16px; }
        .brand { font-size: 16px; font-weight: 700; margin-bottom: 24px; }
        .guest form button { width: 100%; margin-top: 20px; }
        .guest .below { margin-top: 16px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <main class="guest">
        <div class="brand">MU Intelligence</div>
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
