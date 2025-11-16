<?php

namespace App\Models\Pusdatin\Parsed;

use Illuminate\Database\Eloquent\Model;

class PenilaianPenghargaan_Parsed extends Model
{
  protected $table = 'penilaian_penghargaan_parsed';
  protected $fillable=[  
            'id_dinas' ,
            'nama_dinas',
            'Adipura_Jumlah_Wilayah',
            'Adipura_Skor_Max',
            'Adipura_Skor',
            'Adiwiyata_Jumlah_Sekolah',
            'Adiwiyata_Skor_Max',
            'Adiwiyata_Skor',
            'Proklim_Jumlah_Desa',
            'Proklim_Skor_Max',
            'Proklim_Skor',
            'Proper_Jumlah_Perusahaan',
            'Proper_Skor_Max',
            'Proper_Skor',
            'Kalpataru_Jumlah_Penerima',
            'Kalpataru_Skor_Max',
            'Kalpataru_Skor',
            'Adipura_Persentase',
            'Adiwiyata_Persentase',
            'Proklim_Persentase',
            'Proper_Persentase',
            'Kalpataru_Persentase',
            'Total_Skor',
            'error_messages',
            'status',
            'penilaian_penghargaan_id',
        ];

}
