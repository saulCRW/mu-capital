<?php

namespace Tests\Feature\Prices;

use App\Modules\Prices\Models\Benchmark;
use App\Modules\Prices\Services\Providers\YahooFinanceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Cotizaciones del momento (página Mercado y saldo en vivo), con respuestas simuladas. */
class LiveQuotesTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    private const AHORA = 1791316800; // 06/10/2026 14:00 (EE. UU., UTC−4)

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-10-06 13:00:00');
        config(['services.yahoo_finance.url' => 'https://yahoo.test']);
    }

    /** Respuesta intradía de Yahoo: último precio, cierre anterior y barras de 5 minutos. */
    private static function intradia(float $precio, float $anterior, int $hora, array $cierres): array
    {
        return ['chart' => ['result' => [[
            'meta' => ['regularMarketPrice' => $precio, 'previousClose' => $anterior, 'regularMarketTime' => $hora, 'currency' => 'USD',
                'currentTradingPeriod' => ['regular' => ['start' => self::AHORA - 3600, 'end' => self::AHORA + 3600]]],
            'timestamp' => array_map(fn ($i) => $hora - (count($cierres) - 1 - $i) * 300, array_keys($cierres)),
            'indicators' => ['quote' => [['close' => $cierres]]],
        ]], 'error' => null]];
    }

    public function test_lee_precio_cambio_y_serie_del_dia(): void
    {
        Http::fake(['yahoo.test/*' => Http::response(self::intradia(333.63, 332.89, self::AHORA, [332.98, null, 333.36, 333.63]))]);

        $c = (new YahooFinanceProvider('https://yahoo.test'))->cotizaciones(['AAPL'])['AAPL'];

        $this->assertSame([333.63, 332.89, self::AHORA, 'USD', true], [$c['precio'], $c['anterior'], $c['hora'], $c['moneda'], $c['abierto']]);
        $this->assertSame([332.98, 333.36, 333.63], $c['puntos']);
    }

    /** Le pasa a NAFTRAC.MX: el "último precio" de Yahoo es de 2019 y no hay barras del día. */
    public function test_si_el_ultimo_precio_esta_viejo_usa_el_ultimo_cierre_diario(): void
    {
        Http::fake(function (Request $r) {
            return $r['interval'] === '5m'
                ? Http::response(['chart' => ['result' => [['meta' => ['regularMarketPrice' => 45.0, 'regularMarketTime' => 1561759658], 'indicators' => ['quote' => [[]]]]]]])
                : Http::response(['chart' => ['result' => [[
                    'meta' => ['regularMarketPrice' => 45.0, 'regularMarketTime' => 1561759658, 'dataGranularity' => '1d', 'currency' => 'MXN'],
                    'timestamp' => [1791034200, 1791120600, 1791207000],
                    'indicators' => ['quote' => [['close' => [63.5, 64.11, 64.7]]]],
                ]]]]);
        });

        $c = (new YahooFinanceProvider('https://yahoo.test'))->cotizaciones(['NAFTRAC.MX'])['NAFTRAC.MX'];

        $this->assertSame([64.7, 64.11, 1791207000], [$c['precio'], $c['anterior'], $c['hora']]);
    }

    public function test_el_portafolio_recibe_sus_activos_y_el_tipo_de_cambio(): void
    {
        $this->depositar('100000');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->aapl->id, 'cantidad' => '1', 'precio' => '200', 'tipo_cambio' => '18']);
        Http::fake([
            'yahoo.test/v8/finance/chart/AAPL*' => Http::response(self::intradia(333.63, 332.89, self::AHORA, [333.63])),
            'yahoo.test/*' => Http::response(self::intradia(18.07, 18.10, self::AHORA, [18.07])),
        ]);

        $datos = $this->actingAs($this->usuario)->getJson(route('mercado.cotizaciones', ['portfolio' => $this->portafolio->id]))->assertOk()->json();

        $this->assertSame([(string) $this->aapl->id], array_map('strval', array_keys($datos['activos'])));
        $this->assertSame(['AAPL', 333.63], [$datos['activos'][$this->aapl->id]['simbolo'], $datos['activos'][$this->aapl->id]['precio']]);
        $this->assertEqualsWithDelta(333.63 / 332.89 - 1, $datos['activos'][$this->aapl->id]['cambio_pct'], 1e-12);
        $this->assertSame(18.07, $datos['fx']['USD']['precio']);
    }

    public function test_las_cotizaciones_se_reutilizan_unos_segundos(): void
    {
        Http::fake(['yahoo.test/*' => Http::response(self::intradia(333.63, 332.89, self::AHORA, [333.63]))]);

        $this->actingAs($this->usuario)->getJson(route('mercado.cotizaciones'))->assertOk();
        $consultas = count(Http::recorded());
        $this->actingAs($this->usuario)->getJson(route('mercado.cotizaciones'))->assertOk();

        $this->assertSame($consultas, count(Http::recorded()));
    }

    public function test_la_pagina_de_mercado_incluye_indices(): void
    {
        Benchmark::create(['nombre' => 'IPC', 'simbolo_proveedor' => '^MXX']);
        $this->usuario->watchlist()->attach($this->aapl->id);
        Http::fake(['yahoo.test/*' => Http::response(self::intradia(65312.46, 64975.08, self::AHORA, [65312.46]))]);

        $this->actingAs($this->usuario)->get(route('mercado'))->assertSee('data-activo="'.$this->aapl->id.'"', false);
        $datos = $this->actingAs($this->usuario)->getJson(route('mercado.cotizaciones'))->json();

        $this->assertSame(['IPC'], array_column($datos['indices'], 'nombre'));
    }

    public function test_no_se_consultan_portafolios_ajenos(): void
    {
        Http::fake();

        $this->actingAs($this->otroUsuario)->getJson(route('mercado.cotizaciones', ['portfolio' => $this->portafolio->id]))->assertNotFound();
    }
}
