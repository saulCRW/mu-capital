<?php

namespace App\Modules\Organizations\Requests;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('organization'));
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'estado' => ['required', Rule::in(Organization::ESTADOS)],
            'tasa_libre_riesgo_pct' => ['required', 'decimal:0,4', 'min:0', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'plan_id' => 'plan',
            'tasa_libre_riesgo_pct' => 'tasa libre de riesgo',
        ];
    }
}
