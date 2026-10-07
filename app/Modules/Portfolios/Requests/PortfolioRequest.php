<?php

namespace App\Modules\Portfolios\Requests;

use App\Modules\Portfolios\Models\Portfolio;
use Illuminate\Foundation\Http\FormRequest;

/** Crear: ruta clients/{client}/portfolios. Editar: ruta portfolios/{portfolio}. */
class PortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $portfolio = $this->route('portfolio');

        return $portfolio
            ? $this->user()->can('update', $portfolio)
            : $this->user()->can('create', [Portfolio::class, $this->route('client')]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['moneda_base' => strtoupper(trim((string) $this->input('moneda_base')))]);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'moneda_base' => ['required', 'regex:/^[A-Z]{3}$/'],
            'benchmark_id' => ['nullable', 'integer', 'exists:benchmarks,id'],
        ];
    }
}
