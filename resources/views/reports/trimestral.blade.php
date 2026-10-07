{{--
    Reporte trimestral. La misma plantilla se muestra en la página y se convierte a PDF con dompdf
    ($pdf = true). dompdf no entiende grid ni flex: la maquetación usa tablas (.rejilla).
--}}
@php
    $pdf ??= false;
    $dinero = fn ($v) => number_format((float) $v, 2, '.', ',');
    $cantidad = fn ($v) => rtrim(rtrim(number_format((float) $v, 6, '.', ','), '0'), '.');
    $porcentaje = fn (?float $v) => $v === null ? '—' : number_format($v * 100, 2, '.', ',').' %';
    $razon = fn (?float $v) => $v === null ? '—' : number_format($v, 2, '.', ',');
    $signo = fn ($v) => $v === null ? '' : ((float) $v > 0 ? 'pos' : ((float) $v < 0 ? 'neg' : ''));
    $fecha = fn (string $f) => \Carbon\Carbon::parse($f)->format('d/m/Y');
    $etiquetaTrimestre = fn (int $t, int $a) => "{$t}T {$a}";

    $r = $reporte['resumen'];
    $vacio = $r['vacio'];
    $estado = $vacio ? null : $r['estado'];
    $periodo = $vacio ? null : $r['periodo'];
    $trimestreActual = collect($reporte['anual']['trimestres'])->firstWhere('trimestre', $reporte['trimestre']);
    $base = $portfolio->moneda_base;
    $abiertas = $vacio ? collect() : collect($estado['posiciones'])
        ->filter(fn ($p) => (float) $p['cantidad'] != 0)
        ->sortByDesc(fn ($p) => (float) $p['valor_base']);
    $simbolos = $vacio ? collect() : collect($estado['posiciones'])->pluck('simbolo', 'asset_id');
    $dimensiones = collect(['clase' => 'Clase de activo', 'sector' => 'Sector', 'moneda' => 'Moneda', 'pais' => 'País'])
        ->filter(fn ($titulo, $clave) => ! $vacio && $r['asignacion'][$clave] !== []);
    $salidas = ['RETIRO', 'COMISION'];

    $evolucion = $reporte['evolucion'];
    $graficas = [
        'valor' => ['Valor del portafolio y capital aportado ('.$base.')', \App\Modules\Reports\Services\SvgLineChart::render([
            ['nombre' => 'Valor', 'puntos' => $evolucion['valor']],
            ['nombre' => 'Capital aportado', 'puntos' => $evolucion['aportado']],
        ], 'dinero', 'Valor del portafolio y capital aportado')],
        'rendimiento' => ['Rendimiento acumulado, base 100', \App\Modules\Reports\Services\SvgLineChart::render([
            ['nombre' => 'Portafolio (TWR)', 'puntos' => $evolucion['indice']],
            ['nombre' => $portfolio->benchmark?->nombre ?? 'Benchmark', 'puntos' => $evolucion['benchmark']],
        ], 'indice', 'Rendimiento acumulado del portafolio contra el benchmark, base 100')],
    ];
    $tituloCorto = 'Reporte '.$etiquetaTrimestre($reporte['trimestre'], $reporte['anio']).' · '.$portfolio->nombre;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tituloCorto }} · MU Intelligence</title>
    @include('layouts.partials.styles')
    <style>
        body { background: var(--bg-soft); }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: end; justify-content: space-between; max-width: 900px; margin: 0 auto; padding: 16px; }
        .toolbar form, .toolbar .actions { display: flex; gap: 8px; align-items: end; }
        .toolbar label { margin: 0 0 4px; }
        .toolbar select { width: auto; }
        .hoja { max-width: 900px; margin: 0 auto 32px; padding: 40px 48px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; }
        .encabezado { padding-bottom: 16px; border-bottom: 2px solid var(--text); }
        .encabezado h1 { font-size: 22px; margin: 4px 0 2px; }
        .encabezado .org { font-size: 12px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }
        .encabezado .meta { text-align: right; font-size: 12px; color: var(--muted); line-height: 1.6; }
        .hoja h2 { font-size: 14px; text-transform: uppercase; letter-spacing: .04em; margin: 28px 0 10px; }
        .hoja h3 { font-size: 12px; color: var(--muted); margin: 0 0 6px; }
        .hoja table th, .hoja table td { height: 30px; padding: 0 8px; font-size: 13px; }
        .hoja tbody tr:hover { background: none; }
        .hoja tfoot td { font-weight: 600; border-top: 2px solid var(--border); }

        /* Maquetación con tablas (dompdf): celdas sin bordes ni alto fijo. */
        table.rejilla { table-layout: fixed; border-collapse: separate; border-spacing: 0; }
        .hoja table.rejilla > tbody > tr > td { height: auto; padding: 0; border: none; vertical-align: top; }
        .hoja table.rejilla > tbody > tr > td + td { padding-left: 28px; }
        .tile { padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; }
        .tile .label { font-size: 11px; color: var(--muted); }
        .tile .value { font-size: 17px; font-weight: 600; margin-top: 4px; }
        .tile .sub { font-size: 11px; margin-top: 2px; }
        .hoja table.rejilla.kpis > tbody > tr > td + td { padding-left: 10px; }
        .hoja table.datos td { height: 24px; padding: 0; border: none; }
        .hoja table.datos td.etiqueta { color: var(--muted); }
        .barra { margin-bottom: 7px; font-size: 13px; }
        .barra .pista { height: 6px; margin-top: 2px; background: var(--bg-soft); border-radius: 3px; overflow: hidden; }
        .barra .relleno { height: 6px; background: var(--accent); border-radius: 3px; }
        .barra .peso { float: right; font-variant-numeric: tabular-nums; }
        .grafica { margin-bottom: 18px; }
        .notas { margin-top: 32px; padding-top: 12px; border-top: 1px solid var(--border); font-size: 11px; color: var(--muted); line-height: 1.5; }

        @unless ($pdf)
        /* dompdf aplica las reglas de pantalla sin evaluar el ancho: este bloque no va en el PDF. */
        @media screen and (max-width: 640px) {
            .hoja { padding: 24px 16px; }
            table.rejilla, table.rejilla > tbody, table.rejilla > tbody > tr, table.rejilla > tbody > tr > td { display: block; width: auto !important; }
            .hoja table.rejilla > tbody > tr > td + td { padding: 12px 0 0; }
            .encabezado .meta { text-align: left; }
        }
        @endunless
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .hoja { max-width: none; margin: 0; padding: 0; border: none; }
            .hoja h2 { break-after: avoid; }
            table, .grafica { break-inside: avoid; }
            @page { size: letter; margin: 16mm 14mm; }
        }
        @if ($pdf)
            /* dompdf: tipografía con todos los signos (−, —, ←) y sin el marco de pantalla. */
            body { background: #fff; font-family: "DejaVu Sans", sans-serif; font-size: 12px; }
            /* DejaVu solo trae normal y negrita: el 600 caería en otra fuente. */
            h1, h2, h3, th, strong, tfoot td, .tile .value, .tile .label, .encabezado .org, [style*="font-weight:600"] { font-weight: bold; }
            .hoja { max-width: none; margin: 0; padding: 0; border: none; }
            .hoja table th, .hoja table td { font-size: 11px; height: 22px; }
            .hoja table.datos td { height: 18px; }
            .tile .value { font-size: 14px; }
            .barra { font-size: 11px; }
            .hoja h2 { font-size: 12px; margin: 20px 0 8px; }
            .encabezado h1 { font-size: 18px; }
            @page { size: letter; margin: 14mm 12mm; }
        @endif
    </style>
</head>
<body>
    @unless ($pdf)
        <div class="toolbar">
            <a href="{{ route('portfolios.show', $portfolio) }}">← {{ $portfolio->nombre }}</a>
            <form method="GET">
                <div>
                    <label for="trimestre">Trimestre</label>
                    <select id="trimestre" name="trimestre">
                        @foreach ([1, 2, 3, 4] as $t)
                            <option value="{{ $t }}" @selected($reporte['trimestre'] === $t)>{{ $t }}T</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="anio">Año</label>
                    <select id="anio" name="anio">
                        @foreach (range(now()->year, now()->year - 5) as $a)
                            <option value="{{ $a }}" @selected($reporte['anio'] === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Ver</button>
            </form>
            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="window.print()">Imprimir</button>
                <a class="btn" href="{{ route('portfolios.reporte.pdf', [$portfolio, 'anio' => $reporte['anio'], 'trimestre' => $reporte['trimestre']]) }}">Descargar PDF</a>
            </div>
        </div>
    @endunless

    <main class="hoja">
        <table class="rejilla encabezado">
            <tbody>
                <tr>
                    <td style="width:60%">
                        <div class="org">MU Intelligence</div>
                        <h1>Reporte trimestral · {{ $etiquetaTrimestre($reporte['trimestre'], $reporte['anio']) }}</h1>
                        <div>{{ $portfolio->user->nombre }} — {{ $portfolio->nombre }}</div>
                    </td>
                    <td class="meta">
                        Periodo: {{ $fecha($reporte['desde']) }} – {{ $fecha($reporte['hasta']) }}{{ $reporte['cerrado'] ? '' : ' (en curso)' }}<br>
                        Moneda: {{ $base }} · Benchmark: {{ $portfolio->benchmark?->nombre ?? '—' }}<br>
                        Emitido el {{ now()->format('d/m/Y') }}
                    </td>
                </tr>
            </tbody>
        </table>

        @if ($vacio || ! $trimestreActual)
            <p class="muted" style="margin-top:24px">El portafolio no tiene movimientos ni valuaciones en este trimestre.</p>
        @else
            <h2>Resumen del trimestre</h2>
            <table class="rejilla kpis">
                <tbody>
                    <tr>
                        <td>
                            <div class="tile">
                                <div class="label">Valor al {{ $fecha($reporte['hasta']) }}</div>
                                <div class="value">{{ $dinero($trimestreActual['valor_final']) }}</div>
                                <div class="sub muted">inicial: {{ $dinero($trimestreActual['valor_inicial']) }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="tile">
                                <div class="label">Aportaciones netas</div>
                                <div class="value">{{ $dinero($trimestreActual['flujo_neto']) }}</div>
                                <div class="sub muted">depósitos − retiros</div>
                            </div>
                        </td>
                        <td>
                            <div class="tile">
                                <div class="label">Ganancia del trimestre</div>
                                <div class="value {{ $signo($trimestreActual['ganancia']) }}">{{ $dinero($trimestreActual['ganancia']) }}</div>
                                <div class="sub muted">{{ $base }}</div>
                            </div>
                        </td>
                        <td>
                            <div class="tile">
                                <div class="label">Rendimiento (TWR)</div>
                                <div class="value {{ $signo($periodo['twr']) }}">{{ $porcentaje($periodo['twr']) }}</div>
                                <div class="sub muted">benchmark: {{ $porcentaje($periodo['benchmark']) }}</div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <table class="rejilla" style="margin-top:14px">
                <tbody>
                    <tr>
                        <td>
                            <table class="datos">
                                <tbody>
                                    <tr><td class="etiqueta">Tu rendimiento con tus aportaciones (XIRR, anual)</td><td class="num {{ $signo($periodo['xirr']) }}">{{ $porcentaje($periodo['xirr']) }}</td></tr>
                                    <tr>
                                        <td class="etiqueta">Diferencia vs. benchmark</td>
                                        @php $diferencia = $periodo['twr'] !== null && $periodo['benchmark'] !== null ? $periodo['twr'] - $periodo['benchmark'] : null; @endphp
                                        <td class="num {{ $signo($diferencia) }}">{{ $porcentaje($diferencia) }}</td>
                                    </tr>
                                    <tr><td class="etiqueta">Dividendos e intereses (desde el inicio)</td><td class="num">{{ $dinero($estado['ingresos']) }}</td></tr>
                                </tbody>
                            </table>
                        </td>
                        <td>
                            <table class="datos">
                                <tbody>
                                    <tr><td class="etiqueta">Volatilidad (anualizada)</td><td class="num">{{ $porcentaje($periodo['volatilidad']) }}</td></tr>
                                    <tr><td class="etiqueta">Máxima caída</td><td class="num {{ $signo($periodo['max_drawdown']) }}">{{ $porcentaje($periodo['max_drawdown']) }}</td></tr>
                                    <tr><td class="etiqueta">Sharpe · Sortino · Beta</td><td class="num">{{ $razon($periodo['sharpe']) }} · {{ $razon($periodo['sortino']) }} · {{ $razon($periodo['beta']) }}</td></tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2>Evolución {{ $reporte['anio'] }}</h2>
            @foreach ($graficas as [$titulo, $svg])
                @if ($svg !== '')
                    <div class="grafica">
                        <h3>{{ $titulo }}</h3>
                        @if ($pdf)
                            <img src="{{ \App\Modules\Reports\Services\SvgLineChart::dataUri($svg) }}" alt="{{ $titulo }}" style="width:100%">
                        @else
                            <div class="chart">{!! $svg !!}</div>
                        @endif
                    </div>
                @endif
            @endforeach

            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Trimestre</th><th class="num">Valor inicial</th><th class="num">Aportaciones netas</th>
                            <th class="num">Ganancia</th><th class="num">Valor final</th><th class="num">TWR</th><th class="num">Benchmark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reporte['anual']['trimestres'] as $q)
                            <tr @if ($q['trimestre'] === $reporte['trimestre']) style="font-weight:600" @endif>
                                <td>{{ $etiquetaTrimestre($q['trimestre'], $reporte['anio']) }}</td>
                                <td class="num">{{ $dinero($q['valor_inicial']) }}</td>
                                <td class="num">{{ $dinero($q['flujo_neto']) }}</td>
                                <td class="num {{ $signo($q['ganancia']) }}">{{ $dinero($q['ganancia']) }}</td>
                                <td class="num">{{ $dinero($q['valor_final']) }}</td>
                                <td class="num {{ $signo($q['twr']) }}">{{ $porcentaje($q['twr']) }}</td>
                                <td class="num">{{ $porcentaje($q['benchmark']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Año a la fecha</td>
                            <td class="num">{{ $dinero($reporte['anual']['anio']['valor_inicial']) }}</td>
                            <td class="num">{{ $dinero($reporte['anual']['anio']['flujo_neto']) }}</td>
                            <td class="num {{ $signo($reporte['anual']['anio']['ganancia']) }}">{{ $dinero($reporte['anual']['anio']['ganancia']) }}</td>
                            <td class="num">{{ $dinero($reporte['anual']['anio']['valor_final']) }}</td>
                            <td class="num {{ $signo($reporte['anual']['anio']['twr']) }}">{{ $porcentaje($reporte['anual']['anio']['twr']) }}</td>
                            <td class="num">{{ $porcentaje($reporte['anual']['anio']['benchmark']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <h2>Composición al {{ $fecha($reporte['hasta']) }}</h2>
            <table class="rejilla">
                <tbody>
                    @foreach ($dimensiones->chunk(2) as $fila)
                        <tr>
                            @foreach ($fila as $clave => $titulo)
                                <td style="padding-bottom:12px">
                                    <h3>{{ $titulo }}</h3>
                                    @foreach ($r['asignacion'][$clave] as $g)
                                        <div class="barra">
                                            <span class="peso">{{ $porcentaje($g['peso']) }}</span>
                                            <span>{{ $clave === 'clase' ? (\App\Modules\Assets\Models\Asset::ETIQUETAS[$g['clave']] ?? $g['clave']) : $g['clave'] }}</span>
                                            <div class="pista"><div class="relleno" style="width: {{ round($g['peso'] * 100, 1) }}%"></div></div>
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                            @if ($fila->count() === 1)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <h2>Posiciones</h2>
            <div style="overflow-x:auto">
                <table>
                    <thead>
                        <tr>
                            <th>Activo</th><th class="num">Títulos</th><th class="num">Costo prom.</th><th class="num">Precio</th>
                            <th class="num">Valor ({{ $base }})</th><th class="num">G/P no realizada</th><th class="num">Peso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($abiertas as $p)
                            <tr>
                                <td><strong>{{ $p['simbolo'] }}</strong> <span class="muted">{{ \Illuminate\Support\Str::limit($p['nombre'], 24) }}</span></td>
                                <td class="num">{{ $cantidad($p['cantidad']) }}</td>
                                <td class="num">{{ $dinero($p['costo_promedio_local']) }} {{ $p['moneda'] }}</td>
                                <td class="num">{{ $p['precio'] === null ? '—' : $dinero($p['precio']) }} {{ $p['moneda'] }}</td>
                                <td class="num">{{ $dinero($p['valor_base']) }}</td>
                                <td class="num {{ $signo($p['gp_no_realizada']) }}">{{ $dinero($p['gp_no_realizada']) }} ({{ $porcentaje($p['gp_no_realizada_pct']) }})</td>
                                <td class="num">{{ $porcentaje((float) $estado['valor_total'] > 0 ? (float) $p['valor_base'] / (float) $estado['valor_total'] : null) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">Sin posiciones abiertas.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Efectivo</td><td></td><td></td><td></td>
                            <td class="num">{{ $dinero($estado['efectivo']) }}</td><td></td>
                            <td class="num">{{ $porcentaje((float) $estado['valor_total'] > 0 ? (float) $estado['efectivo'] / (float) $estado['valor_total'] : null) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <table class="rejilla">
                <tbody>
                    <tr>
                        <td>
                            <h2>Mejores y peores <span class="muted" style="text-transform:none;font-weight:400">desde la compra</span></h2>
                            <table>
                                <tbody>
                                    @forelse ([...$r['top']['ganadores'], ...array_reverse($r['top']['perdedores'])] as $p)
                                        <tr>
                                            <td>{{ $p['simbolo'] }}</td>
                                            <td class="num {{ $signo($p['gp_no_realizada_pct']) }}">{{ $porcentaje($p['gp_no_realizada_pct']) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="muted">Sin posiciones abiertas.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </td>
                        <td>
                            <h2>Contribución <span class="muted" style="text-transform:none;font-weight:400">desde el inicio</span></h2>
                            <table>
                                <tbody>
                                    @foreach ($r['contribucion'] as $c)
                                        <tr>
                                            <td>{{ $c['asset_id'] === null ? 'Otros (efectivo, comisiones)' : ($simbolos[$c['asset_id']] ?? '—') }}</td>
                                            <td class="num {{ $signo($c['aporte']) }}">{{ $dinero($c['aporte']) }}</td>
                                            <td class="num {{ $signo($c['contribucion']) }}">{{ $porcentaje($c['contribucion']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h2>Movimientos del trimestre</h2>
            @if ($reporte['movimientos']->isEmpty())
                <p class="muted">Sin movimientos en el trimestre.</p>
            @else
                <div style="overflow-x:auto">
                    <table>
                        <thead>
                            <tr><th>Fecha</th><th>Tipo</th><th>Activo</th><th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Comisión</th><th>Moneda</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($reporte['movimientos'] as $t)
                                <tr>
                                    <td>{{ $t->fecha->format('d/m/Y') }}</td>
                                    <td>{{ $t->tipo }}</td>
                                    <td>{{ $t->asset?->simbolo ?? '—' }}</td>
                                    <td class="num {{ in_array($t->tipo, $salidas, true) ? 'neg' : '' }}">{{ in_array($t->tipo, $salidas, true) ? '-' : '' }}{{ $cantidad($t->cantidad) }}</td>
                                    <td class="num">{{ in_array($t->tipo, ['COMPRA', 'VENTA'], true) ? $dinero($t->precio) : '—' }}</td>
                                    <td class="num">{{ (float) $t->comision ? $dinero($t->comision) : '—' }}</td>
                                    <td>{{ $t->moneda }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif

        <footer class="notas">
            Cifras en {{ $base }}. Valuación con el último cierre registrado en o antes del fin del periodo; activos en otra moneda convertidos al tipo de cambio de esa fecha.
            TWR: rendimiento ponderado por tiempo, sin el efecto de depósitos y retiros (mide la gestión). XIRR: tasa anual que refleja lo que ganaste con tus aportaciones.
            Volatilidad, Sharpe y Sortino se anualizan con 252 días hábiles; Sharpe y Sortino usan la tasa libre de riesgo configurada en tu cuenta.
            Rendimientos pasados no garantizan rendimientos futuros.
        </footer>
    </main>

    @unless ($pdf)
        @include('layouts.partials.chart-hover')
    @endunless
</body>
</html>
