<?php

namespace App\Modules\Reports\Services;

/**
 * Gráfica de líneas en SVG generada en el servidor: la misma salida sirve en
 * la página, al imprimir y en el PDF (dompdf la incrusta como imagen).
 *
 * Una sola escala Y. Leyenda arriba, valor final junto a cada línea, rejilla
 * horizontal tenue. El atributo data-chart lleva los valores por fecha para el
 * tooltip de la página (resources/views/layouts/partials/chart-hover.blade.php).
 *
 * Los colores van en atributos (los usa el PDF) y cada elemento lleva una clase
 * c-* para que en la página siga al tema claro u oscuro (layouts/partials/styles).
 */
final class SvgLineChart
{
    /** Colores por posición de la serie (validados para daltonismo sobre blanco). */
    public const COLORES = ['#2a78d6', '#eb6834'];

    private const TINTA = '#1f2328';

    private const TINTA_TENUE = '#59636e';

    private const REJILLA = '#e6eaef';

    private const FUENTE = 'Inter, Segoe UI, DejaVu Sans, sans-serif';

    private const MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    /**
     * @param  list<array{nombre:string, puntos:list<array{0:string, 1:float}>}>  $series  puntos [fecha Y-m-d, valor], ordenados
     * @param  'dinero'|'indice'  $formato
     */
    public static function render(array $series, string $formato, string $titulo, int $ancho = 720, int $alto = 260): string
    {
        $series = array_values(array_filter($series, fn ($s) => $s['puntos'] !== []));
        if ($series === []) {
            return '';
        }

        [$arriba, $izquierda, $derecha, $abajo] = [36, 56, 92, 26];
        $x0 = $izquierda;
        $x1 = $ancho - $derecha;
        $y0 = $alto - $abajo;
        $y1 = $arriba;

        $fechas = array_values(array_unique(array_merge(...array_map(fn ($s) => array_column($s['puntos'], 0), $series))));
        sort($fechas);
        $t0 = strtotime($fechas[0]);
        $t1 = max(strtotime(end($fechas)), $t0 + 86400);
        $x = fn (string $f) => $x0 + (strtotime($f) - $t0) / ($t1 - $t0) * ($x1 - $x0);

        $valores = array_merge(...array_map(fn ($s) => array_column($s['puntos'], 1), $series));
        [$min, $max, $paso] = self::escala(min($valores), max($valores));
        $y = fn (float $v) => $y0 - ($v - $min) / ($max - $min) * ($y0 - $y1);

        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="%d" height="%d" role="img" aria-label="%s" font-family="%s" font-size="11" style="width:100%%;height:auto" data-chart="%s">',
            $ancho, $alto, $ancho, $alto, self::e($titulo), self::FUENTE, self::e(json_encode(self::datosTooltip($series, $fechas, $x, $formato))),
        );

        // Rejilla y etiquetas del eje Y.
        for ($v = $min; $v <= $max + $paso / 2; $v += $paso) {
            $py = round($y($v), 1);
            $svg[] = sprintf('<line class="c-grid" x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="1"/>', $x0, $py, $x1, $py, self::REJILLA);
            $svg[] = sprintf('<text class="c-label" x="%s" y="%s" text-anchor="end" fill="%s">%s</text>', $x0 - 8, $py + 4, self::TINTA_TENUE, self::e(self::eje($v, $paso, $formato)));
        }

        // Etiquetas del eje X: inicio de mes (o de trimestre si el periodo es largo).
        $meses = (int) round(($t1 - $t0) / (86400 * 30.4));
        $cada = $meses > 30 ? 6 : ($meses > 14 ? 3 : ($meses > 7 ? 2 : 1));
        $anterior = null;
        foreach ($fechas as $f) {
            $mes = (int) substr($f, 5, 2);
            $clave = substr($f, 0, 7);
            if ($clave !== $anterior && ($mes - 1) % $cada === 0) {
                $etiqueta = self::MESES[$mes - 1].($mes === 1 || $anterior === null ? ' '.substr($f, 2, 2) : '');
                $svg[] = sprintf('<text class="c-label" x="%s" y="%s" text-anchor="middle" fill="%s">%s</text>', round($x($f), 1), $alto - 8, self::TINTA_TENUE, self::e($etiqueta));
            }
            $anterior = $clave;
        }

        // Leyenda.
        $lx = $x0;
        foreach ($series as $i => $s) {
            $color = self::COLORES[$i % count(self::COLORES)];
            $svg[] = sprintf('<line class="c-s%d" x1="%s" y1="14" x2="%s" y2="14" stroke="%s" stroke-width="2" stroke-linecap="round"/>', $i % count(self::COLORES), $lx, $lx + 16, $color);
            $svg[] = sprintf('<text class="c-ink" x="%s" y="18" fill="%s" font-size="12">%s</text>', $lx + 22, self::TINTA, self::e($s['nombre']));
            $lx += 22 + mb_strlen($s['nombre']) * 6.6 + 24;
        }

        // Líneas.
        foreach ($series as $i => $s) {
            $puntos = implode(' ', array_map(fn ($p) => round($x($p[0]), 1).','.round($y($p[1]), 1), $s['puntos']));
            $svg[] = sprintf('<polyline class="c-s%d" points="%s" fill="none" stroke="%s" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>', $i % count(self::COLORES), $puntos, self::COLORES[$i % count(self::COLORES)]);
        }

        // Valor final de cada línea; si dos quedan encimados se separan con una guía.
        $finales = [];
        foreach ($series as $i => $s) {
            $ultimo = end($s['puntos']);
            $finales[] = ['i' => $i, 'x' => $x($ultimo[0]), 'y' => $y($ultimo[1]), 'ly' => $y($ultimo[1]), 'v' => $ultimo[1]];
        }
        usort($finales, fn ($a, $b) => $a['y'] <=> $b['y']);
        for ($k = 1; $k < count($finales); $k++) {
            $finales[$k]['ly'] = max($finales[$k]['ly'], $finales[$k - 1]['ly'] + 14);
        }
        foreach ($finales as $f) {
            $color = self::COLORES[$f['i'] % count(self::COLORES)];
            $svg[] = sprintf('<circle class="c-s%d" cx="%s" cy="%s" r="3.5" fill="%s" stroke="#ffffff" stroke-width="2"/>', $f['i'] % count(self::COLORES), round($f['x'], 1), round($f['y'], 1), $color);
            if (abs($f['ly'] - $f['y']) > 1) {
                $svg[] = sprintf('<line class="c-guia" x1="%s" y1="%s" x2="%s" y2="%s" stroke="%s" stroke-width="1"/>', round($f['x'] + 4, 1), round($f['y'], 1), round($f['x'] + 8, 1), round($f['ly'], 1), self::TINTA_TENUE);
            }
            $svg[] = sprintf('<text class="c-ink" x="%s" y="%s" fill="%s" font-weight="600">%s</text>', round($f['x'] + 10, 1), round($f['ly'] + 4, 1), self::TINTA, self::e(self::final($f['v'], $formato)));
        }

        // Cursor del tooltip (lo mueve el script de la página; en PDF queda oculto).
        $svg[] = sprintf('<line class="chart-cursor c-guia" x1="0" y1="%s" x2="0" y2="%s" stroke="%s" stroke-width="1" visibility="hidden"/>', $y1, $y0, self::TINTA_TENUE);
        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * La misma gráfica como data URI, para dompdf (que no dibuja SVG en línea).
     * Sin los datos ni el cursor del tooltip: su librería de SVG ignora "visibility".
     */
    public static function dataUri(string $svg): string
    {
        $svg = preg_replace(['/ data-chart="[^"]*"/', '/<line class="chart-cursor[^"]*"[^>]*\/>/'], '', $svg);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Mínimo, máximo y paso "redondos" (1, 2, 2.5 o 5 × 10^n) con unas 4 divisiones.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public static function escala(float $min, float $max): array
    {
        if ($max - $min < 1e-9) {
            $holgura = abs($max) > 0 ? abs($max) * 0.05 : 1;
            [$min, $max] = [$min - $holgura, $max + $holgura];
        }

        $bruto = ($max - $min) / 4;
        $magnitud = 10 ** floor(log10($bruto));
        $paso = $magnitud * (collect([1, 2, 2.5, 5, 10])->first(fn ($m) => $m * $magnitud >= $bruto) ?? 10);

        return [floor($min / $paso) * $paso, ceil($max / $paso) * $paso, $paso];
    }

    /** Etiqueta del eje: compacta (k, M) con los decimales que pide el paso. */
    public static function eje(float $v, float $paso, string $formato): string
    {
        if ($formato === 'indice') {
            return number_format($v, $paso < 1 ? 1 : 0, '.', ',');
        }

        $unidad = max(abs($v), $paso) >= 1e6 ? [1e6, ' M'] : (max(abs($v), $paso) >= 1e3 ? [1e3, ' k'] : [1, '']);
        // Los decimales que necesita el paso en esa unidad (0.05 M → 2; 2.5 k → 1).
        $decimales = 0;
        while ($decimales < 3 && abs(round($paso / $unidad[0], $decimales) - $paso / $unidad[0]) > 1e-9) {
            $decimales++;
        }

        return number_format($v / $unidad[0], $decimales, '.', ',').$unidad[1];
    }

    private static function final(float $v, string $formato): string
    {
        return $formato === 'indice' ? number_format($v, 1, '.', ',') : number_format($v, 0, '.', ',');
    }

    /**
     * Posición X y valor formateado de cada serie en cada fecha, para el tooltip.
     *
     * @return array{x: list<float>, fechas: list<string>, series: list<array{nombre:string, color:string, valores:list<?string>}>}
     */
    private static function datosTooltip(array $series, array $fechas, callable $x, string $formato): array
    {
        $decimales = $formato === 'indice' ? 1 : 2;

        return [
            'x' => array_map(fn ($f) => round($x($f), 1), $fechas),
            'fechas' => array_map(fn ($f) => substr($f, 8, 2).'/'.substr($f, 5, 2).'/'.substr($f, 0, 4), $fechas),
            'series' => array_map(function ($s, $i) use ($fechas, $decimales) {
                $porFecha = array_column($s['puntos'], 1, 0);

                return [
                    'nombre' => $s['nombre'],
                    'color' => self::COLORES[$i % count(self::COLORES)],
                    'valores' => array_map(fn ($f) => isset($porFecha[$f]) ? number_format($porFecha[$f], $decimales, '.', ',') : null, $fechas),
                ];
            }, $series, array_keys($series)),
        ];
    }

    private static function e(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
