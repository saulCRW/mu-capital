<?php

namespace App\Modules\Imports\Requests;

use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Transaction::class, $this->route('portfolio')]);
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'max:2048', 'mimes:csv,txt'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Elige el archivo CSV.',
            'archivo.max' => 'El archivo no puede pasar de 2 MB.',
            'archivo.mimes' => 'El archivo debe ser CSV. En Excel: Archivo → Guardar como → CSV UTF-8.',
        ];
    }
}
