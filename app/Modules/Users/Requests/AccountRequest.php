<?php

namespace App\Modules\Users\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            // Opcional: vacía = se conserva la actual. Cambiarla pide la actual.
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
            'password_actual' => ['required_with:password', 'nullable', 'current_password'],
            'tasa_libre_riesgo' => [Rule::excludeIf($this->user()->isSuperadmin()), 'required', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,4})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required_with' => 'Escribe tu contraseña actual para cambiarla.',
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'tasa_libre_riesgo.between' => 'La tasa debe estar entre 0 y 100 %.',
        ];
    }
}
