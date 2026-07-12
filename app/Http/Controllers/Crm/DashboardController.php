<?php

namespace App\Http\Controllers\Crm;

use App\Http\Resources\NoteResource;
use App\Models\Contact;
use App\Models\Note;
use App\Models\Organization;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(): Response
    {
        return Inertia::render('Crm/Dashboard', [
            'totalContacts' => Inertia::defer(fn () => Contact::count()),
            'totalOrganizations' => Inertia::defer(fn () => Organization::count()),
            'recentNotesCount' => Inertia::defer(fn () => Note::where('created_at', '>=', now()->subDays(7))->count()),
            'recentActivity' => NoteResource::collection(
                Note::with(['contact', 'user'])
                    ->latest()
                    ->limit(10)
                    ->get()
            ),
        ]);
    }
}
