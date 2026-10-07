<?php

namespace App\Modules\Transactions\Requests;

use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crear: ruta portfolios/{portfolio}/transactions. Editar: ruta transactions/{transaction}.
 * Las reglas de negocio (no vender de más, no gastar efectivo inexistente)
 * se validan después, en TransactionService, contra todo el libro.
 */
class TransactionRequest extends FormRequest
{
    /** Número positivo con hasta 6 decimales, sin signos ni notación científica. */
    private const NUMERO = 'regex:/^\d+(\.\d{1,6})?$/';

    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction
            ? $this->user()->can('update', $transaction)
            : $this->user()->can('create', [Transaction::class, $this->route('portfolio')]);
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('moneda')) {
            $this->merge(['moneda' => strtoupper(trim($this->input('moneda')))]);
        }
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(Transaction::TIPOS)],
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'asset_id' => [
                'nullable',
                'integer',
                'exists:assets,id',
                Rule::requiredIf(fn () => in_array($this->input('tipo'), Transaction::REQUIEREN_ACTIVO, true)),
                Rule::prohibitedIf(fn () => in_array($this->input('tipo'), Transaction::SIN_ACTIVO, true)),
            ],
            'cantidad' => ['required', 'numeric', self::NUMERO, 'gt:0'],
            'precio' => [
                Rule::requiredIf(fn () => in_array($this->input('tipo'), Transaction::OPERACIONES, true)),
                'nullable', 'numeric', self::NUMERO, 'gt:0',
            ],
            'comision' => ['nullable', 'numeric', self::NUMERO],
            'moneda' => ['nullable', 'regex:/^[A-Z]{3}$/'],
            'tipo_cambio' => ['nullable', 'numeric', self::NUMERO, 'gt:0'],
            'nota' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'asset_id.required' => 'Este tipo de transacción requiere un activo.',
            'asset_id.prohibited' => 'Los depósitos y retiros no llevan activo.',
            'precio.required' => 'Las compras y ventas requieren precio.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'regex' => ':attribute debe ser un número positivo con máximo 6 decimales.',
            'moneda.regex' => 'La moneda debe ser un código de 3 letras (ej. MXN, USD).',
        ];
    }
}
