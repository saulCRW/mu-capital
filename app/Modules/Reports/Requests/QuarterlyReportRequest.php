<?php

namespace App\Modules\Reports\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Trimestre del reporte (?anio=2026&trimestre=3). Por omisión, el último trimestre cerrado. */
class QuarterlyReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('portfolio'));
    }

    protected function prepareForValidation(): void
    {
        $ultimoCerrado = CarbonImmutable::today()->startOfQuarter()->subDay();

        $this->merge([
            'anio' => $this->input('anio', $ultimoCerrado->year),
            'trimestre' => $this->input('trimestre', $ultimoCerrado->quarter),
        ]);
    }

    public function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'between:2000,'.CarbonImmutable::today()->year],
            'trimestre' => ['required', 'integer', 'between:1,4'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $inicio = CarbonImmutable::create((int) $this->input('anio'), (int) $this->input('trimestre') * 3 - 2, 1);
                if ($inicio->isFuture()) {
                    $validator->errors()->add('trimestre', 'Ese trimestre todavía no empieza.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'anio.between' => 'El año debe estar entre 2000 y el actual.',
            'trimestre.between' => 'El trimestre debe ser del 1 al 4.',
        ];
    }
}
