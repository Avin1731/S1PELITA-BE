<?php

namespace App\Events;

use App\Models\Pusdatin\PenilaianSLHD;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PenilaianSLHDUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public PenilaianSLHD $penilaianSLHD;
    /**
     * Create a new event instance.
     */
    public function __construct(PenilaianSLHD $penilaianSLHD)
    {
        $this->penilaianSLHD = $penilaianSLHD;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
