<?php

namespace App\Modules\Users\Requests;

use App\Modules\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            // La regla "unique" consulta la tabla directamente: el correo es único en TODO el sistema.
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['required', Rule::in(User::ROLES_DESPACHO)],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
