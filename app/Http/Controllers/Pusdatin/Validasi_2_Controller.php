<?php

namespace App\Http\Controllers\Pusdatin;

use App\Http\Controllers\Controller;
use App\Models\Pusdatin\Parsed\Validasi2Parsed;
use App\Models\Pusdatin\Validasi2;
use App\Services\ValidasiService;
use Illuminate\Http\Request;


class Validasi_2_Controller extends Controller
{
    protected $validasiService;
    public function __construct(ValidasiService $validasiService)
    {
        $this->validasiService = $validasiService;
    }
    
    public function index(Request $request, $year)
    {
        
        $validasi2=Validasi2::where(['year'=>$year,'status'=>'finalized'])->first();
        if(!$validasi2){
            return response()->json([
                'message'=>'Belum ada Validasi 2 yang sudah difinalisasi untuk tahun '.$year
            ],404);
        }
        $data=$validasi2->Validasi2Parsed()->get();
        return response()->json($data,200);

    }
    public function updateCheklist(Request $request,Validasi2Parsed $validasi2Parsed){
        $validatedData=$request->validate([
            'Kriteria_WTP'=>'required|boolean',
            'Kriteria_Kasus_Hukum'=>'required|boolean',
            'catatan'=>'nullable|string'
        ]);
        $validasi2Parsed->update($validatedData);
        return response()->json([
            'message'=>'Checklist Validasi 2 berhasil diperbarui',
            'data'=>$validasi2Parsed
        ],200);
    }
    
    public function finalize(Request $request,$year){
        $validasi2=Validasi2::where('year',$year)->first();
        if(!$validasi2){
            return response()->json([
                'message'=>'Validasi 2 untuk tahun '.$year.' tidak ditemukan'
            ],404); 
        }

        $validasi2->update([
            'status'=>'finalized',
            'is_finalized'=>true,
            'finalized_at'=>now(),
            'finalized_by'=>$request->user()->id
        ]);
        $this->validasiService->createLulusValidasi2($validasi2);
        return response()->json([
            'message'=>'Validasi 2 untuk tahun '.$year.' berhasil difinalisasi',
            'data'=>$validasi2
        ],200); 


    }
    public function ranked($year){
        $validasi2=Validasi2::where(['year'=>$year,'status'=>'finalized'])->first();
        if(!$validasi2){
            return response()->json([
                'message'=>'Belum ada Validasi 2 yang sudah difinalisasi untuk tahun '.$year
            ],404);
        }
        $data=$validasi2->Validasi2Parsed()
        ->where('status_validasi','lolos')
        ->get();
        $belumranked=[];
        foreach($data as $item){
            $rowtoinsert=[];
            $item->dinas; // Load data dinas terkait
        }

        return response()->json($data,200);

    }
}
