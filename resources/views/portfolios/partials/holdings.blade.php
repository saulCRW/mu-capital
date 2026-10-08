{{--
    Pestaña "Holdings": tabla tipo screener. Se ordena con clic en el encabezado, se agrupa
    por moneda, tipo, sector o exchange (con subtotales) y puede incluir las posiciones vendidas.
    Todo va en la URL (?vista=holdings&orden=valor&dir=desc&agrupar=sector&vendidas=1).
--}}
@php
    $etiquetaGrupo = fn (string $clave) => $opciones['agrupar'] === 'tipo' ? (\App\Modules\Assets\Models\Asset::ETIQUETAS[$clave] ?? $clave) : $clave;
    // Enlace del encabezado: la misma columna invierte el orden; otra empieza de mayor a menor (A→Z en símbolo).
    $columna = function (string $orden, string $texto, bool $numerica = true) use ($portfolio, $opciones) {
        $actual = $opciones['orden'] === $orden;
        $dir = $actual ? ($opciones['dir'] === 'asc' ? 'desc' : 'asc') : ($orden === 'simbolo' ? 'asc' : 'desc');
        $url = route('portfolios.show', array_filter([$portfolio, 'vista' => 'holdings', 'orden' => $orden, 'dir' => $dir,
            'agrupar' => $opciones['agrupar'], 'vendidas' => $opciones['vendidas'] ? 1 : null]));
        $flecha = $actual ? ($opciones['dir'] === 'asc' ? ' ▲' : ' ▼') : '';
        $aria = $actual ? ($opciones['dir'] === 'asc' ? 'ascending' : 'descending') : 'none';

        return new \Illuminate\Support\HtmlString('<th class="'.($numerica ? 'num' : '').'" aria-sort="'.$aria.'"><a class="orden" href="'.e($url).'">'.e($texto).$flecha.'</a></th>');
    };
    $enVivo = $opciones['agrupar'] === null;
@endphp

@if ($saldo['vacio'])
    <p class="muted">Este portafolio todavía no tiene transacciones. Empieza con un DEPOSITO o importa tus movimientos.</p>
@else
    <style>
        th a.orden { color: inherit; text-decoration: none; }
        th a.orden:hover { color: var(--text); }
        tr.grupo td { font-weight: 600; background: var(--bg-soft); }
        tr.subtotal td { font-weight: 600; }
        tr.vendida td { color: var(--muted); }
    </style>

    <form method="GET" class="filters">
        <input type="hidden" name="vista" value="holdings">
        <input type="hidden" name="orden" value="{{ $opciones['orden'] }}">
        <input type="hidden" name="dir" value="{{ $opciones['dir'] }}">
        <div>
            <label for="agrupar">Agrupar por</label>
            <select id="agrupar" name="agrupar">
                <option value="">Sin agrupar</option>
                @foreach (\App\Modules\Portfolios\Services\HoldingsTable::AGRUPACIONES as $clave => $texto)
                    <option value="{{ $clave }}" @selected($opciones['agrupar'] === $clave)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:0">
            <label for="vendidas" style="display:flex;gap:8px;align-items:center;margin-bottom:10px">
                <input id="vendidas" type="checkbox" name="vendidas" value="1" @checked($opciones['vendidas']) style="width:auto;height:auto"> Mostrar posiciones vendidas
            </label>
        </div>
        <button type="submit" class="btn btn-secondary">Aplicar</button>
    </form>

    @if ($holdings['grupos'] === [])
        <p class="muted">{{ $opciones['vendidas'] ? 'Este portafolio no ha tenido posiciones.' : 'No hay posiciones abiertas. Marca "Mostrar posiciones vendidas" para ver las cerradas.' }}</p>
    @else
        <div style="overflow-x:auto">
            <table>
                <thead>
                    <tr>
                        {{ $columna('simbolo', 'Símbolo', false) }}
                        {{ $columna('cantidad', 'Cantidad') }}
                        {{ $columna('costo', 'Costo prom.') }}
                        {{ $columna('precio', 'Precio') }}
                        {{ $columna('valor', 'Valor ('.$portfolio->moneda_base.')') }}
                        {{ $columna('peso', 'Peso') }}
                        {{ $columna('gp_no_realizada', 'G/P no realizada') }}
                        {{ $columna('gp_realizada', 'G/P realizada') }}
                        {{ $columna('rendimiento', 'Rendimiento') }}
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($holdings['grupos'] as $grupo)
                        @if ($grupo['clave'] !== null)
                            <tr class="grupo"><td colspan="10">{{ $etiquetaGrupo($grupo['clave']) }}</td></tr>
                        @endif
                        @foreach ($grupo['filas'] as $p)
                            <tr @class(['vendida' => ! $p['abierta']])
                                @if ($enVivo && $p['abierta']) data-posicion="{{ $p['asset_id'] }}" @endif>
                                <td>
                                    <a href="{{ route('portfolios.activo', [$portfolio, $p['asset_id']]) }}"><strong>{{ $p['simbolo'] }}</strong></a> <span class="muted">{{ \Illuminate\Support\Str::limit($p['nombre'], 26) }}</span>
                                    @unless ($p['abierta']) <span class="chip">vendida</span> @endunless
                                </td>
                                <td class="num">{{ $cantidad($p['cantidad']) }}</td>
                                <td class="num">{{ $p['costo_promedio_local'] === null ? '—' : $dinero($p['costo_promedio_local']).' '.$p['moneda'] }}</td>
                                <td class="num cotizacion"><span data-vivo="precio">{{ $p['precio'] === null ? '—' : $dinero($p['precio']) }}</span> {{ $p['moneda'] }}</td>
                                <td class="num" data-vivo="valor">{{ $dinero($p['valor_base']) }}</td>
                                <td class="num" data-vivo="peso">{{ $porcentaje($p['peso']) }}</td>
                                <td class="num {{ $signo($p['gp_no_realizada']) }}" data-vivo="gp">{{ $dinero($p['gp_no_realizada']) }}</td>
                                <td class="num {{ $signo($p['gp_realizada']) }}">{{ $dinero($p['gp_realizada']) }}</td>
                                <td class="num {{ $signo($p['rendimiento_pct']) }}" data-vivo="rendimiento">{{ $porcentaje($p['rendimiento_pct']) }}</td>
                                <td>@if ($p['abierta'])<a href="{{ route('portfolios.operar', [$portfolio, 'tipo' => 'VENTA', 'asset_id' => $p['asset_id']]) }}">Vender</a>@endif</td>
                            </tr>
                        @endforeach
                        @if ($grupo['clave'] !== null)
                            @php $st = $grupo['subtotal']; @endphp
                            <tr class="subtotal">
                                <td colspan="4">Subtotal {{ $etiquetaGrupo($grupo['clave']) }}</td>
                                <td class="num">{{ $dinero($st['valor_base']) }}</td>
                                <td class="num">{{ $porcentaje($st['peso']) }}</td>
                                <td class="num {{ $signo($st['gp_no_realizada']) }}">{{ $dinero($st['gp_no_realizada']) }}</td>
                                <td class="num {{ $signo($st['gp_realizada']) }}">{{ $dinero($st['gp_realizada']) }}</td>
                                <td class="num {{ $signo($st['rendimiento_pct']) }}">{{ $porcentaje($st['rendimiento_pct']) }}</td>
                                <td></td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    @php $t = $holdings['total']; @endphp
                    <tr>
                        <td colspan="4">Total posiciones</td>
                        <td class="num">{{ $dinero($t['valor_base']) }}</td>
                        <td class="num">{{ $porcentaje($t['peso']) }}</td>
                        <td class="num {{ $signo($t['gp_no_realizada']) }}">{{ $dinero($t['gp_no_realizada']) }}</td>
                        <td class="num {{ $signo($t['gp_realizada']) }}">{{ $dinero($t['gp_realizada']) }}</td>
                        <td class="num {{ $signo($t['rendimiento_pct']) }}">{{ $porcentaje($t['rendimiento_pct']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p class="help">
            Costo promedio y precio en la moneda del activo; valor y G/P en {{ $portfolio->moneda_base }}. Peso sobre el valor total (incluye efectivo).
            Rendimiento = (G/P no realizada + G/P realizada + dividendos e intereses) ÷ lo invertido.
            @unless ($enVivo) Agrupada, la tabla no se actualiza en vivo (los subtotales usan el último cierre). @endunless
        </p>
    @endif
@endif
