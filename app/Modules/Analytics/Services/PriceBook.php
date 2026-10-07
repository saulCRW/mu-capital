<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Prices\Models\FxRate;
use App\Modules\Prices\Models\Price;

/**
 * Libreta de precios y tipos de cambio en memoria.
 *
 * Responde "¿cuál es el último precio (o tipo de cambio) registrado en o
 * antes de esta fecha?". Los fines de semana o días sin dato usan el último
 * conocido. Se construye con arreglos (para pruebas) o desde la base (cargar).
 */
final class PriceBook
{
    /** @var array<int|string, array{0: list<string>, 1: list<string>}> activo => [fechas, cierres] */
    private array $precios = [];

    /** @var array<string, array{0: list<string>, 1: list<string>}> "USD>MXN" => [fechas, tasas] */
    private array $tasas = [];

    /**
     * @param  array<int|string, array<string, string>>  $precios  activo => [fecha => cierre]
     * @param  array<string, array<string, string>>  $tasas  "USD>MXN" => [fecha => tasa]
     */
    public function __construct(array $precios = [], array $tasas = [])
    {
        foreach ($precios as $activo => $serie) {
            ksort($serie);
            $this->precios[$activo] = [array_keys($serie), array_values($serie)];
        }

        foreach ($tasas as $par => $serie) {
            ksort($serie);
            $this->tasas[$par] = [array_keys($serie), array_values($serie)];
        }
    }

    /**
     * @param  list<int>  $activos
     * @param  list<string>  $monedas  monedas distintas a la base que se necesitan convertir
     */
    public static function cargar(array $activos, array $monedas, string $base, string $hasta): self
    {
        $precios = [];
        Price::whereIn('asset_id', $activos)->where('fecha', '<=', $hasta)
            ->orderBy('fecha')
            ->get(['asset_id', 'fecha', 'cierre'])
            ->each(function (Price $p) use (&$precios) {
                $precios[$p->asset_id][$p->fecha->toDateString()] = $p->cierre;
            });

        $tasas = [];
        if ($monedas !== []) {
            FxRate::where('fecha', '<=', $hasta)
                ->where(fn ($q) => $q
                    ->where(fn ($w) => $w->whereIn('moneda_origen', $monedas)->where('moneda_destino', $base))
                    ->orWhere(fn ($w) => $w->where('moneda_origen', $base)->whereIn('moneda_destino', $monedas)))
                ->orderBy('fecha')
                ->get(['moneda_origen', 'moneda_destino', 'fecha', 'tasa'])
                ->each(function (FxRate $r) use (&$tasas) {
                    $tasas[$r->moneda_origen.'>'.$r->moneda_destino][$r->fecha->toDateString()] = $r->tasa;
                });
        }

        return new self($precios, $tasas);
    }

    /**
     * Como cargar(), pero solo con el último cierre de cada activo y la última
     * tasa de cada par en o antes de "hasta". Sirve para consultar a esa fecha
     * sin traer todo el historial.
     *
     * @param  list<int>  $activos
     * @param  list<string>  $monedas  monedas distintas a la base que se necesitan convertir
     */
    public static function cargarUltimos(array $activos, array $monedas, string $base, string $hasta): self
    {
        $ultimos = Price::selectRaw('asset_id, MAX(fecha) AS fecha')
            ->whereIn('asset_id', $activos)->where('fecha', '<=', $hasta)
            ->groupBy('asset_id');

        $precios = [];
        Price::joinSub($ultimos, 'u', fn ($j) => $j->on('prices.asset_id', '=', 'u.asset_id')->on('prices.fecha', '=', 'u.fecha'))
            ->get(['prices.asset_id', 'prices.fecha', 'prices.cierre'])
            ->each(function (Price $p) use (&$precios) {
                $precios[$p->asset_id][$p->fecha->toDateString()] = $p->cierre;
            });

        $tasas = [];
        if ($monedas !== []) {
            $pares = fn ($q) => $q
                ->where(fn ($w) => $w->whereIn('moneda_origen', $monedas)->where('moneda_destino', $base))
                ->orWhere(fn ($w) => $w->where('moneda_origen', $base)->whereIn('moneda_destino', $monedas));
            $ultimas = FxRate::selectRaw('moneda_origen, moneda_destino, MAX(fecha) AS fecha')
                ->where('fecha', '<=', $hasta)->where($pares)
                ->groupBy('moneda_origen', 'moneda_destino');

            FxRate::joinSub($ultimas, 'u', fn ($j) => $j
                ->on('fx_rates.moneda_origen', '=', 'u.moneda_origen')
                ->on('fx_rates.moneda_destino', '=', 'u.moneda_destino')
                ->on('fx_rates.fecha', '=', 'u.fecha'))
                ->get(['fx_rates.moneda_origen', 'fx_rates.moneda_destino', 'fx_rates.fecha', 'fx_rates.tasa'])
                ->each(function (FxRate $r) use (&$tasas) {
                    $tasas[$r->moneda_origen.'>'.$r->moneda_destino][$r->fecha->toDateString()] = $r->tasa;
                });
        }

        return new self($precios, $tasas);
    }

    /** Último cierre del activo en o antes de la fecha, o null si no hay ninguno. */
    public function precio(int|string $activo, string $fecha): ?string
    {
        return isset($this->precios[$activo]) ? self::ultimo($this->precios[$activo], $fecha) : null;
    }

    /** Tasa para convertir 1 unidad de $origen a $destino; usa la inversa si solo existe la contraria. */
    public function tasa(string $origen, string $destino, string $fecha): ?string
    {
        if ($origen === $destino) {
            return '1';
        }

        if (isset($this->tasas["$origen>$destino"]) && ($t = self::ultimo($this->tasas["$origen>$destino"], $fecha)) !== null) {
            return $t;
        }

        if (isset($this->tasas["$destino>$origen"]) && ($t = self::ultimo($this->tasas["$destino>$origen"], $fecha)) !== null && bccomp($t, '0', 6) > 0) {
            return bcdiv('1', $t, 6);
        }

        return null;
    }

    /** Búsqueda binaria del último valor con fecha <= $fecha. */
    private static function ultimo(array $serie, string $fecha): ?string
    {
        [$fechas, $valores] = $serie;
        $lo = 0;
        $hi = count($fechas) - 1;
        $encontrado = null;

        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($fechas[$mid] <= $fecha) {
                $encontrado = $mid;
                $lo = $mid + 1;
            } else {
                $hi = $mid - 1;
            }
        }

        return $encontrado === null ? null : $valores[$encontrado];
    }
}
