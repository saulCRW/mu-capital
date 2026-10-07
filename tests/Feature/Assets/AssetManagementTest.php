<?php

namespace Tests\Feature\Assets;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_un_usuario_da_de_alta_un_activo(): void
    {
        $this->actingAs($this->usuario)->post(route('assets.store'), [
            'simbolo' => 'walmex', 'nombre' => 'Walmart de México', 'tipo' => 'accion',
            'sector' => 'Consumo', 'pais' => 'México', 'moneda' => 'mxn', 'exchange' => 'bmv',
        ])->assertRedirect(route('assets.index'));

        $this->assertDatabaseHas('assets', ['simbolo' => 'WALMEX', 'moneda' => 'MXN', 'exchange' => 'BMV']);
    }

    public function test_no_repite_simbolo_en_el_mismo_exchange(): void
    {
        $this->actingAs($this->usuario)->post(route('assets.store'), [
            'simbolo' => 'NAFTRAC', 'nombre' => 'Duplicado', 'tipo' => 'etf', 'moneda' => 'MXN', 'exchange' => 'BMV',
        ])->assertSessionHasErrors('simbolo');
    }

    public function test_solo_el_superadmin_edita_el_catalogo(): void
    {
        $datos = ['simbolo' => 'NAFTRAC', 'nombre' => 'Nuevo nombre', 'tipo' => 'etf', 'moneda' => 'MXN', 'exchange' => 'BMV'];

        $this->actingAs($this->usuario)->put(route('assets.update', $this->naftrac), $datos)->assertForbidden();

        $this->actingAs($this->superadmin)
            ->put(route('assets.update', $this->naftrac), $datos)
            ->assertRedirect(route('assets.index'));

        $this->assertSame('Nuevo nombre', $this->naftrac->fresh()->nombre);
    }

    public function test_no_se_elimina_un_activo_con_transacciones(): void
    {
        $this->depositar('10000');
        $this->registrar(['tipo' => 'COMPRA', 'fecha' => '2026-01-06', 'asset_id' => $this->naftrac->id, 'cantidad' => '10', 'precio' => '60']);

        $this->actingAs($this->superadmin)
            ->delete(route('assets.destroy', $this->naftrac))
            ->assertSessionHasErrors('general');

        $this->assertModelExists($this->naftrac);
    }
}
