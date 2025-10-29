<?php

namespace App\Http\Controllers\Pusdatin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\ReviewService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    protected $reviewService;
    public function __construct(ReviewService $reviewService){
        $this->reviewService = $reviewService;
    }

    public function index($year = null){
        $year = $year ?? now()->year();

        $submission = Submission::with('dinas')
        ->where(['status'=>'finalized','year'=> $year])->get();
        return response()->json($submission);
    }
    public function show(Submission $submission){
        $submission->load('ringkasanEksekutif','laporanUtama','tabelUtama','iklh');
        return response()->json($submission);
    }
    public function reviewDocument(Request $request, Submission $submission, $documentType){
        $submission->load('ringkasanEksekutif','laporanUtama','tabelUtama','iklh');
        $validated = $request->validate([
        'status' => 'required|in:accepted,rejected',
        'catatan_admin' => 'nullable|string|max:1000',
    ],[
        'status.required' => 'Status review harus diisi.',
        'status.in' => 'Status review harus berupa accepted atau rejected.',
        'catatan_admin.max' => 'Catatan admin maksimal 1000 karakter.',
    ]);

        $result=$this->reviewService->evaluateDocument($submission, $documentType, $validated);
        return response()->json(['message'=>'Document reviewed successfully.','document'=>$result]);

    }

    public function reviewDocumentStandAlone(Request $request, $documentType){
        // Implementasi untuk review dokumen standalone berdasarkan tipe dokumen
    }



    public function finalizeReview(Submission $submission){
        
        $submission->load('ringkasanEksekutif','laporanUtama','tabelUtama','iklh');

        $result=$this->reviewService->finalizeReview($submission);

        return response()->json(['message'=>'Review finalized successfully.','result'=> $result]);
    }
}
