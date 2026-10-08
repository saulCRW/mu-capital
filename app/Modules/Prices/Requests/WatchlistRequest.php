<?php

namespace App\Modules\Prices\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WatchlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', Rule::exists('assets', 'id')->where(fn ($q) => $q->where('tipo', '!=', 'efectivo'))],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.required' => 'Elige el activo que quieres seguir.',
            'asset_id.exists' => 'Ese activo no está en el catálogo.',
        ];
    }
}
