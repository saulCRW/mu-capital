<?php

namespace Tests\Feature\Prices;

use App\Modules\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Mercado muestra los activos de la cartera del usuario más los de su lista personal. */
class WatchlistTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');
        config(['services.yahoo_finance.url' => 'https://yahoo.test']);
        Http::fake(['yahoo.test/*' => Http::response(['chart' => ['result' => null, 'error' => ['code' => 'Not Found']]], 404)]);
    }

    public function test_solo_se_cotiza_lo_de_la_cartera_y_la_lista(): void
    {
        $this->depositar('10000');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '10', 'precio' => '50']);
        $msft = Asset::create(['simbolo' => 'MSFT', 'nombre' => 'Microsoft', 'tipo' => 'accion', 'moneda' => 'USD', 'exchange' => 'NASDAQ']);
        $this->usuario->watchlist()->attach($msft->id);

        $datos = $this->actingAs($this->usuario)->getJson(route('api.v1.mercado.cotizaciones'))->assertOk()->json('data');

        $this->assertEqualsCanonicalizing([$this->naftrac->id, $msft->id], array_map('intval', array_keys($datos['activos'])));
    }

    public function test_agrega_y_quita_activos_de_su_lista(): void
    {
        $this->actingAs($this->usuario)->post(route('mercado.lista.store'), ['asset_id' => $this->aapl->id])
            ->assertRedirect(route('mercado'))
            ->assertSessionHas('status', 'Activo agregado a tu lista.');
        $this->assertSame([$this->aapl->id], $this->usuario->watchlist()->pluck('assets.id')->all());
        $this->assertSame([], $this->otroUsuario->watchlist()->pluck('assets.id')->all());

        $this->actingAs($this->usuario)->get(route('mercado'))->assertSee('Quitar AAPL de tu lista');

        $this->actingAs($this->usuario)->delete(route('mercado.lista.destroy', $this->aapl))
            ->assertSessionHas('status', 'AAPL ya no está en tu lista.');
        $this->assertSame([], $this->usuario->watchlist()->pluck('assets.id')->all());
    }

    public function test_sin_cartera_ni_lista_explica_como_empezar(): void
    {
        $this->actingAs($this->usuario)->get(route('mercado'))
            ->assertSee('Tu lista está vacía.')
            ->assertDontSee('data-activo=', false);
    }

    public function test_no_se_puede_seguir_el_efectivo(): void
    {
        $efectivo = Asset::create(['simbolo' => 'MXN', 'nombre' => 'Efectivo', 'tipo' => 'efectivo', 'moneda' => 'MXN']);

        $this->actingAs($this->usuario)->post(route('mercado.lista.store'), ['asset_id' => $efectivo->id])
            ->assertSessionHasErrors(['asset_id' => 'Ese activo no está en el catálogo.']);
    }
}
