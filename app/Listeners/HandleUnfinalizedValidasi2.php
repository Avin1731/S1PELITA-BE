<?php

namespace App\Listeners;

use App\Events\Validasi2Updated;
use App\Models\Pusdatin\Wawancara;
use App\Services\TahapanPenilaianService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleUnfinalizedValidasi2
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
    public function handle(Validasi2Updated $event): void
    {
        $validasi2 = $event->validasi2;
        
        // Jika status berubah dari finalized ke non-finalized, hapus data Wawancara untuk year terkait
        if ($validasi2->getOriginal('is_finalized') === true && $validasi2->is_finalized === false) {
            // Hapus semua Wawancara untuk year terkait (tidak ada FK cascade)
            Wawancara::where('year', $validasi2->year)->delete();

            // Update tahapan penilaian status (sudah include reset rekap di dalamnya)
            // $this->tahapanService->updateSetelahUnfinalize('validasi_2', $validasi2->year);
        }
    }
}
