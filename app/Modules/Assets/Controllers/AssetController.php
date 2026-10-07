<?php

namespace App\Modules\Assets\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assets\Models\Asset;
use App\Modules\Assets\Requests\AssetRequest;
use App\Modules\Assets\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function __construct(private AssetService $service)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asset::class);

        $busqueda = trim((string) $request->query('q')) ?: null;
        $assets = $this->service->buscar($busqueda)->paginate(50)->withQueryString();

        return view('assets.index', compact('assets', 'busqueda'));
    }

    public function create(): View
    {
        Gate::authorize('create', Asset::class);

        return view('assets.create');
    }

    public function store(AssetRequest $request): RedirectResponse
    {
        $asset = $this->service->create($request->validated());

        return redirect()->route('assets.index')->with('status', "Activo {$asset->simbolo} creado.");
    }

    public function edit(Asset $asset): View
    {
        Gate::authorize('update', $asset);

        return view('assets.edit', compact('asset'));
    }

    public function update(AssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->service->update($asset, $request->validated());

        return redirect()->route('assets.index')->with('status', "Activo {$asset->simbolo} actualizado.");
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        Gate::authorize('delete', $asset);

        $this->service->delete($asset);

        return redirect()->route('assets.index')->with('status', "Activo {$asset->simbolo} eliminado.");
    }
}
