<?php

namespace Tests\Feature\Organizations;

use App\Modules\Billing\Models\Plan;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->superadmin()->create();
    }

    public function test_superadmin_ve_la_lista_de_despachos(): void
    {
        $org = Organization::factory()->create(['nombre' => 'Despacho Uno']);

        $this->actingAs($this->superadmin)->get(route('superadmin.organizations.index'))
            ->assertOk()
            ->assertSee('Despacho Uno');
    }

    public function test_admin_de_despacho_no_entra_al_panel_de_superadmin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('superadmin.organizations.index'))->assertForbidden();
    }

    public function test_superadmin_crea_despacho_con_su_admin(): void
    {
        $plan = Plan::factory()->create();

        $this->actingAs($this->superadmin)->post(route('superadmin.organizations.store'), [
            'nombre' => 'Asesores del Norte',
            'plan_id' => $plan->id,
            'tasa_libre_riesgo_pct' => '10.5',
            'admin_nombre' => 'Marta Ríos',
            'admin_email' => 'marta@norte.test',
            'admin_password' => 'ClaveSegura1',
            'admin_password_confirmation' => 'ClaveSegura1',
        ])->assertRedirect(route('superadmin.organizations.index'));

        $org = Organization::where('nombre', 'Asesores del Norte')->firstOrFail();
        $this->assertSame('0.105000', $org->tasa_libre_riesgo);
        $this->assertSame(Organization::ESTADO_ACTIVA, $org->estado);
        $this->assertDatabaseHas('users', [
            'email' => 'marta@norte.test',
            'rol' => User::ROL_ADMIN,
            'organization_id' => $org->id,
        ]);
    }

    public function test_si_falla_la_validacion_no_se_crea_nada(): void
    {
        $plan = Plan::factory()->create();
        $existente = User::factory()->create();

        $this->actingAs($this->superadmin)->post(route('superadmin.organizations.store'), [
            'nombre' => 'Repetido',
            'plan_id' => $plan->id,
            'tasa_libre_riesgo_pct' => '10',
            'admin_nombre' => 'X',
            'admin_email' => $existente->email,
            'admin_password' => 'ClaveSegura1',
            'admin_password_confirmation' => 'ClaveSegura1',
        ])->assertSessionHasErrors('admin_email');

        $this->assertDatabaseMissing('organizations', ['nombre' => 'Repetido']);
    }

    public function test_superadmin_cambia_plan_estado_y_tasa(): void
    {
        $org = Organization::factory()->create();
        $nuevoPlan = Plan::factory()->create();

        $this->actingAs($this->superadmin)->put(route('superadmin.organizations.update', $org), [
            'nombre' => $org->nombre,
            'plan_id' => $nuevoPlan->id,
            'estado' => Organization::ESTADO_SUSPENDIDA,
            'tasa_libre_riesgo_pct' => '9.75',
        ])->assertRedirect(route('superadmin.organizations.index'));

        $org->refresh();
        $this->assertSame($nuevoPlan->id, $org->plan_id);
        $this->assertSame(Organization::ESTADO_SUSPENDIDA, $org->estado);
        $this->assertSame('0.097500', $org->tasa_libre_riesgo);
    }
}
