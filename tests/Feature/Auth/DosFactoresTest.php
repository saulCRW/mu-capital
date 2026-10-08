<?php

namespace Tests\Feature\Auth;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Auth\Controllers\DosFactoresLoginController;
use App\Modules\Auth\Services\DosFactores;
use App\Modules\Auth\Services\Totp;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Doble factor: activarlo desde Mi cuenta, pedir el código al iniciar sesión y desactivarlo. */
class DosFactoresTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** @var list<string> */
    private array $codigosRecuperacion = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-08 12:00:00');
        $this->user = User::factory()->create();
    }

    public function test_se_activa_con_un_codigo_de_la_app(): void
    {
        $this->actingAs($this->user)->post(route('cuenta.dos-factores.iniciar'))->assertRedirect(route('cuenta.edit').'#dos-factores');
        $clave = session(DosFactores::SESION_PENDIENTE);
        $this->get(route('cuenta.edit'))->assertSee('<svg', false)->assertSee(trim(chunk_split($clave, 4, ' ')));

        $this->post(route('cuenta.dos-factores.confirmar'), ['codigo' => '000000'])->assertSessionHasErrors('codigo');
        $this->assertFalse($this->user->fresh()->tieneDosFactores());

        $this->post(route('cuenta.dos-factores.confirmar'), ['codigo' => $this->codigo($clave)])
            ->assertSessionHas('codigos_recuperacion', fn (array $codigos) => count($codigos) === DosFactores::CODIGOS_RECUPERACION);

        $user = $this->user->fresh();
        $this->assertTrue($user->tieneDosFactores());
        $this->assertSame($clave, $user->dos_factores_secreto);
        // En la base la clave está cifrada.
        $this->assertNotSame($clave, DB::table('users')->where('id', $user->id)->value('dos_factores_secreto'));
    }

    public function test_el_login_pide_el_codigo_despues_de_la_contrasena(): void
    {
        $clave = $this->activar();

        $this->post(route('login'), ['email' => $this->user->email, 'password' => 'password'])
            ->assertRedirect(route('login.dos-factores'));
        $this->assertGuest();

        $this->post(route('login.dos-factores'), ['codigo' => '000000'])->assertSessionHasErrors(['codigo' => 'El código no es correcto o ya se usó.']);
        $this->assertGuest();

        $this->post(route('login.dos-factores'), ['codigo' => $this->codigo($clave)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_un_codigo_ya_usado_no_sirve_otra_vez(): void
    {
        $clave = $this->activar();
        $this->entrarConCodigo($this->codigo($clave))->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        $this->entrarConCodigo($this->codigo($clave))->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_un_codigo_de_recuperacion_sirve_una_sola_vez(): void
    {
        $this->activar();
        $codigo = $this->codigosRecuperacion[0];

        $this->entrarConCodigo(strtoupper($codigo))->assertRedirect(route('dashboard'));
        $this->assertCount(DosFactores::CODIGOS_RECUPERACION - 1, $this->user->fresh()->dos_factores_recuperacion);
        $this->post(route('logout'));

        $this->entrarConCodigo($codigo)->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_bloquea_tras_demasiados_codigos_incorrectos(): void
    {
        $clave = $this->activar();
        $this->post(route('login'), ['email' => $this->user->email, 'password' => 'password']);
        for ($i = 0; $i < DosFactoresLoginController::MAX_INTENTOS; $i++) {
            $this->post(route('login.dos-factores'), ['codigo' => '000000']);
        }

        $this->post(route('login.dos-factores'), ['codigo' => $this->codigo($clave)])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_el_codigo_se_espera_solo_unos_minutos(): void
    {
        $clave = $this->activar();
        $this->post(route('login'), ['email' => $this->user->email, 'password' => 'password']);

        $this->travel(DosFactores::MINUTOS_LOGIN + 1)->minutes();

        $this->post(route('login.dos-factores'), ['codigo' => $this->codigo($clave)])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_desactivarlo_pide_la_contrasena_y_un_codigo(): void
    {
        $clave = $this->activar();

        $this->actingAs($this->user)->delete(route('cuenta.dos-factores.desactivar'), ['password_actual' => 'incorrecta', 'codigo' => $this->codigo($clave)])
            ->assertSessionHasErrorsIn('dosFactores', ['password_actual' => 'La contraseña actual no es correcta.']);
        $this->assertTrue($this->user->fresh()->tieneDosFactores());

        $this->delete(route('cuenta.dos-factores.desactivar'), ['password_actual' => 'password', 'codigo' => $this->codigo($clave)])
            ->assertRedirect(route('cuenta.edit'));
        $this->assertFalse($this->user->fresh()->tieneDosFactores());
    }

    public function test_el_superadmin_lo_quita_y_un_usuario_no_puede(): void
    {
        $this->activar();
        $otro = User::factory()->create();

        $this->actingAs($otro)->delete(route('superadmin.users.dos-factores.destroy', $this->user))->assertForbidden();
        $this->assertTrue($this->user->fresh()->tieneDosFactores());

        $this->actingAs(User::factory()->superadmin()->create())->delete(route('superadmin.users.dos-factores.destroy', $this->user))
            ->assertRedirect(route('superadmin.users.edit', $this->user));
        $this->assertFalse($this->user->fresh()->tieneDosFactores());
    }

    public function test_la_auditoria_no_guarda_la_clave_ni_los_codigos(): void
    {
        $clave = $this->activar();

        $cambio = AuditLog::where('entidad', 'User')->where('accion', 'cambio')->latest('id')->firstOrFail();
        $this->assertSame('********', $cambio->despues['dos_factores_secreto']);
        $this->assertSame('********', $cambio->despues['dos_factores_recuperacion']);
        $this->assertStringNotContainsString($clave, json_encode($cambio->despues));
        $this->assertArrayNotHasKey('dos_factores_ultimo_paso', $cambio->despues);
    }

    /** Activa el doble factor con el código del paso anterior: el del paso actual queda libre para entrar. */
    private function activar(): string
    {
        $clave = Totp::nuevaClave();
        $this->codigosRecuperacion = app(DosFactores::class)->activar($this->user, $clave, Totp::codigo($clave, $this->paso() - 1));

        return $clave;
    }

    private function entrarConCodigo(string $codigo): TestResponse
    {
        $this->post(route('login'), ['email' => $this->user->email, 'password' => 'password']);

        return $this->post(route('login.dos-factores'), ['codigo' => $codigo]);
    }

    private function codigo(string $clave): string
    {
        return Totp::codigo($clave, $this->paso());
    }

    private function paso(): int
    {
        return Totp::paso(now()->getTimestamp());
    }
}
