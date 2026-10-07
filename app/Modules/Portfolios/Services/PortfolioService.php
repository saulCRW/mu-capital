<?php

namespace App\Modules\Portfolios\Services;

use App\Modules\Clients\Models\Client;
use App\Modules\Portfolios\Models\Portfolio;
use Illuminate\Validation\ValidationException;

class PortfolioService
{
    /** @param  array{nombre:string, moneda_base:string, benchmark_id:?int}  $data */
    public function create(Client $client, array $data): Portfolio
    {
        return Portfolio::create($data + [
            'organization_id' => $client->organization_id,
            'client_id' => $client->id,
        ]);
    }

    /** @param  array{nombre:string, moneda_base:string, benchmark_id:?int}  $data */
    public function update(Portfolio $portfolio, array $data): Portfolio
    {
        // Cambiar la moneda base invalidaría el tipo de cambio de todas las transacciones ya capturadas.
        if ($data['moneda_base'] !== $portfolio->moneda_base && $portfolio->transactions()->exists()) {
            throw ValidationException::withMessages([
                'moneda_base' => 'No se puede cambiar la moneda base de un portafolio que ya tiene transacciones.',
            ]);
        }

        $portfolio->update($data);

        return $portfolio;
    }

    public function delete(Portfolio $portfolio): void
    {
        if ($portfolio->transactions()->exists()) {
            throw ValidationException::withMessages([
                'general' => 'Este portafolio tiene transacciones. Elimínalas antes de eliminar el portafolio.',
            ]);
        }

        $portfolio->delete();
    }
}
