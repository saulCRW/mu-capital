@extends('layouts.app')

@section('title', 'Plan')

@section('content')
    <style>
        .uso { height: 8px; background: var(--bg-soft); border-radius: 4px; overflow: hidden; min-width: 160px; }
        .uso div { height: 8px; background: #2a78d6; border-radius: 4px; }
        .uso div.lleno { background: var(--danger); }
    </style>

    <h1>Plan {{ $plan->nombre }}</h1>
    <p class="muted">Límites de tu cuenta. Al llegar a uno puedes seguir consultando y editando, pero no agregar más.</p>

    <table style="max-width:760px">
        <thead>
            <tr><th>Límite</th><th class="num">Usado</th><th class="num">Máximo</th><th style="width:220px"></th></tr>
        </thead>
        <tbody>
            @foreach ($uso as $u)
                @php $fraccion = $u['maximo'] > 0 ? min(1, $u['usado'] / $u['maximo']) : 1; @endphp
                <tr>
                    <td>{{ $u['limite'] }}</td>
                    <td class="num {{ $u['usado'] >= $u['maximo'] ? 'neg' : '' }}">{{ number_format($u['usado']) }}</td>
                    <td class="num">{{ number_format($u['maximo']) }}</td>
                    <td>
                        <div class="uso" role="img" aria-label="{{ round($fraccion * 100) }} % usado">
                            <div @class(['lleno' => $u['usado'] >= $u['maximo']]) style="width: {{ round($fraccion * 100, 1) }}%"></div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p class="help">Por portafolio se muestra el más cargado.</p>
@endsection
