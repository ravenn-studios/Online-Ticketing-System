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

use App\Customer;
use App\Chat;
use App\ChatMessage;
use App\TicketStatus;
use App\Ticket;
use App\Message;
use App\TicketOrigin;
use App\TicketPriority;
use App\TicketType;
use App\GmailApi;
use App\AgentChatLog;
use App\Setting;
use App\User;

class CustomerMessaged implements ShouldBroadcast {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat_id;
    public $customer_id;

    public function __construct($name, $email, $message, $type, $file_id = null) {

        // logger($name);

        if ($type == 1) // under chat
        {

            $customer = Customer::firstOrNew(
                [
                    'name'       => $name,
                    'email'      => $email,
                    // 'ip_address' => \Request::ip() // need the actual ip to be sent
                ],
                [
                    'name'       => $name,
                    'email'      => $email,
                    'ip_address' => \Request::ip() // need the actual ip to be sent
                ]
            );
            

            //if customer does not exists, save, and create the chat and chat messages.
            if ( !$customer->exists )
            {

                $customer->save();

                $chat = Chat::create([
                    'customer_id' => $customer->id,
                    'status_id'   => TicketStatus::STATUS_UNASSIGNED,
                ]);
                
                if ( !empty($message) )
                {

                    $chat_message = ChatMessage::create([
                        'chat_id' => $chat->id,
                        'user_id' => $customer->id,
                        'from'    => 'customer',
                        'message' => base64_encode($message),
                        'file_id' => $file_id
                        // 'message' => base64_encode('A customer wants to chat with you.'),
                    ]);
                    
                }

            }
            else
            {
                //if the customer exists, check if the last chat status is already closed then create new chat and chat messages
                // else just add chat messages on to the last conversaion messages

                $chat = Chat::where([
                    'customer_id' => $customer->id,
                    ['status_id', '!=', TicketStatus::STATUS_CLOSED],
                ])->first();

        
                //if chat closed already, create new
                if ( !$chat )
                {
                    
                    $chat = Chat::create([
                        'customer_id' => $customer->id,
                        'status_id'   => TicketStatus::STATUS_UNASSIGNED,
                    ]);

                }

                $chat_message = ChatMessage::create([
                    'chat_id' => $chat->id,
                    'user_id' => $customer->id,
                    'from'    => 'customer',
                    'message' => base64_encode($message),
                    'file_id' => $file_id
                ]);
                
            }

            //add/update chat log, to identify the duration of chat for each user agent's chat
            $agentChatLog = AgentChatLog::updateOrCreate(
                [
                    'chat_id' => $chat->id,
                ],
                [
                    'chat_id' => $chat->id,
                ]
            );
            // logger($agentChatLog);

            $this->chat_id     = $chat->id;
            $this->customer_id = $customer->id;

        }
        else // offline message, create ticket
        {

            $emailSupport           = \App\EmailSupportAddress::active()->first()->email;
            $autoTicketDistribution = Setting::active()->where('name', Setting::AUTO_TICKET_DISTRIBUTION)->count();
            $users                  = User::faeAgents()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();

            //same id and thread_id
            $createTicket = new Ticket([
                'thread_id'         => 0, // tmp
                'history_id'        => 0,
                'channel_id'        => TicketOrigin::ORIGIN_CHAT,                // temporarily for GMAIL
                'origin_id'         => TicketOrigin::ORIGIN_CHAT,
                'subject'           => 'Offline chat message - ' . $name,
                'snippet'           => 'Offline chat message - ' . $name,
                // 'requester'         => $thread['requester'],
                'requester'         => $email,
                'receiver'          => $emailSupport,
                'thread_started_at' => \Carbon\Carbon::now(),
                'status_id'         => TicketStatus::STATUS_PENDING,
                'priority_id'       => TicketPriority::PRIORITY_NORMAL, // default for now
                'type_id'           => TicketType::TYPE_QUESTION, // default for now
            ]);

            if ( $createTicket->save() )
            {
                $storeMessage = new Message([
                    'ticket_id'     => $createTicket->id,
                    'message_id'    => 0, //tmp
                    'message'       => ($file_id) ? $message : base64_encode($message),
                    // 'message'       => $message['message'],
                    'from'          => $email,
                    'to'            => $emailSupport,
                    // 'internal_date' => self::formatInternalDate($message['internal_date']),
                    'internal_date' => \Carbon\Carbon::now(),
                ]);

                if ( $storeMessage->save() )
                {

                    if ( $autoTicketDistribution )
                    {
                        $ticket = new Ticket;

                        $ticket->assignTickets($random = true, [$createTicket->fresh()], $users->toArray());
                    }

                }

            }

            Ticket::where('id', $createTicket->id)->update(['thread_id'   => $createTicket->id]);
            // Message::where('id', $storeMessage->id)->update(['message_id' => $storeMessage->id]);

        }

    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('customer-messaged');
        return ['customer-messaged'];
    }
}
