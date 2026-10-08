{{--
    Pestaña "Resumen": rendimiento contra el benchmark (1M, 3M, YTD, 1A, Todo),
    asignación por clase, sector y moneda, y top ganadores / perdedores.
--}}
@if ($saldo['vacio'])
    <p class="muted">Este portafolio todavía no tiene transacciones. Empieza con un DEPOSITO o importa tus movimientos.</p>
@else
    @php
        $grafica = $resumen['grafica'];
        $hayGrafica = count($grafica['rangos']['TODO']['portafolio']) >= 2;
        $etiqueta = fn (string $dimension, string $clave) => $dimension === 'clase' ? (\App\Modules\Assets\Models\Asset::ETIQUETAS[$clave] ?? $clave) : $clave;
    @endphp
    <style>
        .resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
        .barras { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .barras li { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 2px 8px; font-size: var(--fs-1); }
        .barras .barra { grid-column: 1 / -1; height: 6px; background: var(--bg-soft); border-radius: var(--radius-sm); overflow: hidden; }
        .barras .barra span { display: block; height: 6px; background: var(--muted); }
        .panel h3 { margin: 0 0 12px; font-size: var(--fs-2); font-weight: 600; }
        .panel table { border: none; }
        .panel th, .panel td { padding: 0 6px; }
    </style>

    <h2>Rendimiento vs benchmark</h2>
    @if (! $hayGrafica)
        <p class="muted">La gráfica aparece cuando el portafolio tiene al menos dos días hábiles de historial.</p>
    @else
        @include('portfolios.partials.grafica')
    @endif

    <h2>Asignación</h2>
    <div class="resumen-grid">
        @foreach (\App\Modules\Portfolios\Services\PortfolioOverview::DIMENSIONES as $dimension => $titulo)
            <div class="panel">
                <h3>{{ $titulo }}</h3>
                @if ($resumen['asignacion'][$dimension] === [])
                    <p class="muted">Sin datos.</p>
                @else
                    <ul class="barras">
                        @foreach ($resumen['asignacion'][$dimension] as $g)
                            <li>
                                <span>{{ $etiqueta($dimension, $g['clave']) }}</span>
                                <span class="num">{{ $porcentaje($g['peso']) }}</span>
                                <span class="barra" role="img" aria-label="{{ $porcentaje($g['peso']) }}"><span style="width: {{ round(max(0, $g['peso']) * 100, 2) }}%"></span></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    <h2>Top ganadores y perdedores <span class="muted" style="font-weight:400">G/P no realizada desde la compra</span></h2>
    <div class="resumen-grid">
        @foreach (['ganadores' => 'Ganadores', 'perdedores' => 'Perdedores'] as $clave => $titulo)
            <div class="panel">
                <h3>{{ $titulo }}</h3>
                @if ($resumen['top'][$clave] === [])
                    <p class="muted">{{ $clave === 'ganadores' ? 'Ninguna posición en ganancia.' : 'Ninguna posición en pérdida.' }}</p>
                @else
                    <table>
                        <tbody>
                            @foreach ($resumen['top'][$clave] as $p)
                                <tr>
                                    <td><strong>{{ $p['simbolo'] }}</strong></td>
                                    <td class="num {{ $signo($p['gp_no_realizada']) }}">{{ $dinero($p['gp_no_realizada']) }}</td>
                                    <td class="num {{ $signo($p['gp_no_realizada_pct']) }}">{{ ($p['gp_no_realizada_pct'] > 0 ? '+' : '').$porcentaje($p['gp_no_realizada_pct']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        @endforeach
    </div>
@endif
