<?php

namespace App\Models\Pusdatin\Parsed;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pusdatin\PenilaianSLHD;

class PenilaianSLHD_Parsed extends Model
{
    protected $table = 'penilaian_slhd_parsed';
    protected $casts=[
        'error_messages' => 'array'
    ];
    protected $fillable = [
        'Total_Skor',
        'penilaian_slhd_id',
        'id_dinas',
        'nama_dinas',
        'Bab_1',
        'Jumlah_Pemanfaatan_Pelayanan_Laboratorium',
        'Daya_Dukung_dan_Daya_Tampung_Lingkungan_Hidup',
        'Kajian_Lingkungan_Hidup_Strategis',        
        'Keanekaragaman_Hayati',
        'Kualitas_Air',
        'Laut_Pesisir_dan_Pantai',
        'Kualitas_Udara',
        'Pengelolaan_Sampah_dan_Limbah',        
        'Lahan_dan_Hutan',
        'Perubahan_Iklim',      
        'Risiko_Bencana',
        'Penetapan_Isu_Prioritas',
        'Bab_3',
        'Bab_4',
        'Bab_5',
        'error_messages',
        'status'

        // Add other relevant fields here
    ];
    
    public function penilaianSLHD()
    {
        return $this->belongsTo(PenilaianSLHD::class, 'penilaian_slhd_id');
    }
}
