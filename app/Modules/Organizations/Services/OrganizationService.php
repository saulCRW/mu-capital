<?php

namespace App\Modules\Organizations\Services;

use App\Modules\Organizations\Models\Organization;
use App\Modules\Users\Models\User;
use Illuminate\Support\Facades\DB;

class OrganizationService
{
    /**
     * Da de alta un despacho junto con su primer usuario admin.
     * Ambos se guardan en una transacción: o se crean los dos, o ninguno.
     *
     * @param  array{nombre:string, plan_id:int, tasa_libre_riesgo_pct:string, admin_nombre:string, admin_email:string, admin_password:string}  $data
     */
    public function create(array $data): Organization
    {
        return DB::transaction(function () use ($data) {
            $organization = Organization::create([
                'nombre' => $data['nombre'],
                'plan_id' => $data['plan_id'],
                'estado' => Organization::ESTADO_ACTIVA,
                'fecha_alta' => now()->toDateString(),
                'tasa_libre_riesgo' => $this->pctToFraction($data['tasa_libre_riesgo_pct']),
            ]);

            User::create([
                'organization_id' => $organization->id,
                'nombre' => $data['admin_nombre'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'rol' => User::ROL_ADMIN,
            ]);

            return $organization;
        });
    }

    /** @param  array{nombre:string, plan_id:int, estado:string, tasa_libre_riesgo_pct:string}  $data */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update([
            'nombre' => $data['nombre'],
            'plan_id' => $data['plan_id'],
            'estado' => $data['estado'],
            'tasa_libre_riesgo' => $this->pctToFraction($data['tasa_libre_riesgo_pct']),
        ]);

        return $organization;
    }

    /** "10.5" (porcentaje que captura el usuario) → "0.105000" (fracción que se guarda). */
    public function pctToFraction(string $pct): string
    {
        return bcdiv($pct, '100', 6);
    }

    /** "0.105000" → "10.5" para mostrarlo en el formulario. */
    public function fractionToPct(string $fraction): string
    {
        return rtrim(rtrim(bcmul($fraction, '100', 4), '0'), '.');
    }
}
