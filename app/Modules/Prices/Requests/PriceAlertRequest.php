<?php

namespace App\Modules\Prices\Requests;

use App\Modules\Prices\Models\PriceAlert;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Alta de una alerta de precio en Mercado. */
class PriceAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->where(fn ($q) => $q->where('tipo', '!=', 'efectivo'))],
            'condicion' => ['required', Rule::in(array_keys(PriceAlert::CONDICIONES))],
            'precio' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            if (PriceAlert::de($this->user()->id)->activas()->count() >= PriceAlert::MAX_ACTIVAS) {
                $validator->errors()->add('alerta', 'Tienes '.PriceAlert::MAX_ACTIVAS.' alertas activas; borra alguna para crear otra.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'asset_id.required' => 'Elige el activo.',
            'asset_id.exists' => 'Ese activo no está en el catálogo.',
            'precio.required' => 'Escribe el precio de la alerta.',
            'precio.gt' => 'El precio debe ser mayor que cero.',
            'precio.decimal' => 'El precio admite hasta 6 decimales.',
        ];
    }
}
