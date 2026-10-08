<?php

namespace App\Modules\Transactions\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Alta de un dividendo anunciado en el calendario de dividendos. */
class ExpectedDividendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->where(fn ($q) => $q->where('tipo', '!=', 'efectivo'))],
            'fecha_ex' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:fecha_pago'],
            'fecha_pago' => ['required', 'date_format:Y-m-d'],
            'monto_por_titulo' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
            'nota' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.required' => 'Elige el activo.',
            'asset_id.exists' => 'Ese activo no está en el catálogo.',
            'fecha_ex.before_or_equal' => 'La fecha ex no puede ser posterior a la fecha de pago.',
            'fecha_pago.required' => 'Escribe la fecha de pago.',
            'monto_por_titulo.required' => 'Escribe el dividendo por título.',
            'monto_por_titulo.gt' => 'El dividendo por título debe ser mayor que cero.',
            'monto_por_titulo.decimal' => 'El dividendo por título admite hasta 6 decimales.',
        ];
    }
}
