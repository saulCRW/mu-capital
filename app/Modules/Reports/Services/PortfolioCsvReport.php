<?php

namespace App\Modules\Reports\Services;

use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Assets\Models\Asset;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Portfolios\Services\HoldingsTable;

/**
 * Estado del portafolio en CSV (PROYECTO.md, pantalla 10) para entregar al
 * cliente o abrir en Excel: un bloque de resumen y la tabla de posiciones
 * (abiertas y vendidas), con los mismos cálculos que la pantalla.
 *
 * Los números van sin separador de miles y con punto decimal, para que una
 * hoja de cálculo los lea como números; los porcentajes, en puntos (9.78 = 9.78 %).
 */
class PortfolioCsvReport
{
    public function __construct(private AnalyticsService $analytics) {}

    /** @return list<list<string>> filas del CSV */
    public function filas(Portfolio $portfolio, string $hoy): array
    {
        $saldo = $this->analytics->saldo($portfolio, $hoy);
        $filas = [
            ['MU Intelligence', 'Estado del portafolio'],
            ['Portafolio', $portfolio->nombre],
            ['Fecha', $hoy],
            ['Moneda base', $portfolio->moneda_base],
        ];

        if ($saldo['vacio']) {
            return [...$filas, ['Sin transacciones']];
        }

        $e = $saldo['estado'];
        $filas = [
            ...$filas,
            ['Valor total', self::numero($e['valor_total'])],
            ['Efectivo', self::numero($e['efectivo'])],
            ['Valor de posiciones', self::numero($e['valor_posiciones'])],
            ['Aportaciones netas', self::numero($e['aportaciones_netas'])],
            ['G/P total', self::numero($e['gp_total'])],
            ['Rendimiento total %', self::porcentaje($saldo['rendimiento_total'])],
            ['Rendimiento anualizado %', self::porcentaje($saldo['rendimiento_anualizado'])],
            ['G/P no realizada', self::numero($e['gp_no_realizada'])],
            ['G/P realizada', self::numero($e['gp_realizada'])],
            ['Dividendos e intereses', self::numero($e['ingresos'])],
            [],
            ['simbolo', 'nombre', 'tipo', 'sector', 'moneda', 'cantidad', 'costo_promedio', 'precio',
                'valor_'.strtolower($portfolio->moneda_base), 'peso_%', 'gp_no_realizada', 'gp_realizada', 'rendimiento_%', 'estado'],
        ];

        $tabla = HoldingsTable::armar($e['posiciones'], $e['valor_total'], null, 'valor', 'desc', true);
        foreach ($tabla['grupos'][0]['filas'] ?? [] as $p) {
            $filas[] = [
                $p['simbolo'], $p['nombre'], Asset::ETIQUETAS[$p['tipo']] ?? (string) $p['tipo'], (string) ($p['sector'] ?? ''), $p['moneda'],
                self::numero($p['cantidad']), self::numero($p['costo_promedio_local']), self::numero($p['precio']),
                self::numero($p['valor_base']), self::porcentaje($p['peso']),
                self::numero($p['gp_no_realizada']), self::numero($p['gp_realizada']), self::porcentaje($p['rendimiento_pct']),
                $p['abierta'] ? 'abierta' : 'vendida',
            ];
        }

        return $filas;
    }

    /** "1234.500000" → "1234.5" (sin miles; vacío si no hay dato). */
    private static function numero(string|float|null $valor): string
    {
        if ($valor === null) {
            return '';
        }
        $texto = rtrim(rtrim(number_format((float) $valor, 6, '.', ''), '0'), '.');

        return $texto === '-0' ? '0' : $texto;
    }

    /** 0.0978 → "9.78". */
    private static function porcentaje(?float $valor): string
    {
        return $valor === null ? '' : number_format($valor * 100, 2, '.', '');
    }
}
