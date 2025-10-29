<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    protected function redirectTo($request)
    {
        // Kalau API request, jangan redirect, kirim JSON
        if (!$request->expectsJson()) {
            return null; // biar gak redirect ke /login
        }

        abort(response()->json([
            'message' => 'Unauthenticated.'
        ], 401));
    }
}
