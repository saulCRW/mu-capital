<?php

namespace Tests\Feature\Reports;

use App\Modules\Clients\Models\Client;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portfolios\Models\Portfolio;
use App\Modules\Transactions\Models\Transaction;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Portal del usuario cliente: consulta sus portafolios y reportes, sin poder modificar nada. */
class ClientPortalTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    private User $usuarioCliente;

    private Portfolio $deOtroCliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
        $this->travelTo('2026-03-02 12:00:00');

        $this->usuarioCliente = User::factory()->create([
            'organization_id' => $this->org->id, 'rol' => User::ROL_CLIENTE, 'client_id' => $this->cliente->id,
        ]);
        $otro = Client::create(['organization_id' => $this->org->id, 'asesor_id' => $this->asesor->id, 'nombre' => 'Cliente Dos', 'perfil_riesgo' => 'moderado']);
        $this->deOtroCliente = Portfolio::create(['organization_id' => $this->org->id, 'client_id' => $otro->id, 'nombre' => 'Ajeno', 'moneda_base' => 'MXN']);

        $this->depositar('10000', '2026-01-05');
        $this->actingAs($this->asesor)->post(route('portfolios.transactions.store', $this->deOtroCliente), ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-05', 'cantidad' => '7000']);
    }

    public function test_ve_solo_sus_portafolios_en_el_inicio(): void
    {
        $response = $this->actingAs($this->usuarioCliente)->get(route('dashboard'));

        $response->assertSeeInOrder(['Tu patrimonio', '10,000.00', 'MXN · 1 portafolio']);
        $response->assertSee(route('portfolios.reporte', $this->portafolio), false);
        $response->assertDontSee('Ajeno');
        $response->assertDontSee('href="'.route('portfolios.show', $this->portafolio).'"', false);
    }

    public function test_consulta_el_reporte_y_el_pdf_de_su_portafolio(): void
    {
        $this->actingAs($this->usuarioCliente)->get(route('portfolios.reporte', [$this->portafolio, 'anio' => 2026, 'trimestre' => 1]))
            ->assertSee('Reporte trimestral · 1T 2026');
        $this->actingAs($this->usuarioCliente)->get(route('portfolios.reporte.pdf', [$this->portafolio, 'anio' => 2026, 'trimestre' => 1]))
            ->assertDownload();
    }

    public function test_no_ve_el_reporte_de_otro_cliente(): void
    {
        $this->actingAs($this->usuarioCliente)->get(route('portfolios.reporte', [$this->deOtroCliente, 'anio' => 2026, 'trimestre' => 1]))
            ->assertForbidden();
    }

    public function test_no_puede_gestionar_su_cartera(): void
    {
        $this->actingAs($this->usuarioCliente)->get(route('portfolios.show', $this->portafolio))->assertForbidden();
        $this->actingAs($this->usuarioCliente)
            ->post(route('portfolios.transactions.store', $this->portafolio), ['tipo' => 'DEPOSITO', 'fecha' => '2026-01-06', 'cantidad' => '1'])
            ->assertForbidden();

        // Aunque una ruta dejara pasar su rol, las policies solo le permiten consultar.
        $this->assertTrue($this->usuarioCliente->can('view', $this->portafolio));
        $this->assertFalse($this->usuarioCliente->can('update', $this->portafolio));
        $this->assertFalse($this->usuarioCliente->can('update', $this->cliente));
        $this->assertFalse($this->usuarioCliente->can('create', [Transaction::class, $this->portafolio]));
    }

    public function test_sin_cliente_ligado_ve_un_aviso(): void
    {
        $sinLigar = User::factory()->create(['organization_id' => $this->org->id, 'rol' => User::ROL_CLIENTE]);

        $this->actingAs($sinLigar)->get(route('dashboard'))
            ->assertSee('Tu cuenta todavía no está ligada a tus portafolios.');
    }

    public function test_el_admin_liga_el_usuario_cliente_a_un_cliente_de_su_despacho(): void
    {
        $datos = ['nombre' => 'Uno', 'email' => 'uno@example.com', 'rol' => User::ROL_CLIENTE, 'password' => 'ClaveSegura1', 'password_confirmation' => 'ClaveSegura1'];
        $ajeno = Client::withoutGlobalScopes()->create([
            'organization_id' => Organization::factory()->create()->id, 'asesor_id' => $this->asesor->id, 'nombre' => 'De otro despacho', 'perfil_riesgo' => 'moderado',
        ]);

        $this->actingAs($this->admin)->post(route('admin.users.store'), $datos)->assertSessionHasErrors(['client_id' => 'Elige el cliente al que pertenece este usuario.']);
        $this->actingAs($this->admin)->post(route('admin.users.store'), $datos + ['client_id' => $ajeno->id])->assertSessionHasErrors('client_id');
        $this->actingAs($this->admin)->post(route('admin.users.store'), $datos + ['client_id' => $this->cliente->id])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'uno@example.com', 'client_id' => $this->cliente->id]);
    }

    public function test_al_dejar_de_ser_cliente_se_desliga(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->usuarioCliente), [
            'nombre' => $this->usuarioCliente->nombre, 'email' => $this->usuarioCliente->email,
            'rol' => User::ROL_ASESOR, 'client_id' => $this->cliente->id,
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->usuarioCliente->fresh()->client_id);
    }
}
