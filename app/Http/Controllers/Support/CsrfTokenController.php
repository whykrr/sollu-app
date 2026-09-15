<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CsrfTokenController extends Controller
{
    /**
     * Return fresh CSRF token and authentication status.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token(),
            'authenticated' => $request->user() !== null,
        ]);
    }
}
