<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Quitar el doble factor de la propia cuenta pide la contraseña actual y un código vigente. */
class DesactivarDosFactoresRequest extends FormRequest
{
    /** Errores aparte para no mostrarlos en el formulario de cambiar contraseña de la misma página. */
    protected $errorBag = 'dosFactores';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual' => ['required', 'current_password'],
            'codigo' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required' => 'Escribe tu contraseña actual.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'codigo.required' => 'Escribe el código de tu app.',
        ];
    }
}
