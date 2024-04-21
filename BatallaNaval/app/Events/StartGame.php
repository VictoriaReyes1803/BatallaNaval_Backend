<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StartGame
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $game;

    public function __construct($game)
    {
        $this->game = $game;
    }
   
    public function broadcastWith()
    {
        return [
            'hola' => 'hola',
            'game' => $this->game,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('game-created');
    }
}
