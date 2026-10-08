<?php

namespace App\Modules\Portfolios\Requests;

use App\Modules\Portfolios\Services\HoldingsTable;
use App\Modules\Transactions\Requests\TransactionFilterRequest;
use Illuminate\Validation\Rule;

/**
 * Parámetros de la página del portafolio (vienen en la URL): la pestaña
 * (?vista=resumen|holdings|transacciones), cómo se ordena y agrupa la tabla
 * de holdings, y los filtros de transacciones que ya validaba TransactionFilterRequest.
 */
class PortfolioShowRequest extends TransactionFilterRequest
{
    public const VISTAS = ['resumen' => 'Resumen', 'holdings' => 'Holdings', 'transacciones' => 'Transacciones'];

    public function rules(): array
    {
        return parent::rules() + [
            'vista' => ['nullable', Rule::in(array_keys(self::VISTAS))],
            'orden' => ['nullable', Rule::in(array_keys(HoldingsTable::ORDENES))],
            'dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'agrupar' => ['nullable', Rule::in(array_keys(HoldingsTable::AGRUPACIONES))],
            'vendidas' => ['nullable', 'boolean'],
        ];
    }

    public function vista(): string
    {
        return $this->validated('vista') ?? 'resumen';
    }

    /** @return array{orden: string, dir: string, agrupar: ?string, vendidas: bool} */
    public function holdings(): array
    {
        return [
            'orden' => $this->validated('orden') ?? 'valor',
            'dir' => $this->validated('dir') ?? 'desc',
            'agrupar' => $this->validated('agrupar'),
            'vendidas' => (bool) $this->validated('vendidas'),
        ];
    }

    /** @return array<string, string> solo los filtros de transacciones */
    public function filtros(): array
    {
        return array_filter(array_intersect_key($this->validated(), parent::rules()), fn ($v) => $v !== null);
    }
}
