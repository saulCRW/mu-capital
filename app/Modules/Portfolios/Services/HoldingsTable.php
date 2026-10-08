<?php

namespace App\Modules\Portfolios\Services;

/**
 * Tabla de holdings tipo screener (PROYECTO.md, pantalla 4): ordena, agrupa
 * por moneda, tipo, sector o exchange con subtotales y, si se pide, incluye
 * las posiciones ya vendidas (cantidad 0).
 *
 * Peso % = valor ÷ valor total del portafolio (incluye efectivo).
 * Rendimiento % de un grupo = (G/P no realizada + G/P realizada + ingresos) ÷ invertido,
 * con las sumas del grupo. Ejemplo: A invirtió 1,000 y lleva +100 no realizada y 20 de
 * dividendos; B invirtió 3,000, lleva −60 no realizada y +50 realizada →
 * (100 + 20 − 60 + 50) ÷ 4,000 = 2.75 %.
 */
final class HoldingsTable
{
    public const AGRUPACIONES = ['moneda' => 'Moneda', 'tipo' => 'Tipo', 'sector' => 'Sector', 'exchange' => 'Exchange'];

    /** Columna ordenable → campo de la posición. */
    public const ORDENES = [
        'simbolo' => 'simbolo', 'cantidad' => 'cantidad', 'costo' => 'costo_promedio_local', 'precio' => 'precio',
        'valor' => 'valor_base', 'peso' => 'valor_base', 'gp_no_realizada' => 'gp_no_realizada',
        'gp_realizada' => 'gp_realizada', 'rendimiento' => 'rendimiento_pct',
    ];

    public const SIN_CLASIFICAR = 'Sin clasificar';

    /**
     * @param  list<array<string, mixed>>  $posiciones  las de PortfolioLedger::valuar() con los datos del activo
     * @return array{grupos: list<array{clave: ?string, filas: list<array<string, mixed>>, subtotal: array<string, mixed>}>, total: array<string, mixed>}
     */
    public static function armar(array $posiciones, string $valorTotal, ?string $agrupar, string $orden, string $direccion, bool $vendidas): array
    {
        $filas = [];
        foreach ($posiciones as $p) {
            $abierta = bccomp((string) $p['cantidad'], '0', 6) !== 0;
            if (! $abierta && ! $vendidas) {
                continue;
            }
            $filas[] = $p + [
                'abierta' => $abierta,
                'peso' => bccomp($valorTotal, '0', 6) > 0 ? (float) bcdiv((string) $p['valor_base'], $valorTotal, 10) : null,
            ];
        }

        $filas = self::ordenar($filas, self::ORDENES[$orden] ?? 'valor_base', $direccion === 'asc');

        $grupos = [];
        if ($agrupar !== null && isset(self::AGRUPACIONES[$agrupar])) {
            foreach ($filas as $f) {
                $grupos[$f[$agrupar] ?: self::SIN_CLASIFICAR][] = $f;
            }
            $grupos = array_map(fn ($clave, $filasGrupo) => ['clave' => (string) $clave, 'filas' => $filasGrupo, 'subtotal' => self::subtotal($filasGrupo)], array_keys($grupos), $grupos);
            usort($grupos, fn ($a, $b) => bccomp($b['subtotal']['valor_base'], $a['subtotal']['valor_base'], 6));
        } else {
            $grupos = $filas === [] ? [] : [['clave' => null, 'filas' => $filas, 'subtotal' => self::subtotal($filas)]];
        }

        return ['grupos' => $grupos, 'total' => self::subtotal($filas)];
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @return list<array<string, mixed>>
     */
    private static function ordenar(array $filas, string $campo, bool $ascendente): array
    {
        usort($filas, function ($a, $b) use ($campo, $ascendente) {
            // Los vacíos (sin precio, sin rendimiento) siempre al final.
            if ($a[$campo] === null || $b[$campo] === null) {
                return ($a[$campo] === null) <=> ($b[$campo] === null);
            }
            $comparacion = $campo === 'simbolo' ? strcmp($a[$campo], $b[$campo]) : ((float) $a[$campo] <=> (float) $b[$campo]);

            return $ascendente ? $comparacion : -$comparacion;
        });

        return $filas;
    }

    /**
     * @param  list<array<string, mixed>>  $filas
     * @return array{valor_base: string, peso: ?float, gp_no_realizada: string, gp_realizada: string, rendimiento_pct: ?float}
     */
    private static function subtotal(array $filas): array
    {
        $suma = fn (string $campo) => array_reduce($filas, fn ($s, $f) => bcadd($s, (string) $f[$campo], 6), '0');
        $invertido = $suma('invertido_base');
        $resultado = bcadd(bcadd($suma('gp_no_realizada'), $suma('gp_realizada'), 6), $suma('ingresos'), 6);
        $pesos = array_filter(array_column($filas, 'peso'), fn ($p) => $p !== null);

        return [
            'valor_base' => $suma('valor_base'),
            'peso' => $pesos === [] ? null : array_sum($pesos),
            'gp_no_realizada' => $suma('gp_no_realizada'),
            'gp_realizada' => $suma('gp_realizada'),
            'rendimiento_pct' => bccomp($invertido, '0', 6) > 0 ? (float) bcdiv($resultado, $invertido, 10) : null,
        ];
    }
}
