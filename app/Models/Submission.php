<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Files\RingkasanEksekutif;
use App\Models\Files\LaporanUtama;
use App\Models\Files\TabelUtama;
use App\Models\Files\Iklh;
use App\Models\Files\Lampiran;

class Submission extends Model
{
    protected $fillable = [
        'id_dinas',
        'tahun',
        'status',
        'finalized_at',
        'catatan_admin',
    ];

    public function dinas()
    {
        return $this->belongsTo(Dinas::class, 'id_dinas');
    }
    public function ringkasanEksekutif()
{
    return $this->hasOne(RingkasanEksekutif::class);
}

    public function laporanUtama()
    {
        return $this->hasOne(LaporanUtama::class);
    }

    public function tabelUtama()
    {
        return $this->hasMany(TabelUtama::class);
    }

    public function iklh()
    {
        return $this->hasOne(Iklh::class);
    }
    
    public function lampiran()
    {
        return $this->hasOne(Lampiran::class);
    }
}
