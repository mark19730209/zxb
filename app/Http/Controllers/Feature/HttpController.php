<?php

namespace App\Http\Controllers\Feature;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HttpController
{
    public function useHttp(): Response
    {
        return Inertia::render('Features/Http/UseHttp');
    }

    public function useHttpApi(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Hello, '.$request->string('name', 'World').'!',
            'timestamp' => now()->toISOString(),
        ]);
    }
}
