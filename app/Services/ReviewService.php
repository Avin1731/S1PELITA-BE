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
    protected $map = [
        'ringkasanEksekutif' => 'ringkasan_eksekutif',
        'laporanUtama' => 'laporan_utama',
        'lampiran' => 'lampiran',
        'iklh' => 'iklh',
    ];
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;    
    }
    public function evaluateDocument(Submission $submission, $documentType, $data,$user){
        return DB::transaction(function () use ($submission, $documentType, $data,$user) {
            $document = $submission->{$documentType};
            if (!$document) {
                throw new \Exception("Dokumen $documentType tidak ditemukan untuk direview.");
            }
            // Validasi: dokumen harus sudah finalized untuk bisa direview
            if ($document->status == 'draft') {
                throw new \Exception("Dokumen $documentType harus difinalisasi terlebih dahulu sebelum dapat direview. Status saat ini: {$document->status}");
            }
            // Validasi: dokumen yang sudah approved tidak bisa direview ulang
            if ($document->status === 'approved') {
                throw new \Exception("Dokumen $documentType sudah direview, tidak dapat direview ulang.");
            }   
            // Update status dan catatan admin pada dokumen
            $document->update([
                'status' => $data['status'],
                'catatan_admin' => $data['catatan_admin'] ?? null,
            ]);

            // Simpan catatan review

            $this->logService->log([
                'submission_id' => $submission->id,
                'actor_id' => $user,
                'year' => (int) now()->year,           // <-- pastikan inte,
                'stage' => 'review',
                'activity_type' => $data['status'] === 'approved' ? 'approve' : 'reject',
                'document_type' => $this->map[$documentType],
                'status' => $data['status'],
                'catatan' => $data['catatan_admin'] ?? null,
            ]);
            return $document;

        }
    );

   
}
}