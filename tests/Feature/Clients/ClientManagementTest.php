<?php

namespace Tests\Feature\Clients;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

class ClientManagementTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_el_asesor_solo_ve_sus_clientes(): void
    {
        Client::create(['organization_id' => $this->org->id, 'asesor_id' => $this->otroAsesor->id, 'nombre' => 'Cliente Ajeno', 'perfil_riesgo' => 'agresivo']);

        $this->actingAs($this->asesor)->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Cliente Uno')
            ->assertDontSee('Cliente Ajeno');
    }

    public function test_el_admin_ve_todos_los_clientes_del_despacho(): void
    {
        Client::create(['organization_id' => $this->org->id, 'asesor_id' => $this->otroAsesor->id, 'nombre' => 'Cliente Ajeno', 'perfil_riesgo' => 'agresivo']);

        $this->actingAs($this->admin)->get(route('clients.index'))
            ->assertSee('Cliente Uno')
            ->assertSee('Cliente Ajeno');
    }

    public function test_un_asesor_no_puede_abrir_el_cliente_de_otro(): void
    {
        $this->actingAs($this->otroAsesor)->get(route('clients.show', $this->cliente))->assertForbidden();
        $this->actingAs($this->otroAsesor)->get(route('portfolios.show', $this->portafolio))->assertForbidden();
    }

    public function test_el_asesor_crea_clientes_a_su_nombre_aunque_envie_otro_asesor(): void
    {
        $this->actingAs($this->asesor)->post(route('clients.store'), [
            'nombre' => 'Nuevo',
            'perfil_riesgo' => 'conservador',
            'asesor_id' => $this->otroAsesor->id,
        ])->assertRedirect();

        $this->assertSame($this->asesor->id, Client::where('nombre', 'Nuevo')->value('asesor_id'));
    }

    public function test_el_admin_asigna_asesor_solo_de_su_despacho(): void
    {
        $ajeno = User::factory()->create(); // asesor de OTRO despacho

        $this->actingAs($this->admin)->post(route('clients.store'), [
            'nombre' => 'Con asesor ajeno',
            'perfil_riesgo' => 'moderado',
            'asesor_id' => $ajeno->id,
        ])->assertSessionHasErrors('asesor_id');

        $this->actingAs($this->admin)->post(route('clients.store'), [
            'nombre' => 'Con asesor válido',
            'perfil_riesgo' => 'moderado',
            'asesor_id' => $this->otroAsesor->id,
        ])->assertRedirect();

        $this->assertSame($this->otroAsesor->id, Client::where('nombre', 'Con asesor válido')->value('asesor_id'));
    }

    public function test_no_se_elimina_un_cliente_con_portafolios(): void
    {
        $this->actingAs($this->asesor)->delete(route('clients.destroy', $this->cliente))
            ->assertSessionHasErrors('general');

        $this->assertModelExists($this->cliente);
    }

    public function test_el_rol_cliente_no_entra_a_clientes(): void
    {
        $usuarioCliente = User::factory()->create(['organization_id' => $this->org->id, 'rol' => User::ROL_CLIENTE]);

        $this->actingAs($usuarioCliente)->get(route('clients.index'))->assertForbidden();
    }
}
