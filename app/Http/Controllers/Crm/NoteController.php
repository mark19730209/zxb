<?php

namespace App\Http\Controllers\Crm;

use App\Http\Requests\Crm\StoreNoteRequest;
use App\Models\Contact;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NoteController
{
    public function store(StoreNoteRequest $request, Contact $contact): RedirectResponse
    {
        $contact->notes()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return Inertia::flash('message', 'Note added.')->back();
    }
}
