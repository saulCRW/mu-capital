<?php

namespace Tests\Feature\Portfolios;

use App\Modules\Portfolios\Models\Portfolio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

class PortfolioManagementTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_crea_un_portafolio_para_el_cliente(): void
    {
        $this->actingAs($this->asesor)->post(route('clients.portfolios.store', $this->cliente), [
            'nombre' => 'Retiro',
            'moneda_base' => 'usd',
        ])->assertRedirect();

        $this->assertDatabaseHas('portfolios', [
            'nombre' => 'Retiro',
            'moneda_base' => 'USD',
            'client_id' => $this->cliente->id,
            'organization_id' => $this->org->id,
        ]);
    }

    public function test_no_cambia_la_moneda_base_si_ya_hay_transacciones(): void
    {
        $this->depositar('1000');

        $this->actingAs($this->asesor)->put(route('portfolios.update', $this->portafolio), [
            'nombre' => 'Principal',
            'moneda_base' => 'USD',
        ])->assertSessionHasErrors('moneda_base');

        $this->assertSame('MXN', $this->portafolio->fresh()->moneda_base);
    }

    public function test_no_elimina_un_portafolio_con_transacciones(): void
    {
        $this->depositar('1000');

        $this->actingAs($this->asesor)->delete(route('portfolios.destroy', $this->portafolio))
            ->assertSessionHasErrors('general');

        $this->assertModelExists($this->portafolio);
    }

    public function test_elimina_un_portafolio_vacio(): void
    {
        $vacio = Portfolio::create(['organization_id' => $this->org->id, 'client_id' => $this->cliente->id, 'nombre' => 'Vacío', 'moneda_base' => 'MXN']);

        $this->actingAs($this->asesor)->delete(route('portfolios.destroy', $vacio))
            ->assertRedirect(route('clients.show', $this->cliente));

        $this->assertModelMissing($vacio);
    }
}
