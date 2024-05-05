<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckExportStatus
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subject;
    public $filename;
    public $email;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($subject, $filename, $email)
    {
        $this->subject  = $subject;
        $this->filename = $filename;
        $this->email    = $email;

        logger('CheckExportStatus Event..');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['check-export-status'];
    }
}
