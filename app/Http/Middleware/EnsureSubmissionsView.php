<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Submission;

class EnsureSubmissionsView
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $year = $request->query('tahun') ?? $request->input('tahun') ?? now()->year;
        $submission = Submission::where([
            'id_dinas' => $user->id_dinas,
            'tahun' => $year,
        ])->first();

        if (!$submission) {
            return response()->json([
                'message' => 'Belum ada submission untuk tahun ini.'
            ], 404);
        }

        $request->merge(['submission' => $submission]);
        //filter
       
        return $next($request);
    }
}
