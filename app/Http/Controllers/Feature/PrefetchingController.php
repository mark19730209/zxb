<?php

namespace App\Http\Controllers\Feature;

use Inertia\Inertia;
use Inertia\Response;

class PrefetchingController
{
    public function linkPrefetch(): Response
    {
        return Inertia::render('Features/Prefetching/LinkPrefetch');
    }

    public function staleWhileRevalidate(): Response
    {
        return Inertia::render('Features/Prefetching/StaleWhileRevalidate');
    }

    public function manualPrefetch(): Response
    {
        return Inertia::render('Features/Prefetching/ManualPrefetch');
    }

    public function cacheManagement(): Response
    {
        return Inertia::render('Features/Prefetching/CacheManagement');
    }
}
