<?php

namespace Tests\Feature\Users;

use App\Modules\Billing\Models\Plan;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Concerns\CreaEscenario;
use Tests\TestCase;

/** Registro abierto, "Mi cuenta" y administración de cuentas por el superadmin. */
class AccountTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crearEscenario();
    }

    public function test_cualquiera_se_registra_y_empieza_en_el_plan_gratis(): void
    {
        $gratis = Plan::factory()->create(['nombre' => 'Gratis', 'max_portafolios' => 2]);

        $this->post(route('register'), [
            'nombre' => 'Nueva Persona', 'email' => 'Nueva@Example.com',
            'password' => 'ClaveSegura1', 'password_confirmation' => 'ClaveSegura1',
        ])->assertRedirect(route('dashboard'));

        $nuevo = User::where('email', 'nueva@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($nuevo);
        $this->assertSame([User::ROL_USUARIO, User::ESTADO_ACTIVO, $gratis->id], [$nuevo->rol, $nuevo->estado, $nuevo->plan_id]);
    }

    public function test_no_se_registra_dos_veces_el_mismo_correo(): void
    {
        $this->post(route('register'), [
            'nombre' => 'Copia', 'email' => $this->usuario->email,
            'password' => 'ClaveSegura1', 'password_confirmation' => 'ClaveSegura1',
        ])->assertSessionHasErrors(['email' => 'Ya existe una cuenta con ese correo. Inicia sesión o recupera tu contraseña.']);
    }

    public function test_edita_sus_datos_y_su_tasa_libre_de_riesgo(): void
    {
        $this->actingAs($this->usuario)->put(route('cuenta.update'), [
            'nombre' => 'Nombre Nuevo', 'email' => 'nuevo@example.com', 'tasa_libre_riesgo' => '7.25',
        ])->assertRedirect(route('cuenta.edit'));

        $this->usuario->refresh();
        $this->assertSame(['Nombre Nuevo', 'nuevo@example.com', '0.072500'], [$this->usuario->nombre, $this->usuario->email, $this->usuario->tasa_libre_riesgo]);
    }

    public function test_cambiar_la_contrasena_pide_la_actual(): void
    {
        $datos = ['nombre' => $this->usuario->nombre, 'email' => $this->usuario->email, 'tasa_libre_riesgo' => '0', 'password' => 'OtraClave99', 'password_confirmation' => 'OtraClave99'];

        $this->actingAs($this->usuario)->put(route('cuenta.update'), $datos + ['password_actual' => 'incorrecta'])
            ->assertSessionHasErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        $this->actingAs($this->usuario)->put(route('cuenta.update'), $datos + ['password_actual' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('OtraClave99', $this->usuario->fresh()->password));
    }

    public function test_el_superadmin_cambia_el_plan_y_suspende_cuentas(): void
    {
        $pro = Plan::factory()->create(['nombre' => 'Pro']);

        $this->actingAs($this->superadmin)->get(route('superadmin.users.index'))
            ->assertSee($this->usuario->email)
            ->assertDontSee($this->superadmin->email);
        $this->actingAs($this->superadmin)->put(route('superadmin.users.update', $this->usuario), ['plan_id' => $pro->id, 'estado' => User::ESTADO_SUSPENDIDO])
            ->assertRedirect(route('superadmin.users.index'));

        $this->assertSame([$pro->id, User::ESTADO_SUSPENDIDO], [$this->usuario->fresh()->plan_id, $this->usuario->fresh()->estado]);
    }

    public function test_un_usuario_no_administra_cuentas(): void
    {
        $this->actingAs($this->usuario)->get(route('superadmin.users.index'))->assertForbidden();
        $this->actingAs($this->usuario)->put(route('superadmin.users.update', $this->otroUsuario), ['plan_id' => $this->usuario->plan_id, 'estado' => User::ESTADO_SUSPENDIDO])
            ->assertForbidden();
    }
}
