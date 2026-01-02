<?php

namespace App\Listeners;

use App\Events\PenilaianSLHDUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Pusdatin\PenilaianPenghargaan;
use App\Models\Pusdatin\Wawancara;
use App\Services\TahapanPenilaianService;
use Illuminate\Support\Facades\Storage;

class HandleUnfinalizedPenilaianSLHD
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
    public function handle(PenilaianSLHDUpdated $event): void
    {
        $slhd = $event->penilaianSLHD;
        
        if ($slhd->getOriginal('status') === 'finalized' && $slhd->status !== 'finalized') {
            // Hapus file Template terkait
            $templatePath = "penilaian/template_penilaian_penghargaan_{$slhd->year}.xlsx"; 
            Storage::disk('templates')->delete($templatePath);

            // Hapus data penghargaan terkait (akan cascade delete Validasi1 & Validasi2 via DB)
            PenilaianPenghargaan::where('penilaian_slhd_ref_id', $slhd->id)->delete();
            
            // Hapus data Wawancara untuk tahun terkait (tidak ada FK cascade ke Validasi2)
            Wawancara::where('year', $slhd->year)->delete();

            // Update tahapan penilaian status (sudah include reset rekap di dalamnya)
            // $this->tahapanService->updateSetelahUnfinalize('penilaian_slhd', $slhd->year);
        }
    }
}
