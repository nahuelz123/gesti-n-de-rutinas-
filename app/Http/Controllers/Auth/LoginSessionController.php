<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginSessionController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // Restoring a remembered user rotates the session and CSRF token. Do it before reading the token.
        $request->user();

        return response()->json(['token' => $request->session()->token()])
            ->header('Cache-Control', 'private, no-store');
    }
}
