<?php

namespace App\Http\Controllers\Pusdatin;

use App\Http\Controllers\Controller;
use App\Models\Pusdatin\PenilaianPenghargaan;
use App\Models\Pusdatin\Validasi1;
use App\Services\ValidasiService;
use Illuminate\Http\Request;

class Validasi_1_Controller extends Controller
{
    protected $validasiService;
    public function __construct(ValidasiService $validasiService)
    {
        $this->validasiService = $validasiService;
    }
    
    public function index(Request $request, $year)
    {
         $penilaian = PenilaianPenghargaan::where([
        'year' => $year,
        'status' => 'finalized'
    ])->first();

    if (!$penilaian) {
        return response()->json([
            'message' => 'Belum ada Penilaian Penghargaan yang sudah difinalisasi'
        ], 404);
    }
    $validasi1 = $penilaian->Validasi1()->first();

    if (!$validasi1) {
        return response()->json([
            'message' => 'Validasi 1 belum dibuat untuk penilaian ini'
        ], 404);
    }

    // Ambil hasil parsed validasi-1
    $data = $validasi1->Validasi1Parsed()->get();

    return response()->json($data, 200);
    }

    public function finalize(Request $request,$year){
        $validasi1=Validasi1::where('year',$year)->first();
        if(!$validasi1){
            return response()->json([
                'message'=>'Validasi 1 untuk tahun '.$year.' tidak ditemukan'
            ],404); 
        }
        $validasi1->update([
            'status'=>'finalized',
            'is_finalized'=>true,
            'finalized_at'=>now(),
            'finalized_by'=>$request->user()->id
        ]);
         
        $this->validasiService->CreateValidasi2($validasi1);
        return response()->json([
            'message'=>'Validasi 1 untuk tahun '.$year.' berhasil difinalisasi'
        ],200);
    }


    public function unfinalize(){}
    
}

 