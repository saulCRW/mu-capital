{{--
    Alertas de precio disparadas que el usuario todavía no ha visto (Inicio y Mercado).
    "Entendido" las marca como vistas. En Mercado, el script agrega aquí las que se disparan en vivo.
--}}
@php
    $avisos = \App\Modules\Prices\Models\PriceAlert::de(auth()->id())->sinVer()->with('asset:id,simbolo,moneda')->orderByDesc('disparada_en')->get();
@endphp
<div id="avisos-alertas" class="alert alert-warn" role="alert" @if ($avisos->isEmpty()) hidden @endif>
    <div style="flex:1">
        <strong>Alertas de precio</strong>
        <ul id="avisos-lista" style="margin:4px 0 0;padding-left:18px">
            @foreach ($avisos as $alerta)
                <li>{{ \App\Modules\Prices\Services\PriceAlertService::mensaje($alerta) }} · {{ $alerta->disparada_en->format('d/m H:i') }}</li>
            @endforeach
        </ul>
    </div>
    <form method="POST" action="{{ route('mercado.alertas.vistas') }}">
        @csrf
        <button type="submit" class="btn btn-secondary">Entendido</button>
    </form>
</div>
