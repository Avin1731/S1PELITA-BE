<?php

namespace App\Listeners;

use App\Events\PenilaianSLHDUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pusdatin\PenilaianPenghargaan;
use Illuminate\Support\Facades\Storage;
class HandleUnfinalizedPenilaianSLHD
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PenilaianSLHDUpdated $event): void
    {
        $slhd = $event->penilaianSLHD;
         if ($slhd->getOriginal('status') === 'finalized' && $slhd->status !== 'finalized') {
            // hapus file Template terkait
            $templatePath = "penilaian/template_penilaian_penghargaan_{$slhd->year}.xlsx"; 
            Storage::disk('templates')->delete($templatePath);

            // hapus data penghargaan terkait
            PenilaianPenghargaan::where('penilaian_slhd_ref_id', $slhd->id)->delete();
            
        }
    }
}
