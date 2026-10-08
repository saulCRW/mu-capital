<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Código del doble factor: 6 dígitos de la app o, al iniciar sesión, un código de recuperación. */
class DosFactoresCodigoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'Escribe el código.',
        ];
    }
}
