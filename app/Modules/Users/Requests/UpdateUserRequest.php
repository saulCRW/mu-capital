<?php

namespace App\Modules\Users\Requests;

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'rol' => ['required', Rule::in(User::ROLES_DESPACHO)],
            // Opcional al editar: si se deja vacío, se conserva la contraseña actual.
            'password' => ['nullable', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
