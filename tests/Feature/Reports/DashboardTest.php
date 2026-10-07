<?php

namespace Tests\Feature\Reports;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Prices\Models\FxRate;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Hoy = 02/03/2026. El usuario tiene un portafolio en MXN (deposita 10,000) y otro
 * en USD (deposita 1,000). Con USD→MXN = 20 su patrimonio es 10,000 + 1,000 × 20 = 30,000 MXN.
 * Otro usuario tiene su propio portafolio con 5,000 MXN.
 */
class DashboardTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    private Portfolio $enDolares;

    private Portfolio $ajeno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');

        $this->enDolares = Portfolio::create(['user_id' => $this->usuario->id, 'nombre' => 'Dólares', 'moneda_base' => 'USD']);
        $this->ajeno = Portfolio::create(['user_id' => $this->otroUsuario->id, 'nombre' => 'Ajeno', 'moneda_base' => 'MXN']);

        $this->depositar('10000', '2026-01-05');
        $this->depositarEn($this->enDolares, '1000', $this->usuario);
        $this->depositarEn($this->ajeno, '5000', $this->otroUsuario);
    }

    private function depositarEn(Portfolio $portfolio, string $monto, User $como): void
    {
        $this->actingAs($como)->post(route('portfolios.transactions.store', $portfolio), ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => $monto])
            ->assertSessionHasNoErrors();
    }

    public function test_ve_sus_portafolios_con_el_total_convertido(): void
    {
        // insert(): en SQLite el cast de fecha guardaría la hora y rompería la comparación de texto.
        FxRate::insert(['moneda_origen' => 'USD', 'moneda_destino' => 'MXN', 'fecha' => '2026-01-02', 'tasa' => '20']);

        $response = $this->actingAs($this->usuario)->get(route('dashboard'));

        $response->assertSeeInOrder([
            'Tu patrimonio', '30,000.00', 'MXN · 2 portafolios',
            'Aportaciones netas 2026', '30,000.00',
        ]);
        // Ordenados por valor en MXN: 20,000 (USD) antes que 10,000; peso 66.67 % y 33.33 %.
        $response->assertSeeInOrder(['Dólares', '1,000.00', 'USD', '66.67 %', 'Principal', '10,000.00', 'MXN', '33.33 %']);
        $response->assertDontSee('Ajeno');
    }

    public function test_sin_tipo_de_cambio_el_portafolio_queda_fuera_del_total(): void
    {
        $response = $this->actingAs($this->usuario)->get(route('dashboard'));

        $response->assertSeeInOrder(['Tu patrimonio', '10,000.00']);
        $response->assertSee('Sin tipo de cambio a MXN para USD: esos portafolios no entran en los totales.');
    }

    public function test_el_otro_usuario_solo_ve_lo_suyo(): void
    {
        $response = $this->actingAs($this->otroUsuario)->get(route('dashboard'));

        $response->assertSeeInOrder(['Tu patrimonio', '5,000.00', 'MXN · 1 portafolio']);
        $response->assertDontSee('Principal');
    }

    public function test_un_usuario_nuevo_ve_como_empezar(): void
    {
        $nuevo = User::factory()->create();

        $this->actingAs($nuevo)->get(route('dashboard'))
            ->assertSee('Todavía no tienes portafolios.')
            ->assertSee(route('portfolios.create'), false);
    }
}
