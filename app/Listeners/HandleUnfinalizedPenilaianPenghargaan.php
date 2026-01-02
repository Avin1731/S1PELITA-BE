<?php

namespace App\Listeners;

use App\Events\PenilaianPenghargaanUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pusdatin\Validasi1;
use App\Models\Pusdatin\Wawancara;
use App\Services\TahapanPenilaianService;

class HandleUnfinalizedPenilaianPenghargaan
{
    protected $tahapanService;

    /**
     * Create the event listener.
     */
    public function __construct(TahapanPenilaianService $tahapanService)
    {
        $this->tahapanService = $tahapanService;
    }

    /**
     * Handle the event.
     */
    public function handle(PenilaianPenghargaanUpdated $event): void
    {
        $penghargaan = $event->penilaianPenghargaan;
        
        // Jika status berubah dari finalized ke non-finalized
        if ($penghargaan->getOriginal('status') === 'finalized' && $penghargaan->status !== 'finalized') {
            // Hapus data Validasi1 & Validasi2 terkait (cascade akan handle Validasi2)
            Validasi1::where('penilaian_penghargaan_id', $penghargaan->id)->delete();
            
            // Hapus Wawancara untuk year terkait
            Wawancara::where('year', $penghargaan->year)->delete();

            // Update tahapan penilaian status (sudah include reset rekap di dalamnya)
            // $this->tahapanService->updateSetelahUnfinalize('penilaian_penghargaan', $penghargaan->year);
        }
    }
}
