<?php

namespace App\Http\Controllers\Feature;

use Inertia\Inertia;
use Inertia\Response;

class LayoutController
{
    public function persistentLayouts(): Response
    {
        return Inertia::render('Features/Layouts/PersistentLayouts');
    }

    public function persistentLayoutsPageTwo(): Response
    {
        return Inertia::render('Features/Layouts/PersistentLayoutsPageTwo');
    }

    public function nestedLayouts(): Response
    {
        return Inertia::render('Features/Layouts/NestedLayouts');
    }

    public function head(): Response
    {
        return Inertia::render('Features/Layouts/Head');
    }

    public function layoutProps(): Response
    {
        return Inertia::render('Features/Layouts/LayoutProps');
    }
}
