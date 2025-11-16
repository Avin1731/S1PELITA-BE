<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocument
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$docType = null): Response
    {
        $docType=$docType??$request->route('type');
        $submission = $request->submission;

    if (!method_exists($submission, $docType)) {
        return response()->json([
            'message' => "Jenis dokumen '$docType' tidak dikenali di submission."
        ], 400);
    }

    
    $document = $submission->$docType()->first();
    

    if ($document && in_array($document->status, ['finalized', 'approved'])) {
        return response()->json([
            'message' => "Dokumen $docType sudah difinalisasi, tidak dapat diubah."
        ], 403);
    }

        return $next($request);
    }
}
