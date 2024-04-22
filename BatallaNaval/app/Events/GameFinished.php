<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameFinished
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $gameId;

    /**
     * Create a new event instance.
     *
     * @param int $gameId El ID de la partida que ha finalizado
     * @return void
     */
    public function broadcastOn()
    {
        return new Channel('game-finished');
    }
    public function broadcastAs()
    {
        return 'game-finished';
    }
}
