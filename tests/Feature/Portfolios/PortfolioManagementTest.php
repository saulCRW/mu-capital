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

    public function test_crea_un_portafolio_propio(): void
    {
        $this->actingAs($this->usuario)->post(route('portfolios.store'), [
            'nombre' => 'Retiro',
            'moneda_base' => 'usd',
        ])->assertRedirect();

        $this->assertDatabaseHas('portfolios', [
            'nombre' => 'Retiro',
            'moneda_base' => 'USD',
            'user_id' => $this->usuario->id,
        ]);
    }

    public function test_lista_solo_los_portafolios_propios(): void
    {
        Portfolio::create(['user_id' => $this->otroUsuario->id, 'nombre' => 'Ajeno', 'moneda_base' => 'MXN']);

        $this->actingAs($this->usuario)->get(route('portfolios.index'))
            ->assertSee('Principal')
            ->assertDontSee('Ajeno');
    }

    public function test_nadie_mas_ve_ni_edita_un_portafolio_ajeno(): void
    {
        $this->actingAs($this->otroUsuario)->get(route('portfolios.show', $this->portafolio))->assertNotFound();
        $this->actingAs($this->otroUsuario)->put(route('portfolios.update', $this->portafolio), ['nombre' => 'Mío', 'moneda_base' => 'MXN'])->assertNotFound();
        $this->actingAs($this->superadmin)->get(route('portfolios.show', $this->portafolio))->assertForbidden();

        $this->assertSame('Principal', $this->portafolio->fresh()->nombre);
    }

    public function test_no_cambia_la_moneda_base_si_ya_hay_transacciones(): void
    {
        $this->depositar('1000');

        $this->actingAs($this->usuario)->put(route('portfolios.update', $this->portafolio), [
            'nombre' => 'Principal',
            'moneda_base' => 'USD',
        ])->assertSessionHasErrors('moneda_base');

        $this->assertSame('MXN', $this->portafolio->fresh()->moneda_base);
    }

    public function test_no_elimina_un_portafolio_con_transacciones(): void
    {
        $this->depositar('1000');

        $this->actingAs($this->usuario)->delete(route('portfolios.destroy', $this->portafolio))
            ->assertSessionHasErrors('general');

        $this->assertModelExists($this->portafolio);
    }

    public function test_elimina_un_portafolio_vacio(): void
    {
        $vacio = Portfolio::create(['user_id' => $this->usuario->id, 'nombre' => 'Vacío', 'moneda_base' => 'MXN']);

        $this->actingAs($this->usuario)->delete(route('portfolios.destroy', $vacio))
            ->assertRedirect(route('portfolios.index'));

        $this->assertModelMissing($vacio);
    }

    public function test_eliminar_pide_confirmacion_en_un_modal(): void
    {
        $this->actingAs($this->usuario)->get(route('portfolios.show', $this->portafolio))
            ->assertSee('<dialog id="confirmar-', false)
            ->assertSeeInOrder(['Eliminar portafolio', 'No se puede deshacer.', 'Cancelar', 'action="'.route('portfolios.destroy', $this->portafolio).'"'], false)
            ->assertSee('name="_method" value="DELETE"', false);
    }
}
