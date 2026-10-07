<?php

namespace Tests\Feature\Users;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_ve_la_lista_de_usuarios(): void
    {
        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($this->admin->email);
    }

    public function test_asesor_no_puede_entrar_a_usuarios(): void
    {
        $asesor = User::factory()->create(['organization_id' => $this->admin->organization_id]);

        $this->actingAs($asesor)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_admin_crea_un_asesor_en_su_despacho(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'nombre' => 'Pedro López',
            'email' => 'pedro@example.com',
            'rol' => User::ROL_ASESOR,
            'password' => 'ClaveSegura1',
            'password_confirmation' => 'ClaveSegura1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'pedro@example.com',
            'rol' => 'asesor',
            'organization_id' => $this->admin->organization_id,
        ]);
    }

    public function test_admin_no_puede_crear_superadmin(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'nombre' => 'Intruso',
            'email' => 'intruso@example.com',
            'rol' => User::ROL_SUPERADMIN,
            'password' => 'ClaveSegura1',
            'password_confirmation' => 'ClaveSegura1',
        ])->assertSessionHasErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_correo_duplicado_es_rechazado(): void
    {
        $this->actingAs($this->admin)->post(route('admin.users.store'), [
            'nombre' => 'Duplicado',
            'email' => $this->admin->email,
            'rol' => User::ROL_ASESOR,
            'password' => 'ClaveSegura1',
            'password_confirmation' => 'ClaveSegura1',
        ])->assertSessionHasErrors('email');
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $asesor = User::factory()->create(['organization_id' => $this->admin->organization_id]);
        $hashAnterior = $asesor->password;

        $this->actingAs($this->admin)->put(route('admin.users.update', $asesor), [
            'nombre' => 'Nombre nuevo',
            'email' => $asesor->email,
            'rol' => User::ROL_ASESOR,
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $asesor->refresh();
        $this->assertSame('Nombre nuevo', $asesor->nombre);
        $this->assertSame($hashAnterior, $asesor->password);
    }

    public function test_no_se_puede_quitar_el_rol_al_unico_admin(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
            'nombre' => $this->admin->nombre,
            'email' => $this->admin->email,
            'rol' => User::ROL_ASESOR,
        ])->assertSessionHasErrors('rol');

        $this->assertSame(User::ROL_ADMIN, $this->admin->fresh()->rol);
    }

    public function test_admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $this->admin))->assertForbidden();
    }

    public function test_no_se_elimina_un_asesor_con_clientes(): void
    {
        $asesor = User::factory()->create(['organization_id' => $this->admin->organization_id]);
        Client::create([
            'organization_id' => $this->admin->organization_id,
            'asesor_id' => $asesor->id,
            'nombre' => 'Cliente X',
            'perfil_riesgo' => 'moderado',
        ]);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $asesor))
            ->assertSessionHasErrors('general');

        $this->assertModelExists($asesor);
    }

    public function test_elimina_un_asesor_sin_clientes(): void
    {
        $asesor = User::factory()->create(['organization_id' => $this->admin->organization_id]);

        $this->actingAs($this->admin)->delete(route('admin.users.destroy', $asesor))
            ->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($asesor);
    }
}
