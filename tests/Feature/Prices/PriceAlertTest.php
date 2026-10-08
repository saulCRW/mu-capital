<?php

namespace Tests\Feature\Prices;

use App\Modules\Prices\Models\PriceAlert;
use App\Modules\Prices\Services\PriceAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Alertas de precio: AAPL está en la lista del usuario y cotiza a 336.67 USD. */
class PriceAlertTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-10-07 13:00:00');
        $this->usuario->watchlist()->attach($this->aapl->id);
        config(['services.yahoo_finance.url' => 'https://yahoo.test']);
        $hora = now()->timestamp;
        Http::fake([
            'yahoo.test/v8/finance/chart/AAPL*' => Http::response(['chart' => ['result' => [[
                'meta' => ['regularMarketPrice' => 336.67, 'previousClose' => 333.63, 'regularMarketTime' => $hora, 'currency' => 'USD',
                    'currentTradingPeriod' => ['regular' => ['start' => $hora - 3600, 'end' => $hora + 3600]]],
                'timestamp' => [$hora], 'indicators' => ['quote' => [['close' => [336.67]]]],
            ]], 'error' => null]]),
            'yahoo.test/*' => Http::response(['chart' => ['result' => null, 'error' => ['code' => 'Not Found']]], 404),
        ]);
    }

    private function alerta(string $condicion, string $precio): void
    {
        $this->actingAs($this->usuario)->post(route('mercado.alertas.store'), ['asset_id' => $this->aapl->id, 'condicion' => $condicion, 'precio' => $precio])
            ->assertSessionHasNoErrors();
    }

    public function test_se_dispara_con_el_precio_del_momento_una_sola_vez(): void
    {
        $this->alerta('arriba', '335');
        $this->alerta('abajo', '300'); // 336.67 no está por debajo de 300: sigue activa

        $this->actingAs($this->usuario)->getJson(route('api.v1.mercado.cotizaciones'))
            ->assertOk()
            ->assertJsonCount(1, 'data.alertas')
            ->assertJsonPath('data.alertas.0.mensaje', 'AAPL subió a 336.67 USD (tu alerta: 335.00)');

        $disparada = PriceAlert::where('condicion', 'arriba')->first();
        $this->assertSame('336.670000', $disparada->precio_disparo);
        $this->assertNotNull($disparada->disparada_en);
        $this->assertNull(PriceAlert::where('condicion', 'abajo')->value('disparada_en'));

        // La siguiente consulta ya no la repite.
        $this->actingAs($this->usuario)->getJson(route('api.v1.mercado.cotizaciones'))->assertJsonCount(0, 'data.alertas');
    }

    public function test_el_aviso_aparece_en_inicio_y_mercado_hasta_marcarlo_como_visto(): void
    {
        $this->alerta('arriba', '335');
        $this->actingAs($this->usuario)->getJson(route('api.v1.mercado.cotizaciones'));

        $this->actingAs($this->usuario)->get(route('dashboard'))->assertSee('AAPL subió a 336.67 USD (tu alerta: 335.00)');
        $this->actingAs($this->usuario)->get(route('mercado'))->assertSeeInOrder(['Alertas de precio', 'AAPL subió a 336.67 USD', 'disparada']);

        $this->actingAs($this->usuario)->post(route('mercado.alertas.vistas'))->assertRedirect();
        $this->actingAs($this->usuario)->get(route('dashboard'))->assertDontSee('AAPL subió a');
    }

    public function test_cada_usuario_ve_y_borra_solo_las_suyas(): void
    {
        $this->alerta('arriba', '400');
        $alerta = PriceAlert::firstOrFail();
        $this->flushSession(); // sin el mensaje "Alerta creada…" del primer usuario

        $this->actingAs($this->otroUsuario)->get(route('mercado'))->assertDontSee('400.00 USD');
        $this->actingAs($this->otroUsuario)->delete(route('mercado.alertas.destroy', $alerta))->assertNotFound();
        $this->actingAs($this->superadmin)->delete(route('mercado.alertas.destroy', $alerta))->assertNotFound();
        $this->assertModelExists($alerta);

        $this->actingAs($this->usuario)->delete(route('mercado.alertas.destroy', $alerta))->assertSessionHas('status', 'Alerta eliminada.');
        $this->assertModelMissing($alerta);
    }

    public function test_los_cierres_disparan_las_alertas_de_todos_los_usuarios(): void
    {
        $this->alerta('abajo', '320');
        PriceAlert::create(['user_id' => $this->otroUsuario->id, 'asset_id' => $this->aapl->id, 'condicion' => 'abajo', 'precio' => '310']);

        // Cierre de 315: cumple "baja a 320", pero no "baja a 310".
        $disparadas = app(PriceAlertService::class)->revisar([$this->aapl->id => 315.0]);

        $this->assertSame([$this->usuario->id], $disparadas->pluck('user_id')->all());
    }

    public function test_valida_la_alerta_y_el_maximo_de_activas(): void
    {
        $this->actingAs($this->usuario)->post(route('mercado.alertas.store'), ['asset_id' => $this->aapl->id, 'condicion' => 'arriba', 'precio' => '0'])
            ->assertSessionHasErrors(['precio' => 'El precio debe ser mayor que cero.']);

        for ($i = 1; $i <= PriceAlert::MAX_ACTIVAS; $i++) {
            PriceAlert::create(['user_id' => $this->usuario->id, 'asset_id' => $this->aapl->id, 'condicion' => 'arriba', 'precio' => 1000 + $i]);
        }
        $this->actingAs($this->usuario)->post(route('mercado.alertas.store'), ['asset_id' => $this->aapl->id, 'condicion' => 'arriba', 'precio' => '500'])
            ->assertSessionHasErrors(['alerta' => 'Tienes 50 alertas activas; borra alguna para crear otra.']);
    }
}
