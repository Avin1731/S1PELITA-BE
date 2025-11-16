<?php

namespace App\Services;
use App\Models\PusdatinLog;
class LogService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function log(array $data)
    {
        return PusdatinLog::create([
            'submission_id' => $data['submission_id']?? null,
            'year' => $data['year'] ,
            'actor_id' => $data['actor_id'],
            'stage' => $data['stage'],
            'activity_type' => $data['activity_type'],
            'document_type' => $data['document_type']??null,
            'status' => $data['status']?? null,
            'catatan' => $data['catatan'] ?? null,
          
        ]);

    }
}

