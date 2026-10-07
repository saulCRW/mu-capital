<?php

namespace App\Modules\Assets\Requests;

use App\Modules\Assets\Models\Asset;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset
            ? $this->user()->can('update', $asset)
            : $this->user()->can('create', Asset::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'simbolo' => strtoupper(trim((string) $this->input('simbolo'))),
            'moneda' => strtoupper(trim((string) $this->input('moneda'))),
            'exchange' => $this->filled('exchange') ? strtoupper(trim($this->input('exchange'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'simbolo' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9.\-]+$/', $this->simboloUnico()],
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => ['required', Rule::in(Asset::TIPOS)],
            'sector' => ['nullable', 'string', 'max:100'],
            'pais' => ['nullable', 'string', 'max:100'],
            'moneda' => ['required', 'regex:/^[A-Z]{3}$/'],
            'exchange' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'simbolo.regex' => 'El símbolo solo puede tener letras, números, punto y guion.',
            'moneda.regex' => 'La moneda debe ser un código de 3 letras (ej. MXN, USD).',
        ];
    }

    /** El mismo símbolo no puede repetirse en el mismo exchange. */
    private function simboloUnico(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $existe = Asset::where('simbolo', $value)
                ->when($this->input('exchange'), fn ($q, $ex) => $q->where('exchange', $ex), fn ($q) => $q->whereNull('exchange'))
                ->when($this->route('asset'), fn ($q, $asset) => $q->whereKeyNot($asset->id))
                ->exists();

            if ($existe) {
                $fail('Ya existe un activo con ese símbolo en ese exchange.');
            }
        };
    }
}
