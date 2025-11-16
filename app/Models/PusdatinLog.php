<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PusdatinLog extends Model
{

    protected $fillable = [
        'year',
        'submission_id',
        'stage',
        'activity_type',
        'actor_id',
        'document_type',
        'status',
        'catatan',
    ];
}
