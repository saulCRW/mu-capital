<?php

namespace Tests\Feature\Auth;

use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_la_pantalla_de_login(): void
    {
        $this->get('/login')->assertOk()->assertSee('MU Intelligence');
    }

    public function test_inicia_sesion_con_credenciales_correctas(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_rechaza_contrasena_incorrecta(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_bloquea_tras_demasiados_intentos(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < LoginRequest::MAX_INTENTOS; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'incorrecta']);
        }

        // Aun con la contraseña correcta, queda bloqueado temporalmente.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_de_despacho_suspendido_no_puede_entrar(): void
    {
        $org = Organization::factory()->suspendida()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => __('auth.suspended')]);

        $this->assertGuest();
    }

    public function test_si_suspenden_el_despacho_se_cierra_la_sesion(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->organization->update(['estado' => Organization::ESTADO_SUSPENDIDA]);

        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_superadmin_va_al_panel_de_despachos(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin)->get('/dashboard')
            ->assertRedirect(route('superadmin.organizations.index'));
    }

    public function test_cierra_sesion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_invitado_es_enviado_al_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }
}
