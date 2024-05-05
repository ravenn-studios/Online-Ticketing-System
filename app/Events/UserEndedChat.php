<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\TicketStatus;
use App\Chat;
use App\AgentChatLog;

class UserEndedChat implements ShouldBroadcast
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $response;

    public function __construct($chatId)
    {

        $chat = tap(Chat::where('id', $chatId))->update([
            'status_id' => TicketStatus::STATUS_CLOSED,
        ]);

        //update chat log's ended_at chat time, to identify the duration of chat for each user agent's chat
        $agentChatLog = AgentChatLog::where('chat_id', $chatId)->update(['ended_at' => \Carbon\Carbon::now()]);

        $this->response = [
            'chat_id' => $chatId,
        ];

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('channel-name');
        return ['user-ended-chat'];
    }
}
