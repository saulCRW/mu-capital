<?php

namespace App\Modules\Prices\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Alta de un índice de referencia (benchmark) en la pantalla de Precios. */
class BenchmarkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100', 'unique:benchmarks,nombre'],
            'simbolo_proveedor' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del índice.',
            'nombre.unique' => 'Ya existe un índice con ese nombre.',
        ];
    }
}
