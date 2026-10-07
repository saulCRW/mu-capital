<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Cada usuario solo ve sus propios datos (Global Scope por dueño). */
class TenantScopeTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        Portfolio::create(['user_id' => $this->otroUsuario->id, 'nombre' => 'Del otro', 'moneda_base' => 'MXN']);
        $this->depositar('1000');
    }

    public function test_sin_sesion_no_hay_filtro(): void
    {
        auth()->logout();

        $this->assertSame(2, Portfolio::count());
    }

    public function test_un_usuario_solo_ve_sus_datos(): void
    {
        $this->actingAs($this->otroUsuario);

        $this->assertSame(['Del otro'], Portfolio::pluck('nombre')->all());
        $this->assertSame(0, Transaction::count());
    }

    public function test_el_superadmin_no_tiene_filtro(): void
    {
        $this->actingAs($this->superadmin);

        $this->assertSame(2, Portfolio::count());
    }

    public function test_al_crear_se_asigna_el_dueno_automaticamente(): void
    {
        $this->actingAs($this->otroUsuario);

        $nuevo = Portfolio::create(['nombre' => 'Sin dueño explícito', 'moneda_base' => 'USD']);

        $this->assertSame($this->otroUsuario->id, $nuevo->user_id);
    }

    public function test_no_puede_abrir_un_portafolio_ajeno(): void
    {
        $this->actingAs($this->otroUsuario)
            ->get(route('portfolios.show', $this->portafolio))
            ->assertNotFound();
    }
}
