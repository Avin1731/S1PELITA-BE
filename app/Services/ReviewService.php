<?php

namespace App\Services;

use App\Models\DocumentReview;
use Illuminate\Support\Facades\DB;
use app\Models\Files\TabelUtama;
use App\Models\Submission;
use Illuminate\Support\Facades\Auth;

class ReviewService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        
    }
    public function evaluateDocument($submission, $documentType, $data){
        //INI BASED SUBMISION ZIRRRR INGAT
        // Logic to evaluate the document based on type and data
        // This is a placeholder implementation
        // $modelmap = [
        //     'ringkasan_eksekutif' => $submission->ringkasanEksekutif,
        //     'laporan_utama' => $submission->laporanUtama,
        //     'tabel_utama' => $submission->tabelUtama,
        //     'iklh' => $submission->iklh,
        // ];
        //  if (!array_key_exists($documentType, $modelmap)) {
        // abort(400, 'Tipe dokumen tidak dikenali');
        //  }

        // $doc = $modelmap[$documentType];
        // if (!$doc) {
        //     abort(404, 'Dokumen tidak ditemukan untuk submission ini');
        // }
        //   DB::transaction(function () use ($documentType, $submission, $data, $modelmap,$doc) {
        //     if ($documentType === 'tabel_utama') {
        //         // Kalau tabel_utama → update semua row milik submission
        //         TabelUtama::where('submission_id', $submission->id)
        //             ->update([
        //                 'status' => $data['status'],
        //                 'catatan_admin' => $data['catatan_admin'] ?? null,
        //             ]);
        //     } else {
        //         // Kalau bukan tabel_utama → update 1 dokumen
        //         $doc->update([
        //             'status_review' => $data['status'],
        //             'catatan_admin' => $data['catatan_admin'] ?? null,
        //         ]);
                
        //     }
        // });
        // return $doc;
        //AOWKOWKOWKOW LUPA PAKE STAGE
        $latest= DocumentReview::where(['submission_id'=>$submission->id,'document_type'=>$documentType])->latest('version')->first();
        $version= $latest ? $latest->version +1 : 1;
        $review= DocumentReview::updateorCreate(
            [
                'submission_id'=>$submission->id,
                'document_type'=>$documentType,
            ],
            [
                'status_review'=>$data['status'],
                'catatan_admin'=>$data['catatan_admin'] ?? null,
                'reviewed_by'=> Auth::id(),
                'reviewed_at'=> now(),
                'version'=>$version,
            ]
        );
        return $review;

    }   
    public function evaluateDocumentStandAlone(){
        //KALAU INI TAR BASED TYPE DOCUMENT
    }
    public function evaluateAllDocuments(Submission $submission){
        // Logic to evaluate all documents in a submission
        // This is a placeholder implementation
        
    }
    public function finalizeReview($submission){
        // Logic to finalize the review of the submission
        // This is a placeholder implementation
        // $statuses =collect([
        //     optional($submission->ringkasanEksekutif)->status,
        //     optional($submission->laporanUtama)->status,
        //     optional($submission->iklh)->status,
        //     optional($submission->tabelUtamas->first())->status,
        // ]);
        // if ($statuses->contains('rejected')) {
        //     $finalStatus = 'rejected';
        // } elseif ($statuses->every(fn($status) => $status === 'accepted')) {
        //     $finalStatus = 'approved';
        // } elseif ($statuses->contains('uploaded')|| $statuses->contains(null)) {
        // // masih ada yang belum direview
        //     abort(400, 'Masih ada dokumen yang belum direview .');
        // }else{
        //     abort(400, 'Status dokumen tidak valid untuk finalisasi.');
        // }
        // $submission->update(['status' => $finalStatus]);
        // return ['submission_id' => $submission->id, 'final_status' => $finalStatus]; 
        // $reviews = DocumentReview::where('submission_id', $submission->id)->get();
        // if ($reviews->isEmpty()) {
        //     abort(400, 'Belum ada dokumen yang direview.');   
    }   

}
