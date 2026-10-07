<?php

namespace App\Modules\Assets\Services;

use App\Modules\Assets\Models\Asset;
use App\Modules\Organizations\Models\Scopes\OrganizationScope;
use App\Modules\Transactions\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AssetService
{
    public function buscar(?string $busqueda): Builder
    {
        return Asset::query()
            ->when($busqueda, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('simbolo', 'like', '%'.$busqueda.'%')
                ->orWhere('nombre', 'like', '%'.$busqueda.'%')))
            ->orderBy('simbolo');
    }

    public function create(array $data): Asset
    {
        return Asset::create($data);
    }

    public function update(Asset $asset, array $data): Asset
    {
        if ($data['moneda'] !== $asset->moneda && $this->tieneTransacciones($asset)) {
            throw ValidationException::withMessages([
                'moneda' => 'No se puede cambiar la moneda de un activo que ya se usa en transacciones.',
            ]);
        }

        $asset->update($data);

        return $asset;
    }

    public function delete(Asset $asset): void
    {
        if ($this->tieneTransacciones($asset) || $asset->prices()->exists()) {
            throw ValidationException::withMessages([
                'general' => "{$asset->simbolo} tiene transacciones o precios registrados; no se puede eliminar.",
            ]);
        }

        $asset->delete();
    }

    /** Busca en TODOS los despachos: el activo es compartido. */
    private function tieneTransacciones(Asset $asset): bool
    {
        return Transaction::withoutGlobalScope(OrganizationScope::class)
            ->where('asset_id', $asset->id)
            ->exists();
    }
}
