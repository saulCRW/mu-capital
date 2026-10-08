<?php

namespace App\Modules\Prices\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Captura manual del cierre de un activo (pantalla de Precios). */
class PriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->where(fn ($q) => $q->where('tipo', '!=', 'efectivo'))],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'cierre' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.required' => 'Elige el activo.',
            'asset_id.exists' => 'Ese activo no está en el catálogo.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'cierre.gt' => 'El cierre debe ser mayor que cero.',
            'cierre.decimal' => 'El cierre admite hasta 6 decimales.',
        ];
    }
}
