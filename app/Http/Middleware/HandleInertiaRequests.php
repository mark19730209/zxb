<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'message' => function () use ($request) {
                    // 自动捕获 session 中常见的 key
                    if ($msg = $request->session()->get('message')) return $msg;
                    if ($success = $request->session()->get('success')) {
                        return is_array($success) ? $success : ['title' => $success, 'type' => 'success'];
                    }
                    if ($error = $request->session()->get('error')) {
                        return is_array($error) ? $error : ['title' => $error, 'type' => 'error'];
                    }
                    return null;
                },
                'dispatched_url' => $request->session()->get('dispatched_url'),
            ],
        ];
    }
}
