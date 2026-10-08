{{--
    Uso del plan contra sus límites (PlanLimits::uso). Lo usan "Mi plan" y la ficha del
    usuario en la administración. Llegar a un límite es una alerta (ámbar), no una pérdida.
--}}
<style>
    .uso { height: 8px; background: var(--bg-soft); border-radius: var(--radius-sm); overflow: hidden; min-width: 160px; }
    .uso div { height: 8px; background: var(--muted); }
    .uso div.lleno { background: var(--warn); }
</style>
<table style="max-width:760px">
    <thead>
        <tr><th>Límite</th><th class="num">Usado</th><th class="num">Máximo</th><th style="width:220px"></th></tr>
    </thead>
    <tbody>
        @foreach ($uso as $u)
            @php $fraccion = $u['maximo'] > 0 ? min(1, $u['usado'] / $u['maximo']) : 1; @endphp
            <tr>
                <td>{{ $u['limite'] }}</td>
                <td class="num {{ $u['usado'] >= $u['maximo'] ? 'warn' : '' }}">{{ number_format($u['usado']) }}</td>
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
