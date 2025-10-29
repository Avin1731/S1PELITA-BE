<?php

namespace App\Models\Files;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Model;


class Iklh extends Model
{
    //
    protected $table = 'iklh';
    protected $fillable = [
        'submission_id',
        'indeks_kualitas_air',
        'indeks_kualitas_udara',
        'indeks_kualitas_lahan',
        'indeks_kualitas_kehati',
        'indeks_kualitas_pesisir_laut',
        'status',
        'catatan_admin',
    ];
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
