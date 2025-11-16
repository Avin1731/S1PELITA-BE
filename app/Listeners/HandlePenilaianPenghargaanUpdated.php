<?php

namespace App\Listeners;

use App\Events\PenilaianPenghargaanUpdated;
use App\Models\Pusdatin\Validasi1;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandlePenilaianPenghargaanUpdated
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
    public function handle(PenilaianPenghargaanUpdated $event): void
    {
        $penillaianPenghargaan = $event->penilaianPenghargaan;
                 if ($penillaianPenghargaan->getOriginal('status') === 'finalized' && $penillaianPenghargaan->status !== 'finalized'){
                Validasi1::where('penilaian_penghargaan_id', $penillaianPenghargaan->id)->delete();
        }
    }  
}
