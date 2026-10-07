<?php

namespace App\Modules\Organizations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Models\Plan;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Requests\StoreOrganizationRequest;
use App\Modules\Organizations\Requests\UpdateOrganizationRequest;
use App\Modules\Organizations\Services\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Panel del superadmin: alta y edición de despachos. */
class OrganizationController extends Controller
{
    public function __construct(private OrganizationService $service)
    {
    }

    public function index(): View
    {
        Gate::authorize('viewAny', Organization::class);

        $organizations = Organization::with('plan')
            ->withCount('users')
            ->orderBy('nombre')
            ->paginate(25);

        return view('superadmin.organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        Gate::authorize('create', Organization::class);

        return view('superadmin.organizations.create', ['plans' => Plan::orderBy('id')->get()]);
    }

    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        $organization = $this->service->create($request->validated());

        return redirect()->route('superadmin.organizations.index')
            ->with('status', "Despacho \"{$organization->nombre}\" creado.");
    }

    public function edit(Organization $organization): View
    {
        Gate::authorize('update', $organization);

        return view('superadmin.organizations.edit', [
            'organization' => $organization,
            'plans' => Plan::orderBy('id')->get(),
            'tasaPct' => $this->service->fractionToPct($organization->tasa_libre_riesgo),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->service->update($organization, $request->validated());

        return redirect()->route('superadmin.organizations.index')
            ->with('status', "Despacho \"{$organization->nombre}\" actualizado.");
    }
}
