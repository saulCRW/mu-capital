<?php

namespace App\Modules\Clients\Requests;

use App\Modules\Clients\Models\Client;
use App\Modules\Users\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Sirve para crear (sin {client} en la ruta) y para editar. */
class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        $client = $this->route('client');

        return $client
            ? $this->user()->can('update', $client)
            : $this->user()->can('create', Client::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:255'],
            'perfil_riesgo' => ['required', Rule::in(Client::PERFILES)],
            // Solo el admin elige asesor; debe ser admin o asesor de su mismo despacho.
            'asesor_id' => [
                Rule::requiredIf(fn () => $this->user()->isAdmin()),
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('organization_id', $this->user()->organization_id)
                    ->whereIn('rol', [User::ROL_ADMIN, User::ROL_ASESOR]),
            ],
        ];
    }
}
