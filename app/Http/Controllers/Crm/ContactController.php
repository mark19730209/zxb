<?php

namespace App\Http\Controllers\Crm;

use App\Http\Requests\Crm\StoreContactRequest;
use App\Http\Requests\Crm\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\OrganizationResource;
use App\Models\Contact;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ContactController
{
    public function index(Request $request): Response
    {
        return Inertia::render('Contacts/Index', [
            'contacts' => Inertia::scroll(
                ContactResource::collection(
                    Contact::query()
                        ->with('organization')
                        ->search($request->string('search'))
                        ->when($request->boolean('favorite'), fn (Builder $query) => $query->where('is_favorite', true))
                        ->orderBy('first_name')
                        ->orderBy('last_name')
                        ->orderBy('id')
                        ->cursorPaginate(15)
                )
            ),
            'filters' => [
                'search' => $request->string('search'),
                'favorite' => $request->boolean('favorite'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Contacts/Create', [
            'organizations' => OrganizationResource::collection(
                Organization::query()->orderBy('name')->get()
            ),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $contact = Contact::create($request->validated());

        Inertia::flash('message', 'Contact created.');

        return redirect()->route('contacts.show', $contact);
    }

    public function show(Contact $contact): Response
    {
        $contact->load('organization');

        return Inertia::render('Contacts/Show', [
            'contact' => new ContactResource($contact),
            'notes' => Inertia::defer(fn () => NoteResource::collection(
                $contact->notes()->with('user')->latest()->get()
            )),
        ]);
    }

    public function edit(Contact $contact): Response
    {
        $contact->load('organization');

        return Inertia::render('Contacts/Edit', [
            'contact' => new ContactResource($contact),
            'organizations' => OrganizationResource::collection(
                Organization::query()->orderBy('name')->get()
            ),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $contact->update($request->validated());

        Inertia::flash('message', 'Contact updated.');

        return redirect()->route('contacts.show', $contact);
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        Inertia::flash('message', 'Contact deleted.');

        return redirect()->route('contacts.index');
    }

    public function favorite(Contact $contact): RedirectResponse
    {
        $contact->update(['is_favorite' => ! $contact->is_favorite]);

        return Inertia::flash('message', $contact->is_favorite ? 'Added to favorites.' : 'Removed from favorites.')->back();
    }
}
