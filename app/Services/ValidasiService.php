<?php

namespace App\Services;

use App\Models\Files\Iklh;
use App\Models\Pusdatin\Parsed\Validasi1Parsed;
use App\Models\Pusdatin\Parsed\Validasi2Parsed;
use App\Models\Pusdatin\PenilaianPenghargaan;
use App\Models\Pusdatin\Validasi1;
use App\Models\Pusdatin\Validasi2;
use App\Models\Submission;

use function PHPUnit\Framework\isEmpty;

class ValidasiService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function CreateValidasi1(PenilaianPenghargaan $penilaianPenghargaan)
    {
        
        $rows=$penilaianPenghargaan->PenilaianPenghargaanParsed()->get();
        $rowToInsert=[];
        if(isEmpty($rows)){
            return response()->json([
                'message' => 'Data Penilaian belum selesai dibaca,mohon menunggu sebentar.',
            ], 404);
        }
        $validasi1=Validasi1::create([
            'penilaian_penghargaan_ref_id' => $penilaianPenghargaan->id,
            'year' => $penilaianPenghargaan->year,
            'status' => 'parsed_ok',
            'is_finalized' => false,
            'finalized_at' => null,
        ]);
            $ids = $rows->pluck('id_dinas')->unique()->values();

            // Ambil semua submission (1 query)
            $submissions = Submission::whereIn('id_dinas', $ids)
                ->where('year', $penilaianPenghargaan->year)
                ->get()
                ->keyBy('id_dinas');

            // Ambil semua IKLH (1 query)
            $iklhs = Iklh::whereIn('submission_id', $submissions->pluck('id'))
                ->get()
                ->keyBy('submission_id');
        
        foreach($rows as $row){
            $submission = $submissions[$row->id_dinas] ?? null;
            $iklh = $submission ? ($iklhs[$submission->id] ?? null) : null;

            $data=[];
            $data['validasi_1_id']=$validasi1->id;
            $data['id_dinas']=$row->id_dinas;
            $data['nama_dinas']=$row->nama_dinas ??'tidak diketahui';
            $data['Nilai_Penghargaan']=$row->Total_Skor;
            $data['Nilai_IKLH']=$iklh ? collect([$iklh->indeks_kualitas_air??0,$iklh->indeks_kualitas_udara??0,$iklh->indeks_kualitas_lahan??0,$iklh->indeks_kualitas_kehati??0,$iklh->indeks_kualitas_pesisir_laut])->filter(fn($value) => $value !==null)->avg(): null; // nanti diisi dari proses lain
            $data['Total_Skor']= ($data['Nilai_Penghargaan'] ?? 0) + ($data['Nilai_IKLH'] ?? 0)/2;
            $data['status']='parsed_ok';
            $data['status_result']= ($data['Total_Skor'] >= 60) ? 'lulus' : 'tidak_lulus';
            $rowToInsert[]=$data;
        
    }
    Validasi1Parsed::insert($rowToInsert);
    return $validasi1;
}
    public function CreateValidasi2(Validasi1 $validasi1){
        $validasi2 = Validasi2::create([
            'validasi_1_id'=>$validasi1->id,
            'year'=>$validasi1->year,
            'status'=>'parsed_ok',
            'is_finalized'=>false,
            'finalized_at'=>null,
        ]);
        $rowToInsert=[];
        $belumlulus=$validasi1->Validasi1Parsed()->get();
        $lulus=$belumlulus->filter(function($item){
            return ($item->status_result==='lulus');
        });
        foreach($lulus as $row){
            $data=[];
            $data['validasi_2_id']=$validasi2->id;
            $data['id_dinas']=$row->id_dinas;
            $data['nama_dinas']=$row->nama_dinas ??'tidak diketahui';
            // Checklist kriteria
            $data['Kriteria_WTP']=false; // nanti diisi dari proses lain
            $data['Kriteria_Kasus_Hukum']=false; // nanti diisi dari proses lain
            $data['status_validasi']='pending';
            $rowToInsert[]=$data;
        }
        Validasi2Parsed::insert($rowToInsert);
        return $validasi2;

    }
    public function createLulusValidasi2(Validasi2 $validasi2){

        Validasi2Parsed::where('validasi_2_id', $validasi2->id)
        ->where('Kriteria_WTP', true)
        ->where('Kriteria_Kasus_Hukum', true)
        ->update(['status_validasi' => 'lolos']);
    
    // Update yang tidak lolos (salah satu atau kedua kriteria false)
        Validasi2Parsed::where('validasi_2_id', $validasi2->id)
        ->where(function($query) {
            $query->where('Kriteria_WTP', false)
                  ->orWhere('Kriteria_Kasus_Hukum', false);
        })
        ->update(['status_validasi' => 'tidak_lolos']);
    
        return $validasi2;
    }

}
