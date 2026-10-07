<?php

namespace App\Modules\Organizations\Requests;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Organization::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'tasa_libre_riesgo_pct' => ['required', 'decimal:0,4', 'min:0', 'max:100'],
            'admin_nombre' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'plan_id' => 'plan',
            'tasa_libre_riesgo_pct' => 'tasa libre de riesgo',
            'admin_nombre' => 'nombre del administrador',
            'admin_email' => 'correo del administrador',
            'admin_password' => 'contraseña del administrador',
        ];
    }
}
