<?php

namespace App\Modules\Prices\Services;

use App\Modules\Assets\Models\Asset;
use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Services\Providers\PriceProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Cotizaciones del momento para las vistas en vivo (mercado y portafolio).
 *
 * Cada símbolo se guarda en caché unos segundos: muchos usuarios mirando la
 * misma pantalla generan una sola consulta al proveedor por símbolo. No se
 * escribe en la base; la tarea programada (mercado:sincronizar) es la que
 * guarda los cierres y revalúa los portafolios.
 */
class QuoteService
{
    /** Segundos que se reutiliza una cotización. */
    public const VIGENCIA = 15;

    /** Monedas cuyo tipo de cambio a MXN se cotiza en vivo. */
    private const MONEDAS = ['USD', 'EUR'];

    public function __construct(private PriceProvider $precios) {}

    /**
     * @param  Collection<int, Asset>  $activos
     * @return array{activos: array<int, array<string, mixed>>, indices: list<array<string, mixed>>, fx: array<string, ?array<string, mixed>>, generado: string}
     */
    public function cotizar(Collection $activos, bool $conIndices = false): array
    {
        $simbolos = [];
        foreach ($activos as $a) {
            if (($s = $this->precios->simboloPara($a)) !== null) {
                $simbolos[$a->id] = $s;
            }
        }
        $indices = $conIndices ? Benchmark::whereNotNull('simbolo_proveedor')->orderBy('nombre')->get() : collect();
        $pares = array_combine(self::MONEDAS, array_map(fn ($m) => "{$m}MXN=X", self::MONEDAS));

        $cotizaciones = $this->consultar(array_values(array_unique([...array_values($simbolos), ...$indices->pluck('simbolo_proveedor'), ...array_values($pares)])));

        $con = fn (?array $c, array $extra) => $c === null ? null : $extra + $c + [
            'cambio' => $c['anterior'] !== null ? $c['precio'] - $c['anterior'] : null,
            'cambio_pct' => $c['anterior'] ? $c['precio'] / $c['anterior'] - 1 : null,
        ];

        return [
            'activos' => collect($simbolos)->map(fn ($s, $id) => $con($cotizaciones[$s] ?? null, ['simbolo' => $activos->firstWhere('id', $id)->simbolo]))->all(),
            'indices' => $indices->map(fn (Benchmark $b) => $con($cotizaciones[$b->simbolo_proveedor] ?? null, ['nombre' => $b->nombre]))->filter()->values()->all(),
            'fx' => array_map(fn ($par) => $con($cotizaciones[$par] ?? null, []), $pares),
            'generado' => now()->toIso8601String(),
        ];
    }

    /**
     * Unidades de $destino por 1 de $origen con el tipo de cambio del momento
     * (cruzando por MXN). null si falta alguna de las dos monedas.
     *
     * @param  array<string, ?array<string, mixed>>  $fx  el "fx" que devuelve cotizar()
     */
    public static function tasa(string $origen, string $destino, array $fx): ?float
    {
        if ($origen === $destino) {
            return 1.0;
        }

        $enPesos = fn (string $moneda) => $moneda === 'MXN' ? 1.0 : ($fx[$moneda]['precio'] ?? null);
        $o = $enPesos($origen);
        $d = $enPesos($destino);

        return $o !== null && $d ? $o / $d : null;
    }

    /**
     * Cotizaciones desde caché; las que faltan se piden juntas al proveedor.
     *
     * @param  list<string>  $simbolos
     * @return array<string, ?array<string, mixed>>
     */
    private function consultar(array $simbolos): array
    {
        $resultado = [];
        $faltan = [];
        foreach ($simbolos as $s) {
            $guardada = Cache::get("cotizacion:{$s}");
            $guardada === null ? $faltan[] = $s : $resultado[$s] = $guardada;
        }

        foreach ($this->precios->cotizaciones($faltan) as $s => $c) {
            $resultado[$s] = $c;
            if ($c !== null) {
                Cache::put("cotizacion:{$s}", $c, self::VIGENCIA);
            }
        }

        return $resultado;
    }
}
