<?php

namespace App\Modules\Prices\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Captura manual del valor de un índice en una fecha (pantalla de Precios). */
class BenchmarkValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'benchmark_id' => ['required', 'integer', 'exists:benchmarks,id'],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'valor' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
        ];
    }

    public function messages(): array
    {
        return [
            'benchmark_id.required' => 'Elige el índice.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'valor.gt' => 'El valor debe ser mayor que cero.',
            'valor.decimal' => 'El valor admite hasta 6 decimales.',
        ];
    }
}
