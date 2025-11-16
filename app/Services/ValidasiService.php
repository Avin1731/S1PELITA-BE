<?php

namespace App\Services;

use App\Models\Files\Iklh;
use App\Models\Pusdatin\PenilaianPenghargaan;
use App\Models\Pusdatin\Validasi1;
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
            $rowToInsert[]=$data;
        
    }
    Validasi1::insert($rowToInsert);
    return $validasi1;
}

}
