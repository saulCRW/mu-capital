<?php

namespace Tests\Feature\Tenancy;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Organization $orgA;
    private Organization $orgB;
    private User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orgA = Organization::factory()->create(['nombre' => 'Despacho A']);
        $this->orgB = Organization::factory()->create(['nombre' => 'Despacho B']);
        $this->adminA = User::factory()->admin()->create(['organization_id' => $this->orgA->id]);
        User::factory()->count(2)->create(['organization_id' => $this->orgA->id]);
        User::factory()->count(3)->create(['organization_id' => $this->orgB->id]);
    }

    public function test_sin_sesion_no_hay_filtro(): void
    {
        $this->assertSame(6, User::count());
    }

    public function test_un_usuario_solo_ve_datos_de_su_despacho(): void
    {
        $this->actingAs($this->adminA);

        $this->assertSame(3, User::count());
        $this->assertTrue(User::pluck('organization_id')->every(fn ($id) => $id === $this->orgA->id));
    }

    public function test_el_superadmin_ve_todos_los_despachos(): void
    {
        $this->actingAs(User::factory()->superadmin()->create());

        $this->assertSame(7, User::count());
    }

    public function test_al_crear_se_asigna_el_despacho_automaticamente(): void
    {
        $this->actingAs($this->adminA);

        $nuevo = User::create([
            'nombre' => 'Sin despacho explícito',
            'email' => 'nuevo@example.com',
            'password' => 'password',
            'rol' => User::ROL_ASESOR,
        ]);

        $this->assertSame($this->orgA->id, $nuevo->organization_id);
    }

    public function test_no_puede_abrir_un_usuario_de_otro_despacho(): void
    {
        $ajeno = User::where('organization_id', $this->orgB->id)->first();

        $this->actingAs($this->adminA)
            ->get(route('admin.users.edit', $ajeno))
            ->assertNotFound();
    }
}
