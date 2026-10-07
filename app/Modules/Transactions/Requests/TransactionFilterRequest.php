<?php

namespace App\Modules\Transactions\Requests;

use App\Modules\Transactions\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filtros de la tabla de transacciones (vienen en la URL: ?tipo=COMPRA&desde=...). */
class TransactionFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('portfolio'));
    }

    public function rules(): array
    {
        return [
            'tipo' => ['nullable', Rule::in(Transaction::TIPOS)],
            'asset_id' => ['nullable', 'integer'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
