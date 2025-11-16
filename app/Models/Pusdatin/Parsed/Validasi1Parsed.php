<?php

namespace App\Models\Pusdatin\Parsed;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pusdatin\Validasi1;      
class Validasi1Parsed extends Model
{
    protected $table = 'validasi_1_parseds';
    protected $fillable = [
        'validasi_1_id',
        'id_dinas',
        'nama_dinas',
        'Total_Skor',
        'Nilai_IKLH',
        'Nilai_Penghargaan',
        'status',
        'status_result',
        'error_messages',
    ];
    public function validasi_1()
    {
        return $this->belongsTo(Validasi1::class, 'validasi_1_id');
    }
}