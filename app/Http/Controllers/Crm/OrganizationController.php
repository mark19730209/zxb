<?php

namespace App\Http\Controllers\Crm;

use App\Http\Requests\Crm\UpdateOrganizationRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OrganizationController
{
    public function index(Request $request): Response
    {
        return Inertia::render('Organizations/Index', [
            'organizations' => OrganizationResource::collection(
                Organization::query()
                    ->withCount('contacts')
                    ->search($request->string('search'))
                    ->orderBy('name')
                    ->paginate(20)
            ),
            'filters' => [
                'search' => $request->string('search'),
            ],
        ]);
    }

    public function show(Organization $organization): Response
    {
        return Inertia::render('Organizations/Show', [
            'organization' => new OrganizationResource($organization->loadCount('contacts')),
            'contacts' => Inertia::scroll(
                ContactResource::collection(
                    $organization->contacts()
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->orderBy('id')
                        ->cursorPaginate(15)
                )
            ),
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->validated());

        return Inertia::flash('message', 'Organization updated.')->back();
    }
}
