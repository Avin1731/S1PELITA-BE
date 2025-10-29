<?php

namespace App\Models\Files;

use Illuminate\Database\Eloquent\Model;
use App\Models\Submission;

class LaporanUtama extends Model
{
    //
    protected $table = 'laporan_utama';
    protected $fillable = [
        'submission_id',
        'path',
        'status',
        'catatan_admin',
    ];
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
