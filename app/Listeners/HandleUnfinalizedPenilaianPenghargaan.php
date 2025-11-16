<?php

namespace App\Listeners;

use App\Events\PenilaianPenghargaanUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HandleUnfinalizedPenilaianPenghargaan
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
        //
    }
}
