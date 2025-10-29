<?php

namespace App\Models\Files;

use Illuminate\Database\Eloquent\Model;
use App\Models\Submission;

class RingkasanEksekutif extends Model
{
    //
    protected $fillable = [
        'submission_id',
        'path',
        'status',
        'catatan_admin',
    ];
    protected $table = 'ringkasan_eksekutif';
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
