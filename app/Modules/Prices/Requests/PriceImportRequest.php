<?php

namespace App\Modules\Prices\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Archivo de precios o tipos de cambio a importar (CSV o Excel). */
class PriceImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperadmin();
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'max:2048', 'mimes:csv,txt,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Elige el archivo CSV o Excel.',
            'archivo.max' => 'El archivo no puede pasar de 2 MB.',
            'archivo.mimes' => 'El archivo debe ser CSV o Excel (.xlsx).',
        ];
    }
}
