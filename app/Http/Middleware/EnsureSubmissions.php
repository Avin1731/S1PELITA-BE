<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Submission;
class EnsureSubmissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $year = now()->year;
        $submission = Submission::firstOrCreate(
            ['id_dinas' => $user->dinas_id, 'tahun' => $year],
            ['status' => 'draft']
        );
        $request->merge(['submission' => $submission]);
        //filter
        // if ($submission->status == 'finalized'||$submission->status == 'approved') {
        //     return response()->json([
        //         'message' => 'Submission tahun ini sudah difinalisasi, tidak dapat diubah.'
        //     ], 403);
        // }
        return $next($request);
    }
}
