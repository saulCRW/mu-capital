<?php

namespace App\Modules\Rebalancing\Requests;

use App\Modules\Analytics\Services\AllocationCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Asignación objetivo de una dimensión: objetivos[clave] = porcentaje.
 * Los porcentajes deben sumar 100 (o todos en 0/vacíos para borrar el objetivo).
 * Un grupo nuevo se agrega con nueva_clave + nuevo_porcentaje.
 */
class TargetAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('portfolio'));
    }

    protected function prepareForValidation(): void
    {
        $objetivos = (array) $this->input('objetivos', []);
        $nueva = trim((string) $this->input('nueva_clave'));
        if ($nueva !== '' && filled($this->input('nuevo_porcentaje'))) {
            $objetivos[$nueva] = $this->input('nuevo_porcentaje');
        }

        $this->merge(['objetivos' => array_map(fn ($v) => $v === null || $v === '' ? '0' : $v, $objetivos)]);
    }

    public function rules(): array
    {
        return [
            'dimension' => ['required', Rule::in(AllocationCalculator::DIMENSIONES)],
            'objetivos' => ['array'],
            'objetivos.*' => ['numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,4})?$/'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                foreach (array_keys($this->input('objetivos')) as $clave) {
                    if (mb_strlen((string) $clave) > 100) {
                        $validator->errors()->add('objetivos', 'El nombre de un grupo no puede pasar de 100 caracteres.');
                    }
                }
                $suma = array_sum(array_map('floatval', $this->input('objetivos')));
                if ($suma > 0 && abs($suma - 100) > 0.0001) {
                    $validator->errors()->add('objetivos', 'Los porcentajes deben sumar 100 % (suman '.rtrim(rtrim(number_format($suma, 4, '.', ','), '0'), '.').' %).');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'objetivos.*.between' => 'Cada porcentaje debe estar entre 0 y 100.',
            'objetivos.*.regex' => 'Cada porcentaje admite hasta 4 decimales.',
            'objetivos.*.numeric' => 'Cada porcentaje debe ser un número.',
        ];
    }
}
