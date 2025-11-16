<?php

namespace App\Services;

use App\Models\PusdatinLog;
use Illuminate\Support\Facades\DB;
use app\Models\Files\TabelUtama;
use App\Models\Submission;
use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\Auth;

class ReviewService
{
    /**
     * Create a new class instance.
     */
    protected $logService;
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;    
    }
    public function evaluateDocument(Submission $submission, $documentType, $data){
        return DB::transaction(function () use ($submission, $documentType, $data) {
            $document = $submission->{$documentType};
            if (!$document) {
                throw new \Exception("Dokumen $documentType tidak ditemukan untuk direview.");
            }
            if ($document->status === 'finalized' || $document->status === 'approved') {
                throw new \Exception("Dokumen $documentType sudah difinalisasi atau disetujui, tidak dapat direview ulang.");
            }   
            // Update status dan catatan admin pada dokumen
            $document->update([
                'status' => $data['status'],
                'catatan_admin' => $data['catatan_admin'] ?? null,
            ]);

            // Simpan catatan review

            $this->logService->log([
                'submission_id' => $submission->id,
                'actor_id' => Auth::id(),
                'year' => now()->year(),
                'stage' => 'review',
                'activity_type' => $data['status'] === 'approved' ? 'approve' : 'reject',
                'document_type' => $documentType,
                'status' => $data['status'],
                'catatan' => $data['catatan_admin'] ?? null,
            ]);
            return $document;

        }
    );

   
}
}