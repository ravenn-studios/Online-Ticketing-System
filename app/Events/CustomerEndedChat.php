<?php

namespace App\Events;

use Illuminate\Support\Facades\Auth;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Chat;
use App\TicketStatus;
use App\AgentChatLog;

class CustomerEndedChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($chatId)
    {

        $chat = Chat::where('id', $chatId)->update([
            'status_id' => TicketStatus::STATUS_CLOSED,
        ]);

        //update chat log's ended_at chat time, to identify the duration of chat for each user agent's chat
        $agentChatLog = AgentChatLog::where('chat_id', $chatId)->update(['ended_at' => \Carbon\Carbon::now()]);

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('channel-name');
        return ['customer-ended-chat'];
    }
}
