<?php

namespace App\Listeners;

use App\Events\Validasi1Updated;
use App\Models\Pusdatin\Validasi2;
use App\Models\Pusdatin\Wawancara;
use App\Services\TahapanPenilaianService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleUnfinalizedValidasi1
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
    public function handle(Validasi1Updated $event): void
    {
        $validasi1 = $event->validasi1;
        
        // Jika status berubah dari finalized ke non-finalized, hapus data Validasi2
        if ($validasi1->getOriginal('is_finalized') === true && $validasi1->is_finalized === false) {
            // Hapus Validasi2 terkait
            Validasi2::where('validasi_1_id', $validasi1->id)->delete();
            
            // Hapus Wawancara untuk year terkait (tidak ada FK cascade)
            Wawancara::where('year', $validasi1->year)->delete();

            // Update tahapan penilaian status (sudah include reset rekap di dalamnya)
            // $this->tahapanService->updateSetelahUnfinalize('validasi_1', $validasi1->year);
        }
    }
}
