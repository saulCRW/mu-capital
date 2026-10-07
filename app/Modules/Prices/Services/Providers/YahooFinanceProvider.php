<?php

namespace App\Modules\Prices\Services\Providers;

use App\Modules\Assets\Models\Asset;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Precios desde el endpoint público de gráficas de Yahoo Finance: gratis y
 * sin clave, cubre BMV, mercados de EE. UU., cripto e índices.
 * No es una API oficial: si deja de funcionar, se cambia esta clase por otra
 * implementación de PriceProvider sin tocar el resto.
 */
class YahooFinanceProvider implements PriceProvider
{
    public function __construct(private string $url) {}

    /**
     * BMV → WALMEX.MX; cripto → BTC-USD; EE. UU. → AAPL. El campo
     * simbolo_proveedor del activo tiene prioridad sobre esta regla.
     */
    public function simboloPara(Asset $asset): ?string
    {
        return match (true) {
            filled($asset->simbolo_proveedor) => $asset->simbolo_proveedor,
            $asset->tipo === 'efectivo' => null,
            $asset->tipo === 'cripto' => $asset->simbolo.'-'.$asset->moneda,
            $asset->exchange === 'BMV' || $asset->exchange === 'BIVA' => $asset->simbolo.'.MX',
            default => $asset->simbolo,
        };
    }

    public function cierres(string $simbolo, string $desde, string $hasta): array
    {
        $resultado = $this->grafica($simbolo, $desde, $hasta);

        $desfase = (int) ($resultado['meta']['gmtoffset'] ?? 0);
        $cierresCrudos = $resultado['indicators']['quote'][0]['close'] ?? [];
        $cierres = [];
        foreach ($resultado['timestamp'] ?? [] as $i => $ts) {
            // La fecha de la barra es la del mercado, no la UTC.
            $fecha = gmdate('Y-m-d', $ts + $desfase);
            $cierre = $cierresCrudos[$i] ?? null;
            if ($cierre !== null && $fecha >= $desde && $fecha <= $hasta) {
                $cierres[$fecha] = self::numero($cierre);
            }
        }

        // Día en curso: la barra aún no tiene cierre, pero el último precio sí.
        $ultimo = $resultado['meta']['regularMarketPrice'] ?? null;
        $momento = $resultado['meta']['regularMarketTime'] ?? null;
        if ($ultimo !== null && $momento !== null) {
            $fecha = gmdate('Y-m-d', $momento + $desfase);
            if ($fecha >= $desde && $fecha <= $hasta) {
                $cierres[$fecha] = self::numero($ultimo);
            }
        }

        ksort($cierres);

        return $cierres;
    }

    public function dividendos(string $simbolo, string $desde, string $hasta): array
    {
        $resultado = $this->grafica($simbolo, $desde, $hasta, ['events' => 'div']);
        $desfase = (int) ($resultado['meta']['gmtoffset'] ?? 0);

        $dividendos = [];
        foreach ($resultado['events']['dividends'] ?? [] as $evento) {
            $fecha = gmdate('Y-m-d', (int) $evento['date'] + $desfase);
            // Yahoo a veces publica eventos con monto vacío o en cero: no son dividendos.
            if (($evento['amount'] ?? 0) > 0 && $fecha >= $desde && $fecha <= $hasta) {
                $dividendos[$fecha] = self::numero($evento['amount']);
            }
        }
        ksort($dividendos);

        return $dividendos;
    }

    public function cotizaciones(array $simbolos): array
    {
        if ($simbolos === []) {
            return [];
        }

        // Primero el día en curso cada 5 minutos (todas las consultas en paralelo).
        $cotizaciones = [];
        foreach ($this->graficas($simbolos, ['range' => '1d', 'interval' => '5m']) as $simbolo => $resultado) {
            $cotizaciones[$simbolo] = $resultado === null ? null : self::cotizacion($resultado);
        }

        // Si Yahoo no trae barras del día o su "último precio" está viejo (le pasa a algunos
        // valores de la BMV), se usa el último cierre diario real de la semana.
        $faltan = array_keys(array_filter($cotizaciones, fn ($c) => $c === null || $c['puntos'] === []));
        foreach ($this->graficas($faltan, ['range' => '5d', 'interval' => '1d']) as $simbolo => $resultado) {
            $diaria = $resultado === null ? null : self::cotizacion($resultado);
            if ($diaria !== null) {
                $cotizaciones[$simbolo] = $diaria;
            }
        }

        return $cotizaciones;
    }

    /**
     * Varias consultas a /v8/finance/chart en paralelo.
     *
     * @param  list<string>  $simbolos
     * @return array<string, ?array<string, mixed>> símbolo => primer resultado, o null si falló
     */
    private function graficas(array $simbolos, array $parametros): array
    {
        if ($simbolos === []) {
            return [];
        }

        $respuestas = Http::pool(fn (Pool $pool) => array_map(
            fn ($simbolo) => $pool->as($simbolo)->withUserAgent('Mozilla/5.0 (MU Intelligence)')->timeout(10)
                ->get($this->url.'/v8/finance/chart/'.rawurlencode($simbolo), $parametros + ['includePrePost' => 'false']),
            $simbolos,
        ));

        $resultados = [];
        foreach ($simbolos as $simbolo) {
            $respuesta = $respuestas[$simbolo] ?? null;
            $resultados[$simbolo] = $respuesta instanceof Response && $respuesta->successful() ? $respuesta->json('chart.result.0') : null;
        }

        return $resultados;
    }

    /**
     * Último precio confiable: el de la última barra con cierre, salvo que el
     * "último precio" de Yahoo sea igual o más reciente que esa barra.
     *
     * @param  array<string, mixed>  $resultado
     * @return ?array{precio:float, anterior:?float, hora:int, moneda:?string, abierto:bool, puntos:list<float>}
     */
    private static function cotizacion(array $resultado): ?array
    {
        $meta = $resultado['meta'] ?? [];
        $cierres = $resultado['indicators']['quote'][0]['close'] ?? [];
        $barras = [];
        foreach ($resultado['timestamp'] ?? [] as $i => $ts) {
            if (($cierres[$i] ?? null) !== null) {
                $barras[] = [(int) $ts, (float) $cierres[$i]];
            }
        }

        $ultimaBarra = $barras === [] ? null : end($barras);
        $horaMeta = (int) ($meta['regularMarketTime'] ?? 0);
        if (($meta['regularMarketPrice'] ?? null) !== null && ($ultimaBarra === null || $horaMeta >= $ultimaBarra[0])) {
            [$hora, $precio] = [$horaMeta, (float) $meta['regularMarketPrice']];
        } elseif ($ultimaBarra !== null) {
            [$hora, $precio] = $ultimaBarra;
        } else {
            return null;
        }

        $diaria = ($meta['dataGranularity'] ?? null) === '1d';
        // Cierre anterior: el que reporta Yahoo; en la serie diaria, la barra previa a la última.
        $anterior = $diaria && count($barras) > 1 ? $barras[count($barras) - 2][1] : ($meta['previousClose'] ?? $meta['chartPreviousClose'] ?? null);
        $regular = $meta['currentTradingPeriod']['regular'] ?? null;
        $ahora = now()->timestamp;

        return [
            'precio' => $precio,
            'anterior' => $anterior === null ? null : (float) $anterior,
            'hora' => $hora,
            'moneda' => $meta['currency'] ?? null,
            // Cripto y divisas cotizan siempre; el resto, en el horario regular de su bolsa.
            'abierto' => in_array($meta['instrumentType'] ?? null, ['CRYPTOCURRENCY', 'CURRENCY'], true)
                || ($regular !== null && $ahora >= $regular['start'] && $ahora < $regular['end']),
            'puntos' => $diaria ? [] : array_column($barras, 1),
        ];
    }

    /** @return array<string, mixed> el primer resultado de /v8/finance/chart */
    private function grafica(string $simbolo, string $desde, string $hasta, array $extra = []): array
    {
        try {
            $respuesta = Http::withUserAgent('Mozilla/5.0 (MU Intelligence)')
                ->timeout(20)
                ->retry(2, 500, throw: false)
                ->get($this->url.'/v8/finance/chart/'.rawurlencode($simbolo), [
                    'period1' => CarbonImmutable::parse($desde)->startOfDay()->subDay()->timestamp,
                    'period2' => CarbonImmutable::parse($hasta)->endOfDay()->addDay()->timestamp,
                    'interval' => '1d',
                    'includePrePost' => 'false',
                ] + $extra);
        } catch (ConnectionException $e) {
            throw new ProviderException("Yahoo Finance no respondió para {$simbolo}.", previous: $e);
        }

        $resultado = $respuesta->json('chart.result.0');
        if (! $respuesta->successful() || $resultado === null) {
            throw new ProviderException("Yahoo Finance no tiene datos de {$simbolo}: ".($respuesta->json('chart.error.description') ?? 'HTTP '.$respuesta->status()));
        }

        return $resultado;
    }

    private static function numero(float|int $valor): string
    {
        return number_format($valor, 6, '.', '');
    }
}
