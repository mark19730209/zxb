<?php

namespace App\Http\Controllers\Feature;

use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class StateController
{
    public function remember(): Response
    {
        return Inertia::render('Features/State/Remember');
    }

    public function flashData(): Response
    {
        return Inertia::render('Features/State/FlashData');
    }

    public function storeFlashData(): RedirectResponse
    {
        return Inertia::flash('message', 'This is a flash message from the server!')
            ->flash('type', 'success')
            ->back();
    }

    public function storeFlashDataError(): RedirectResponse
    {
        return Inertia::flash('message', 'Something went wrong!')
            ->flash('type', 'error')
            ->back();
    }

    public function storeFlashDataWarning(): RedirectResponse
    {
        return Inertia::flash('message', 'Please check your input.')
            ->flash('type', 'warning')
            ->back();
    }

    public function sharedProps(): Response
    {
        return Inertia::render('Features/State/SharedProps');
    }
}
