<?php

namespace App\Modules\Prices\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Captura manual de un tipo de cambio (pantalla de Precios). */
class FxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    /** Los códigos de moneda se guardan en mayúsculas aunque se escriban en minúsculas. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'moneda_origen' => strtoupper((string) $this->input('moneda_origen')),
            'moneda_destino' => strtoupper((string) $this->input('moneda_destino')),
        ]);
    }

    public function rules(): array
    {
        return [
            'moneda_origen' => ['required', 'regex:/^[A-Z]{3}$/'],
            'moneda_destino' => ['required', 'regex:/^[A-Z]{3}$/', 'different:moneda_origen'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tasa' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
        ];
    }

    public function messages(): array
    {
        return [
            'moneda_origen.regex' => 'Usa un código de 3 letras (ej. USD).',
            'moneda_destino.regex' => 'Usa un código de 3 letras (ej. MXN).',
            'moneda_destino.different' => 'Las dos monedas deben ser distintas.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'tasa.gt' => 'El tipo de cambio debe ser mayor que cero.',
            'tasa.decimal' => 'El tipo de cambio admite hasta 6 decimales.',
        ];
    }
}
