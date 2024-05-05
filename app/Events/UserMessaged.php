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

class UserMessaged implements ShouldBroadcast
{

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $response;

    public function __construct($chat_id, $message, $fileId = null, $image = '')
    {

        $user = Auth::user();

        //if true, create message and notify user end. else, means that u are just starting chat.
        if( !empty($message) || $fileId != null )
        {

            $chat = \App\Chat::where('id', $chat_id)->update([
                'status_id'  => TicketStatus::STATUS_PENDING
            ]);

            // if ( $fileId != null )
            // {
            //     $message = $image;
            // }

            $chat_message = \App\ChatMessage::create([
                'chat_id' => $chat_id,
                'message' => base64_encode($message),
                'file_id' => $fileId,
                'from'    => 'agent',
                'user_id' => $user->id,
                'read'    => true,
            ]);

            $this->response = [
                'chat'    => $chat_message->chat,
                'message' => $chat_message->fresh(),
                'user'    => $user->userDetails(),
                'image'   => $image
            ];
        }
        else
        {
            //response only after start chat
            $this->response = [
                'chat'    => ['id' => $chat_id],
                'message' => $message,
                'user'    => $user->userDetails(),
                'image'   => $image
            ];
        }

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('channel-name');
        return ['user-messaged'];
    }
}
