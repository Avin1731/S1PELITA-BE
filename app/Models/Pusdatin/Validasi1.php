<?php

namespace App\Models\Pusdatin;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pusdatin\Parsed\Validasi1Parsed;


class Validasi1 extends Model
{
    protected $table = 'validasi_1';
    protected $fillable = [
        // Define fillable attributes here
        'penilaian_penghargaan_ref_id',
        'year',
        'status',
        'is_finalized',
        'finalized_at',
        'catatan',
        'uploaded_by',
        'finalized_by',
        
    ];
    public function penilaianPenghargaan()
    {
        return $this->belongsTo(PenilaianPenghargaan::class, 'penilaian_penghargaan_ref_id');
    }
    public function Validasi1Parsed()
    {
        return $this->hasMany(Validasi1Parsed::class, 'validasi_1_id');
    }   
}
