<?php

namespace App\Models\Files;

use Illuminate\Database\Eloquent\Model;
use App\Models\Submission;

class Lampiran extends Model
{
    protected $fillable = [
        'submission_id',
        'path',
        'status',
        'catatan_admin',
    ];
    
    protected $table = 'lampiran';
    
    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
    
    public function finalize()
    {
        app('App\Services\DocumentFinalizer')->finalize($this, 'lampiran');    
    }
}
