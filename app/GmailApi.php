<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Filter;
use Google_Service_Gmail_FilterCriteria;
use Google_Service_Gmail_FilterAction;
use App\Ticket;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\TicketOrigin;
use App\Message;
use App\Role;
use App\Setting;
use App\UserSchedule;
use App\SpamFilter;
use App\AssignedTicket;
use DateTime;
use DateTimeZone;
use DB;
use Log;
use App\EmailSupportAddress;
use Storage;
use Intervention\Image\Facades\Image;
use App\Mail\SendEmailWithNoReply;
use Illuminate\Support\Facades\Mail;


class GmailApi extends Model
{

    static public $receiver;
    static public $isComposedMessage             = false;
    static public $awaitingFulfillmentOrShipment = false;
    static public $orderNumber                   = false;
    static public $threadId;

    public static function sendChatMessage($strRawMessage, $emailContent, $ticketId) {

        $mime   = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
        $msg    = new Google_Service_Gmail_Message;
        $msg->setRaw($mime);

        $user   = 'me';
        $result = self::_sendMessage($user, $msg);

        // dd($result, $result->getId());

        $ticket = Ticket::find($ticketId);

        $ticket->createMessage($emailContent, $result);

        return $result;

    }

    public static function sendComposedMessage($strRawMessage, $receiver, $userId = null, $awaitingFulfillmentOrShipment = null, $orderNumber = null) {

        $mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
        $msg = new Google_Service_Gmail_Message;
        $msg->setRaw($mime);

        // logger(json_encode($msg));

        // $msg->threadId = $threadId;
        // dd($msg);
        // $client  = self::getClient();
        // $service = new Google_Service_Gmail($client);
        $user    = 'me';

        $result = self::_sendMessage($user, $msg);
        // dump($result);
        // dd($result->threadId);

        self::$receiver                      = $receiver;
        self::$isComposedMessage             = true;
        self::$awaitingFulfillmentOrShipment = $awaitingFulfillmentOrShipment;
        self::$orderNumber                   = $orderNumber;
        self::$threadId                      = $result->id;
        //true = composed message

        $emailSupportAddresses = EmailSupportAddress::active()->first();

        // // Get the API client and construct the service object.
        // $client  = self::getClient( $emailSupportAddresses->id );
        // $service = new Google_Service_Gmail($client);
        // $user    = 'me';

        // $thread = self::getThread($service, $user, $result->id);

        $ticket = self::createTickets(null, $userId);
        // dd($ticket);
        // dd(00);
        // $syncThread = self::getTicketDataToSync($result->id, true);

        // return $result;
        return ['message' => $result, 'ticket_id' => $ticket];

    }

    public static function sendMessage($strRawMessage, $threadId) {
        // Send message

        // Sync messages from ThreadID


        $mime = rtrim(strtr(base64_encode($strRawMessage), '+/', '-_'), '=');
        // $mime = strtr(base64_encode($strRawMessage), array('+' => '-', '/' => '_'));

        $msg = new Google_Service_Gmail_Message;
        $msg->setRaw($mime);

        // logger(json_encode($msg));

        $msg->threadId = $threadId;
        // dump(123);
        // $client  = self::getClient();
        // $service = new Google_Service_Gmail($client);
        $user    = 'me';

        $result = self::_sendMessage($user, $msg);
        self::$threadId = $result->id;

        // dump($result);
        // dd($result->threadId);

        // self::$receiver = $receiver;
        
        // $thread = self::getTicketDataToSync($threadId);
        // $syncThread = self::syncThread($threadId);

        /*if ( \Auth::id() == 1 )
        {
            logger('--syncTicket--');
            self::syncTicket($threadId);
        }
        else
        {
            logger('--createTickets--');
            self::createTickets();
        }*/

        self::syncTicket($threadId);

        return $result;

    }
    
    public static function _sendMessage($userId, $message) {
        // dd($message);
        //in the future, add "primary email" function for email support address
        $emailSupportAddresses = EmailSupportAddress::active()->first();

        // $client  = self::getClient( $emailSupportAddresses->id ); //
        $client  = self::getClient( $emailSupportAddresses->id );
        $service = new Google_Service_Gmail($client);

        try {

          $message = $service->users_messages->send($userId, $message);

        //   dd($message);
        //   print 'Message with ID: ' . $message->getId() . ' sent.';
          return $message;
        } catch (Exception $e) {
        //   print 'An error occurred: ' . $e->getMessage();
        }

    }

    //basically to update thread's messages
    public static function syncThread($threadId)
    {

        $thread = self::getTicketDataToSync($threadId);
        // on syc, updated thread's historyId
        foreach ($thread as $_thread)
        {

            Ticket::where('thread_id', $_thread['thread_id'])
            ->update([
                'history_id' => $_thread['history_id'],
                'updated_at'        => \Carbon\Carbon::now(),
            ]);


            foreach ($_thread['messages'] as $message) {

                if ( self::messageExists($message['message_id']) === false )
                {

                    $ticketId = Ticket::where('thread_id', $_thread['thread_id'])->first()->id;

                    $storeMessage = Message::create([
                        'ticket_id'     => $ticketId,
                        'message_id'    => $message['message_id'],
                        'message'       => $message['message'],
                        'from'          => $message['from'],
                        'to'            => $message['to'],
                        'internal_date' => self::formatInternalDate($message['internal_date']),
                        'created_at'    => \Carbon\Carbon::now(),
                        'updated_at'    => \Carbon\Carbon::now(),
                    ]);

                    Ticket::where('thread_id', $_thread['thread_id'])
                        ->update([
                            'snippet' => $_thread['snippet'],
                            'updated_at'        => \Carbon\Carbon::now(),
                        ]);

                }

                //save attachments
                $attachments = $message['attachments'];
                if ( !empty( $attachments ) )
                {

                    foreach ( $attachments as $key => $file )
                    {
                        
                        // return ['data' => $data, 'name' => $part->filename];
                        $ticketModel = new Ticket;
                        $ticketModel->saveAttachment( [ 'data' => $file['data'], 'name' => $file['filename'] ] );

                    }

                }

            }

        }

    }

    public static function getTicketDataToSync($threadId, $test = false)
    {

        //in the future, add "primary email" function for email support address
        $emailSupportAddresses = EmailSupportAddress::active()->first();

        // // Get the API client and construct the service object.
        $client  = self::getClient( $emailSupportAddresses->id );
        $service = new Google_Service_Gmail($client);
        $user    = 'me';

        // $threads = self::listThreads($service,$user);
        $data    = Array();
        $i       = 0;
        $k       = 0;

        $thread = self::getThread($service, $user, $threadId);

        $j = 0;

        // if ($test)
        // {
        //     dump($thread);
        //     dd($thread->messages);
        // }

        // $thread                 = self::getThread($service, $user, $thread['id']);
        $data[$i]['thread_id']    = $threadId;
        $data[$i]['history_id']   = $thread['historyId'];
        $data[$i]['subject']      = self::getSubject($thread->messages[0]->payload->headers);
        $data[$i]['requester']    = self::getFrom($thread->messages[0]->payload->headers);
        $lastIndexOfMessage       = count($thread->messages) - 1;
        $data[$i]['snippet']      = $thread->messages[$lastIndexOfMessage]->snippet;
        $data[$i]['date']         = self::getDate($thread->messages[0]->payload->headers);
        
        $messages = $thread->messages;
        // dd($messages);
        $_message = $textHtml = $textPlain = '';
        foreach ($messages as $key => $message) {

            $attachments = [];
            $_message    = '';
            // if (isset($message->payload->parts)) {

            //     if ( isset( $message->payload->parts[1]->body->data ) && $message->payload->parts[1]->body->data != null )
            //     {
            //         $_message = $message->payload->parts[1]->body->data;
            //     }
            //     else if ( isset( $message->payload->parts[0]->body->data ) && $message->payload->parts[0]->body->data != null )
            //     {
            //         $_message = $message->payload->parts[0]->body->data;
            //     }

            // }
            // else
            // {
            //     $_message = $message->payload->body->data;
            // }

            if (isset($message->payload->parts)) {

                foreach ( $message->payload->parts as $part )
                {
                    if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                    {

                        $attachments = self::getAttachments( $message->id, $message->payload->parts, $service );
                    }
                    else
                    {
                        if ( isset($part->parts) )
                        {

                            //get only message that has attachment - payload parts changes if the message has attachment.

                            foreach($part->parts as $_part)
                            {
                                //store each text/plain and text/html contents in a variable
                                // make a condition if text/html is empy then use text/plain if not empty..

                                if ( $_part->getMimeType() == 'text/html' && $_part->getBody()->data != null && empty( $textHtml ) )
                                {
                                    $textHtml = $_part->getBody()->data;
                                    $mimeType = $_part->getMimeType();
                                }
                                else if ( $_part->getMimeType() == 'text/plain' && $_part->getBody()->data != null && empty( $textPlain ) )
                                {
                                    $textPlain = $_part->getBody()->data;
                                    $mimeType = $_part->getMimeType();
                                }
                                
                            }

                        }
                        else
                        {

                            //get message that has no attachment - payload parts changes if the message has attachment.

                            //store each text/plain and text/html contents in a variable
                            // make a condition if text/html is empy then use text/plain if not empty..

                            if ( empty( $_message ) )
                            {

                                if ( $part->getMimeType() == 'text/html' && $part->getBody()->data != null && empty( $textHtml ) )
                                {
                                    $textHtml = $part->getBody()->data;
                                    $mimeType = $part->getMimeType();
                                }
                                else if ( $part->getMimeType() == 'text/plain' && $part->getBody()->data != null && empty( $textPlain ) )
                                {
                                    $textPlain = $part->getBody()->data;
                                    $mimeType = $part->getMimeType();
                                }

                            }
                            
                        }
                    }
                }

            }
            else
            {
                $_message = $message->payload->body->data;
            }
            
            if ( isset($textHtml) && $textHtml != null )
            {
                $_message = $textHtml;
            }
            else if ( isset($textPlain) && $textPlain != null )
            {
                $_message = $textPlain;
            }

            $textHtml = $textPlain = '';

            $data[$i]['messages'][$j]['message_id']    = $message->id;
            $data[$i]['messages'][$j]['internal_date'] = $message->internalDate;
            $data[$i]['messages'][$j]['from']          = self::getFrom($message->payload->headers);
            $data[$i]['messages'][$j]['to']            = self::getFrom($message->payload->headers, 'To');
            $data[$i]['messages'][$j]['message']       = $_message;
            $data[$i]['messages'][$j]['attachments'] = $attachments;
            $j++;
        }

        return $data;

    }


    public static function createTickets($_threadId = null, $userId = null)
    {
        //this function is also being called in syncTickets command
        // make a dedicated function to sync tickets but by checking history id(specific for GmailAPI) so asto minimize the updating/checking of records/fields

        // Ticket::truncate(); 
        // Message::truncate(); 
        // dd('end');

        if ( $_threadId != null )
        {
            $threads = self::buildTicketData($_threadId);
        }
        else
        {
            $threads = self::buildTicketData();
        }
        

        $createdTicketId = 0;

        // $arrUpdateTickets = Array();
        // $_msg = '';
        $arrFileIds = [];
        
        /*DB::beginTransaction();

        try {*/

            $createdTickets = $updatedTickets = [];
            // $users          = User::faeAgents()->get();
            /*$now = \Carbon\Carbon::now()->format('Y-m-d');
            $sat = '2022-04-02';

            $users = User::faeAgentsExcludeAnne()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();

            if ($now == $sat)
            {
                $users = User::where('id', 8)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
            }*/

            $timeStart = microtime(true);
            //assign tickets to users based on their schedules(regular work day) - /users/schedules
            $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
            $userIds       = array_column($usersSchedule,'user_id');

            //temporary - if anne's tickets reached 5, removed from tickets assignee for now
            $_tmpTicket             = new Ticket;
            $annesTicketsCount      = $_tmpTicket->count_user_tickets_today(9); // user id - anne
            $gieBabesTicketsCount   = $_tmpTicket->count_user_tickets_today(25);
            $christinesTicketsCount = $_tmpTicket->count_user_tickets_today(29);

            if ( $annesTicketsCount >= 3 )
            {
                $key = array_search(9, $userIds);
                if( $key )
                {
                    unset($userIds[$key]);
                }
            }

            if ( $gieBabesTicketsCount >= 3 )
            {
                $key = array_search(25, $userIds);
                if( $key )
                {
                    unset($userIds[$key]);
                }
            }

            if ( $christinesTicketsCount >= 3 )
            {
                $key = array_search(29, $userIds);
                if( $key )
                {
                    unset($userIds[$key]);
                }
            }
            



            // Log::info('create tickets and assign with one of these ids: ' . implode(",",$userIds));
            // $users         = User::whereIn('id', $userIds)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
            // $users         = User::whereIn('id', $userIds)->withCount(['tickets' => function($q){
            //                     $q->where('status_id', TicketStatus::STATUS_PENDING);
            //                 }])
            //                 ->orderBy('tickets_count', 'asc')->get();

            if ( self::$awaitingFulfillmentOrShipment )
            {

                $users = self::getUsersForAFTickets();

            }
            else
            {

                if ( empty($userIds) )
                {
                    $users = User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
                }
                else
                {
                    $users = User::whereIn('id', $userIds)->withCount(['tickets' => function($q){
                                    $q->where('status_id', TicketStatus::STATUS_PENDING);
                                }])
                                ->orderBy('tickets_count', 'asc')->get();
                }

            }
            logger('Get available users to assign a ticket: ' . (microtime(true) - $timeStart));

            $autoTicketDistribution = Setting::active()->where('name', Setting::AUTO_TICKET_DISTRIBUTION)->count();
            $ticketStatus           = TicketStatus::STATUS_UNASSIGNED;
            $_ticketId              = '';

            if ( $autoTicketDistribution )
            {
                $ticketStatus = TicketStatus::STATUS_PENDING;
            }

            $timeStart = microtime(true);
            foreach ($threads as $thread) {

                // if($thread['thread_id'] == '183bf1a21ddb2f39') // tmp quickfix to bypass the error for encoding/decoding string with emoji
                // {
                //     $thread['snippet'] = 'Thank you for getting back to me so quickly';
                // }

                $tmpQueuedTicket = Ticket::whereIn('thread_id', ['','0',0])->where('subject', trim($thread['subject']))->first();

                if ( self::threadExists($thread['thread_id']) === false )
                {

                    if($tmpQueuedTicket) // we used blank thread_id to identify the tickets that were pre created before the actual data was sync with gmail api
                    {

                        $tmpQueuedTicket->update([
                            'thread_id'         => $thread['thread_id'],
                            'history_id'        => $thread['history_id'],
                            'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                            'origin_id'         => self::identifyOriginId($thread['requester']),
                            'subject'           => trim($thread['subject']),
                            'snippet'           => trim($thread['snippet']),
                            'requester'         => str_replace(array('\'','"'), '', $thread['requester']),
                            'receiver'          => $thread['receiver'],
                            'thread_started_at' => $thread['date'],
                            'status_id'         => $ticketStatus,
                        ]);

                        $updatedTickets[] = $tmpQueuedTicket->fresh();

                    }
                    else
                    {

                        $thread['subject'] = self::remove_emoji($thread['subject']);
                        $thread['snippet'] = self::remove_emoji($thread['snippet']);

                        $tmpSubject = trim($thread['subject']);

                        if( strpos($tmpSubject, '[Frankies Auto Electrics & Car Audio]: New order #') !== false )
                        {
                            logger('--Checking New Order, Set status to Unassigned--');
                            $ticketStatus = TicketStatus::STATUS_UNASSIGNED;
                        }


                        $createTicket = new Ticket([
                            'thread_id'         => $thread['thread_id'],
                            'order_number'      => (self::$orderNumber) ? (string)self::$orderNumber : null,
                            'history_id'        => $thread['history_id'],
                            'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                            'origin_id'         => self::identifyOriginId($thread['requester']),
                            'subject'           => $tmpSubject,
                            'snippet'           => trim($thread['snippet']),
                            'reply_to'          => $thread['reply-to'],
                            'requester'         => str_replace(array('\'','"'), '', $thread['requester']),
                            'receiver'          => $thread['receiver'],
                            // 'thread_started_at' => self::formatThreadDate($thread['date']),
                            'thread_started_at' => $thread['date'],
                            'status_id'         => (self::$awaitingFulfillmentOrShipment) ? TicketStatus::STATUS_CLOSED : $ticketStatus,
                            // 'priority_id'       => (int)TicketPriority::PRIORITY_NORMAL, // default for now
                            'type_id'           => (self::$awaitingFulfillmentOrShipment) ? self::$awaitingFulfillmentOrShipment : TicketType::TYPE_QUESTION
                        ]);

                        $createTicket->save();

                        Log::info('create tickets and assign with one of these ids: ' . implode(",",$userIds));

                        $_ticketId        = $createTicket->id;
                        $createdTickets[] = $createTicket->fresh();

                        if(self::$awaitingFulfillmentOrShipment)
                        {
                            Log::info('Created Awaiting Fulfillment / Shipment Tickets and automaticall set to closed status with ticket id: '.$_ticketId);
                        }

                        if( strpos($tmpSubject, '[Frankies Auto Electrics & Car Audio]: New order #') === false )
                        {
                            $createdTickets[] = $createTicket->fresh();
                        }

                        if( empty($updatedTickets) )
                        {
                            $updatedTickets[] = $createTicket->fresh();
                        }

                        //assigning of created tickets
                        $timeStart = microtime(true);
                        if ( !empty($createdTickets) && $autoTicketDistribution && !self::$isComposedMessage )
                        {
                            $ticket = new Ticket;

                            $ticket->assignTickets($random = true, $createdTickets, $users->toArray());
                            $createdTickets = [];
                        }
                        logger('Assigning of Tickets: ' . (microtime(true) - $timeStart));

                    }

                }
                else
                {
                    
                    // $updateTicket = Ticket::where('thread_id', $thread['thread_id'])
                    //                         ->update([
                    //                             'origin_id' => self::identifyOriginId($thread['requester']),
                    //                         ]);

                    // $_ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;

                    $_ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;


                    //all updates/replies on any of karina's tickets, that ticket will automatically be re-assigned to other available agents to handle them
                    $assignedTicket = AssignedTicket::where(['ticket_id' => $_ticketId]);

                    if ( !self::$awaitingFulfillmentOrShipment && $assignedTicket->count() && $assignedTicket->first()->user_id == 18 ) // karina
                    {
                        if ( empty($userIds) )
                        {
                            $reAssignToUserId = User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->first()->id;
                        }
                        else
                        {
                            $reAssignToUserId = array_rand($userIds);
                        }

                        AssignedTicket::where(['ticket_id' => $_ticketId])->first()->update(['user_id' => $reAssignToUserId]);

                        logger('Re-Assigning Karina Ticket to user_id: ' . $reAssignToUserId);
                    }

                }

                foreach ($thread['messages'] as $message)
                {

                    $__ticket        = Ticket::where('thread_id', $thread['thread_id'])->first();
                    $__ticketId      = $__ticket->id;
                    $__ticketSubject = trim($__ticket->subject);

                    if ( self::messageExists($message['message_id']) === false )
                    {

                        //queued message = message was already created in database before send(optimization, and prevent users to experience the slowdowns when sending a message
                        $queuedMessage = Message::where('ticket_id', $__ticketId)->where('message_id', '')->count();

                        if ( $queuedMessage > 0 )
                        {
                            $updateMessage  = Message::where('ticket_id', $__ticketId)->where('message_id', '')
                                                    ->update([
                                                        'message_id'    => $message['message_id'],
                                                        'message'       => self::messageEncode($message['message'], $message['attachments']),
                                                        'internal_date' => $message['internal_date'],
                                                        'updated_at'    => \Carbon\Carbon::now(),
                                                    ]);
                        }
                        else
                        {
                            logger('Ticket Id: ' . $__ticketId);
                            $storeMessage = new Message([
                                // 'ticket_id'     => $createTicket->id,
                                'ticket_id'     => $__ticketId,
                                'message_id'    => $message['message_id'],
                                'message'       => self::messageEncode($message['message'], $message['attachments']),
                                // 'message'       => $message['message'],
                                'from'          => $message['from'],
                                'to'            => $message['to'],
                                // 'internal_date' => self::formatInternalDate($message['internal_date']),
                                'internal_date' => $message['internal_date'],
                                'created_at'    => \Carbon\Carbon::now(),
                                'updated_at'    => \Carbon\Carbon::now(),
                            ]);

                            $storeMessage->save();
                        }
                        

                        $emailSupportAddress = EmailSupportAddress::where('email', 'like', '%'.$message['from'].'%')->get()->count();

                        $ticket = Ticket::find( $__ticketId );

                        if ( !$emailSupportAddress )
                        {
                            $ticket->read = false;

                            // if AF tickets, and with reply, re-assign to ronel

                            if ( self::$awaitingFulfillmentOrShipment )
                            {
                                $tmpReAssignToUserId = 26;
                                AssignedTicket::where(['ticket_id' => $__ticketId])->first()->update(['user_id' => $reAssignToUserId]);
                            }
                        }

                        // set ticket status to pending if theres new customer message for solved/closed tickets and tickets not == facebook or ebay
                        if ( ( $ticket->status_id == Ticket::STATUS_SOLVED || $ticket->status_id == Ticket::STATUS_CLOSED || $ticket->status_id == Ticket::STATUS_PENDING ) && ( $ticket->origin_id != TicketOrigin::ORIGIN_FACEBOOK || $ticket->origin_id != TicketOrigin::ORIGIN_EBAY ) && ( !$emailSupportAddress ) )
                        {
                            $ticket->status_id = (int)Ticket::STATUS_PENDING;
                            $ticket->read = 0;
                        }

                        $newOrderIsUnassigned = true;
                        logger('---------------------------');
                        logger($ticket->status_id);
                        logger($__ticketSubject);
                        logger($emailSupportAddress);
                        logger($message['from']);
                        logger('---------------------------');
                        // when an unassigned new order ticket receives message from customer set ticket status to pending
                        if ( $ticket->status_id == Ticket::STATUS_UNASSIGNED && strpos($__ticketSubject, '[Frankies Auto Electrics & Car Audio]: New order #') !== false && !$emailSupportAddress && $message['from'] != 'sales@frankiesautoelectrics.com.au' && $message['from'] != 'support@frankiesautoelectrics.com.au' )
                        {
                            $ticket->status_id = (int)Ticket::STATUS_PENDING;
                            $ticket->read = 0;

                            logger('--Checking Unassigned New Order--');
                            logger($__ticketSubject);
                            logger($ticket->status_id);
                            logger($emailSupportAddress);
                            logger($message['from']);
                            logger('--End Checking Unassigned New Order--');

                            $newOrderIsUnassigned = false;
                        }

                        $ticket->save();


                        //after save, if the unassigned is now assigned
                        if ( $newOrderIsUnassigned === false )
                        {
                            $tmpUpdatedTickets[] = $ticket->fresh();

                            $ticket->assignTickets($random = true, $tmpUpdatedTickets, $users->toArray());
                        }


                        //save attachments
                        $getMessage = Message::where('message_id', $message['message_id'])->first(); // prevent duplicate saving of attachments
                        $attachments = $message['attachments'];
                        if ( !empty( $attachments ) )
                        {
                            // dump($message);
                            // dd($attachments);
                            $fileIds = [];
                            $fileNames = [];
                            foreach ( $attachments as $key => $file )
                            {
                                
                                $_file = \App\File::where('name', $file['filename']);
                                $fileExists = $_file->exists();
                                if ( $file['for_attachment'] == true )
                                {

                                    $createdFile = false;
                                    if ( !$fileExists )
                                    {
                                        $ticketModel = new Ticket;
                                        $createdFile = $ticketModel->saveAttachment( [ 'data' => $file['data'], 'name' => $file['filename'] ] );
                                    }

                                    //validate if file exists in db, create file if not yet exists and add to array records to avoid duplicates file_ids on each messages
                                    if ( $createdFile != false )
                                    {

                                        $_fileName = $createdFile['filename'];
                                        $ext = explode( '.', $_fileName );
                                        $ext = end($ext);
                                        
                                        $__file = \App\File::create([
                                            'name'      => $_fileName,
                                            'extension' => $ext,
                                            'path'      => $createdFile['path'],
                                        ]);

                                        array_push($fileIds, $__file->id);
                                        array_push($fileNames, $file['filename']);

                                    }
                                    else if ( !in_array($file['filename'], $fileNames) )
                                    {
                                        array_push($fileIds, $_file->first()->id);
                                        array_push($fileNames, $file['filename']);
                                    }

                                }

                            }

                            // Message::where('message_id', $message['message_id'])->update(['file_ids' => json_encode($fileIds)]);
                            array_push($arrFileIds, ['message_id' => $message['message_id'], 'file_ids' => json_encode($fileIds)]);

                        }

                        $ticket = Ticket::find( $__ticketId );
                        $ticket->updated_at = \Carbon\Carbon::now();
                        $ticket->save();

                    }
                    // else
                    // {
                    //     //update message, because there are some cases that messages as null..
                    //     // this will be called from minute cronjob
                    //     $updateMessage = Message::where('message_id', $message['message_id'])
                    //                     ->update([
                    //                         'message' => $message['message'],
                    //                     ]);

                    // }

                }

            }
            logger('Create/Update Tickets: ' . (microtime(true) - $timeStart));


            /*$timeStart = microtime(true);
            if ( !empty($createdTickets) && $autoTicketDistribution && !self::$isComposedMessage )
            {
                $ticket = new Ticket;

                $ticket->assignTickets($random = true, $createdTickets, $users->toArray());
            }
            logger('Assigning of Tickets: ' . (microtime(true) - $timeStart));*/

            $timeStart = microtime(true);
            if ( self::$isComposedMessage )
            {
                //on composed message, assign to the composer
                $ticket = new Ticket;

                if ( self::$awaitingFulfillmentOrShipment )
                {
                    // $user = $users->toArray();
                    // $user = [0 => User::find(1)->toArray()];
                    $user = $users->toArray();
                }
                else if ( !Ticket::BACKGROUND_PROCESS_SEND_MESSAGE )
                {
                    $user = [0 => \Auth::user()->toArray()];
                }
                else
                {
                    $user = [0 => User::find($userId)->toArray()];
                }

                logger('sending compose message, assign to user: ' . json_encode($user));
                // $ticket->assignTickets($random = true, $createdTickets, \Auth::user()->toArray());
                $ticket->assignTickets($random = true, $updatedTickets, $user);
            }

            logger('Assigning of Composed Message/Tickets: ' . (microtime(true) - $timeStart));

            /*DB::commit();

        }
        catch (exception $e)
        {
            DB::rollback();
        }*/

        // Message::where('message_id', $message['message_id'])->update(['file_ids' => json_encode($fileIds)]);
        // array_push($arrFileIds, ['message_id' => $message['message_id'], 'file_ids' => json_encode($fileIds)]);
        foreach ($arrFileIds as $arrFileId) // moved the updating of message file ids outside transactions to avoid deadlock error since App\Message was use twice inside transaction
        {
            Message::where('message_id', $arrFileId['message_id'])->update(['file_ids' => $arrFileId['file_ids']]);
        }

        return $_ticketId;

    }

    public static function syncTicket($_threadId)
    {
        //this function is also being called in syncTickets command
        // make a dedicated function to sync tickets but by checking history id(specific for GmailAPI) so asto minimize the updating/checking of records/fields

        $threads = self::buildSingleTicketData($_threadId);
        

        $createdTicketId = 0;

        // $arrUpdateTickets = Array();
        // $_msg = '';
        $arrFileIds = [];
        
        /*DB::beginTransaction();

        try {*/

            $createdTickets = $updatedTickets = [];
            // $users          = User::faeAgents()->get();
            /*$now = \Carbon\Carbon::now()->format('Y-m-d');
            $sat = '2022-04-02';

            $users = User::faeAgentsExcludeAnne()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();

            if ($now == $sat)
            {
                $users = User::where('id', 8)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
            }*/

            $timeStart = microtime(true);
            //assign tickets to users based on their schedules(regular work day) - /users/schedules
            $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
            $userIds       = array_column($usersSchedule,'user_id');

            //temporary - if anne's tickets reached 5, removed from tickets assignee for now
            $_tmpTicket        = new Ticket;
            $annesTicketsCount = $_tmpTicket->count_user_tickets_today(9); // user id - anne

            if ( $annesTicketsCount >= 3 )
            {

                $key = array_search(9, $userIds);
                if( $key )
                {
                    unset($userIds[$key]);
                }

            }
            //



            // Log::info('create tickets and assign with one of these ids: ' . implode(",",$userIds));
            // $users         = User::whereIn('id', $userIds)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
            // $users         = User::whereIn('id', $userIds)->withCount(['tickets' => function($q){
            //                     $q->where('status_id', TicketStatus::STATUS_PENDING);
            //                 }])
            //                 ->orderBy('tickets_count', 'asc')->get();

            if ( self::$awaitingFulfillmentOrShipment )
            {

                $users = self::getUsersForAFTickets();

            }
            else
            {

                if ( empty($userIds) )
                {
                    $users = User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
                }
                else
                {
                    $users = User::whereIn('id', $userIds)->withCount(['tickets' => function($q){
                                    $q->where('status_id', TicketStatus::STATUS_PENDING);
                                }])
                                ->orderBy('tickets_count', 'asc')->get();
                }

            }
            logger('Get available users to assign a ticket: ' . (microtime(true) - $timeStart));

            $autoTicketDistribution = Setting::active()->where('name', Setting::AUTO_TICKET_DISTRIBUTION)->count();
            $ticketStatus           = TicketStatus::STATUS_UNASSIGNED;
            $_ticketId              = '';

            if ( $autoTicketDistribution )
            {
                $ticketStatus = TicketStatus::STATUS_PENDING;
            }

            $timeStart = microtime(true);
            foreach ($threads as $thread) {

                // if($thread['thread_id'] == '183bf1a21ddb2f39') // tmp quickfix to bypass the error for encoding/decoding string with emoji
                // {
                //     $thread['snippet'] = 'Thank you for getting back to me so quickly';
                // }

                $tmpQueuedTicket = Ticket::whereIn('thread_id', ['','0',0])->where('subject', trim($thread['subject']))->first();

                if ( self::threadExists($thread['thread_id']) === false )
                {

                    if($tmpQueuedTicket) // we used blank thread_id to identify the tickets that were pre created before the actual data was sync with gmail api
                    {

                        $tmpQueuedTicket->update([
                            'thread_id'         => $thread['thread_id'],
                            'history_id'        => $thread['history_id'],
                            'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                            'origin_id'         => self::identifyOriginId($thread['requester']),
                            'subject'           => trim($thread['subject']),
                            'snippet'           => trim($thread['snippet']),
                            'requester'         => str_replace(array('\'','"'), '', $thread['requester']),
                            'receiver'          => $thread['receiver'],
                            'thread_started_at' => $thread['date'],
                            'status_id'         => $ticketStatus,
                        ]);

                        $updatedTickets[] = $tmpQueuedTicket->fresh();

                    }
                    else
                    {

                        $thread['subject'] = self::remove_emoji($thread['subject']);
                        $thread['snippet'] = self::remove_emoji($thread['snippet']);

                        $tmpSubject = trim($thread['subject']);

                        if( strpos($tmpSubject, '[Frankies Auto Electrics & Car Audio]: New order #') !== false )
                        {
                            logger('--Checking New Order, Set status to Unassigned--');
                            $ticketStatus = TicketStatus::STATUS_UNASSIGNED;
                        }


                        $createTicket = new Ticket([
                            'thread_id'         => $thread['thread_id'],
                            'order_number'      => (self::$orderNumber) ? (string)self::$orderNumber : null,
                            'history_id'        => $thread['history_id'],
                            'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                            'origin_id'         => self::identifyOriginId($thread['requester']),
                            'subject'           => $tmpSubject,
                            'snippet'           => trim($thread['snippet']),
                            'reply_to'          => $thread['reply-to'],
                            'requester'         => str_replace(array('\'','"'), '', $thread['requester']),
                            'receiver'          => $thread['receiver'],
                            // 'thread_started_at' => self::formatThreadDate($thread['date']),
                            'thread_started_at' => $thread['date'],
                            'status_id'         => (self::$awaitingFulfillmentOrShipment) ? TicketStatus::STATUS_CLOSED : $ticketStatus,
                            // 'priority_id'       => (int)TicketPriority::PRIORITY_NORMAL, // default for now
                            'type_id'           => (self::$awaitingFulfillmentOrShipment) ? self::$awaitingFulfillmentOrShipment : TicketType::TYPE_QUESTION
                        ]);

                        $createTicket->save();

                        Log::info('create tickets and assign with one of these ids: ' . implode(",",$userIds));

                        $_ticketId        = $createTicket->id;
                        $createdTickets[] = $createTicket->fresh();

                        if(self::$awaitingFulfillmentOrShipment)
                        {
                            Log::info('Created Awaiting Fulfillment / Shipment Tickets and automaticall set to closed status with ticket id: '.$_ticketId);
                        }

                        if( strpos($tmpSubject, '[Frankies Auto Electrics & Car Audio]: New order #') === false )
                        {
                            $createdTickets[] = $createTicket->fresh();
                        }

                        if( empty($updatedTickets) )
                        {
                            $updatedTickets[] = $createTicket->fresh();
                        }

                        //assigning of created tickets
                        $timeStart = microtime(true);
                        if ( !empty($createdTickets) && $autoTicketDistribution && !self::$isComposedMessage )
                        {
                            $ticket = new Ticket;

                            $ticket->assignTickets($random = true, $createdTickets, $users->toArray());
                            $createdTickets = [];
                        }
                        logger('Assigning of Tickets: ' . (microtime(true) - $timeStart));

                    }

                }
                else
                {
                    
                    // $updateTicket = Ticket::where('thread_id', $thread['thread_id'])
                    //                         ->update([
                    //                             'origin_id' => self::identifyOriginId($thread['requester']),
                    //                         ]);

                    // $_ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;

                    $_ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;


                    //all updates/replies on any of karina's tickets, that ticket will automatically be re-assigned to other available agents to handle them
                    $assignedTicket = AssignedTicket::where(['ticket_id' => $_ticketId]);

                    if ( !self::$awaitingFulfillmentOrShipment && $assignedTicket->count() && $assignedTicket->first()->user_id == 18 ) // karina
                    {
                        if ( empty($userIds) )
                        {
                            $reAssignToUserId = User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->first()->id;
                        }
                        else
                        {
                            $reAssignToUserId = array_rand($userIds);
                        }

                        AssignedTicket::where(['ticket_id' => $_ticketId])->first()->update(['user_id' => $reAssignToUserId]);

                        logger('Re-Assigning Karina Ticket to user_id: ' . $reAssignToUserId);
                    }

                }

                foreach ($thread['messages'] as $message)
                {

                    $__ticket        = Ticket::where('thread_id', $thread['thread_id'])->first();
                    $__ticketId      = $__ticket->id;
                    $__ticketSubject = trim($__ticket->subject);

                    if ( self::messageExists($message['message_id']) === false )
                    {

                        //queued message = message was already created in database before send(optimization, and prevent users to experience the slowdowns when sending a message
                        $queuedMessage = Message::where('ticket_id', $__ticketId)->where('message_id', '')->count();

                        if ( $queuedMessage > 0 )
                        {
                            $updateMessage  = Message::where('ticket_id', $__ticketId)->where('message_id', '')
                                                    ->update([
                                                        'message_id'    => $message['message_id'],
                                                        'message'       => self::messageEncode($message['message'], $message['attachments']),
                                                        'internal_date' => $message['internal_date'],
                                                        'updated_at'    => \Carbon\Carbon::now(),
                                                    ]);
                        }
                        else
                        {
                            logger('Ticket Id: ' . $__ticketId);
                            $storeMessage = new Message([
                                // 'ticket_id'     => $createTicket->id,
                                'ticket_id'     => $__ticketId,
                                'message_id'    => $message['message_id'],
                                'message'       => self::messageEncode($message['message'], $message['attachments']),
                                // 'message'       => $message['message'],
                                'from'          => $message['from'],
                                'to'            => $message['to'],
                                // 'internal_date' => self::formatInternalDate($message['internal_date']),
                                'internal_date' => $message['internal_date'],
                                'created_at'    => \Carbon\Carbon::now(),
                                'updated_at'    => \Carbon\Carbon::now(),
                            ]);

                            $storeMessage->save();
                        }
                        

                        $emailSupportAddress = EmailSupportAddress::where('email', 'like', '%'.$message['from'].'%')->get()->count();

                        $ticket = Ticket::find( $__ticketId );

                        if ( !$emailSupportAddress )
                        {
                            $ticket->read = false;
                        }

                        // set ticket status to pending if theres new customer message for solved/closed tickets and tickets not == facebook or ebay
                        if ( ( $ticket->status_id == Ticket::STATUS_SOLVED || $ticket->status_id == Ticket::STATUS_CLOSED || $ticket->status_id == Ticket::STATUS_PENDING ) && ( $ticket->origin_id != TicketOrigin::ORIGIN_FACEBOOK || $ticket->origin_id != TicketOrigin::ORIGIN_EBAY ) && ( !$emailSupportAddress ) )
                        {
                            $ticket->status_id = (int)Ticket::STATUS_PENDING;
                            $ticket->read = 0;
                        }

                        $newOrderIsUnassigned = true;
                        logger('---------------------------');
                        logger($ticket->status_id);
                        logger($__ticketSubject);
                        logger($emailSupportAddress);
                        logger($message['from']);
                        logger('---------------------------');
                        // when an unassigned new order ticket receives message from customer set ticket status to pending
                        if ( $ticket->status_id == Ticket::STATUS_UNASSIGNED && strpos($__ticketSubject, '[Frankies Auto Electrics & Car Audio]: New order #') !== false && !$emailSupportAddress && $message['from'] != 'sales@frankiesautoelectrics.com.au' && $message['from'] != 'support@frankiesautoelectrics.com.au' )
                        {
                            $ticket->status_id = (int)Ticket::STATUS_PENDING;
                            $ticket->read = 0;

                            logger('--Checking Unassigned New Order--');
                            logger($__ticketSubject);
                            logger($ticket->status_id);
                            logger($emailSupportAddress);
                            logger($message['from']);
                            logger('--End Checking Unassigned New Order--');

                            $newOrderIsUnassigned = false;
                        }

                        $ticket->save();


                        //after save, if the unassigned is now assigned
                        if ( $newOrderIsUnassigned === false )
                        {
                            $tmpUpdatedTickets[] = $ticket->fresh();

                            $ticket->assignTickets($random = true, $tmpUpdatedTickets, $users->toArray());
                        }


                        //save attachments
                        $getMessage = Message::where('message_id', $message['message_id'])->first(); // prevent duplicate saving of attachments
                        $attachments = $message['attachments'];
                        if ( !empty( $attachments ) )
                        {
                            // dump($message);
                            // dd($attachments);
                            $fileIds = [];
                            $fileNames = [];
                            foreach ( $attachments as $key => $file )
                            {
                                
                                $_file = \App\File::where('name', $file['filename']);
                                $fileExists = $_file->exists();
                                if ( $file['for_attachment'] == true )
                                {

                                    $createdFile = false;
                                    if ( !$fileExists )
                                    {
                                        $ticketModel = new Ticket;
                                        $createdFile = $ticketModel->saveAttachment( [ 'data' => $file['data'], 'name' => $file['filename'] ] );
                                    }

                                    //validate if file exists in db, create file if not yet exists and add to array records to avoid duplicates file_ids on each messages
                                    if ( $createdFile != false )
                                    {

                                        $_fileName = $createdFile['filename'];
                                        $ext = explode( '.', $_fileName );
                                        $ext = end($ext);
                                        
                                        $__file = \App\File::create([
                                            'name'      => $_fileName,
                                            'extension' => $ext,
                                            'path'      => $createdFile['path'],
                                        ]);

                                        array_push($fileIds, $__file->id);
                                        array_push($fileNames, $file['filename']);

                                    }
                                    else if ( !in_array($file['filename'], $fileNames) )
                                    {
                                        array_push($fileIds, $_file->first()->id);
                                        array_push($fileNames, $file['filename']);
                                    }

                                }

                            }

                            // Message::where('message_id', $message['message_id'])->update(['file_ids' => json_encode($fileIds)]);
                            array_push($arrFileIds, ['message_id' => $message['message_id'], 'file_ids' => json_encode($fileIds)]);

                        }

                        $ticket = Ticket::find( $__ticketId );
                        $ticket->updated_at = \Carbon\Carbon::now();
                        $ticket->save();

                    }
                    // else
                    // {
                    //     //update message, because there are some cases that messages as null..
                    //     // this will be called from minute cronjob
                    //     $updateMessage = Message::where('message_id', $message['message_id'])
                    //                     ->update([
                    //                         'message' => $message['message'],
                    //                     ]);

                    // }

                }

            }
            logger('Create/Update Tickets: ' . (microtime(true) - $timeStart));


            /*$timeStart = microtime(true);
            if ( !empty($createdTickets) && $autoTicketDistribution && !self::$isComposedMessage )
            {
                $ticket = new Ticket;

                $ticket->assignTickets($random = true, $createdTickets, $users->toArray());
            }
            logger('Assigning of Tickets: ' . (microtime(true) - $timeStart));*/

            $timeStart = microtime(true);
            if ( self::$isComposedMessage )
            {
                //on composed message, assign to the composer
                $ticket = new Ticket;

                if ( self::$awaitingFulfillmentOrShipment )
                {
                    // $user = $users->toArray();
                    // $user = [0 => User::find(1)->toArray()];
                    $user = $users->toArray();
                }
                else if ( !Ticket::BACKGROUND_PROCESS_SEND_MESSAGE )
                {
                    $user = [0 => \Auth::user()->toArray()];
                }
                else
                {
                    $user = [0 => User::find($userId)->toArray()];
                }

                logger('sending compose message, assign to user: ' . json_encode($user));
                // $ticket->assignTickets($random = true, $createdTickets, \Auth::user()->toArray());
                $ticket->assignTickets($random = true, $updatedTickets, $user);
            }

            logger('Assigning of Composed Message/Tickets: ' . (microtime(true) - $timeStart));

            /*DB::commit();

        }
        catch (exception $e)
        {
            DB::rollback();
        }*/

        // Message::where('message_id', $message['message_id'])->update(['file_ids' => json_encode($fileIds)]);
        // array_push($arrFileIds, ['message_id' => $message['message_id'], 'file_ids' => json_encode($fileIds)]);
        foreach ($arrFileIds as $arrFileId) // moved the updating of message file ids outside transactions to avoid deadlock error since App\Message was use twice inside transaction
        {
            Message::where('message_id', $arrFileId['message_id'])->update(['file_ids' => $arrFileId['file_ids']]);
        }

        return $_ticketId;

    }

    public static function getUsersForAFTickets()
    {
        // default to miss fortune(manager) - handles AF tickets
        $users = User::whereIn('id', [7])->withCount(['tickets' => function($q){
                        $q->where('status_id', TicketStatus::STATUS_PENDING)
                            ->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)
                            ->orWhere('type_id', TicketType::TYPE_AWAITING_SHIPMENT);
                    }])
                    ->orderBy('tickets_count', 'asc')->get();

        if ( !$users->count() )
        {
            $users = User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
        }

        return $users;
    }

    public static function remove_emoji($string)
    {
        // Match Enclosed Alphanumeric Supplement
        $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
        $clear_string = preg_replace($regex_alphanumeric, '', $string);

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $clear_string);

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);
        
        // Match Supplemental Symbols and Pictographs
        $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';
        $clear_string = preg_replace($regex_supplemental, '', $clear_string);

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        return $clear_string;
    }

    public static function updateTicketsMessages($_threadId = null)
    {
        //this function is also being called in syncTickets command
        // make a dedicated function to sync tickets but by checking history id(specific for GmailAPI) so asto minimize the updating/checking of records/fields

        // Ticket::truncate(); 
        // Message::truncate(); 
        // dd('end');
        $arrThreads = [];
        $threads = self::buildTicketDataToUpdateMessages($_threadId);

        //tmp set all to tickets to Tin - 11/10/2021
        // $users = User::where('id', 8)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();

        // dd($threads);

        foreach ($threads as $thread)
        {

            // $ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;

            if( !in_array($thread['thread_id'], $arrThreads) )
            {

                foreach ($thread['messages'] as $message)
                {

                    if ( self::messageExists($message['message_id']) )
                    {

                        $updateMessage = Message::where('message_id', $message['message_id'])
                                        ->update([
                                            'message' => self::messageEncode($message['message'], null),
                                        ]);

                    }

                }

                array_push($arrThreads, $thread['thread_id']);

            }

            // usleep(350000);
            usleep(400000);

        }

        logger('Updating message under thread_ids => ?: ' . implode(",",$arrThreads));

        return true;

    }

    public static function tmpCreateTickets($_threadId = null)
    {
        //this function is also being called in syncTickets command
        // make a dedicated function to sync tickets but by checking history id(specific for GmailAPI) so asto minimize the updating/checking of records/fields

        // Ticket::truncate(); 
        // Message::truncate(); 
        // dd('end');
        // dd($_threadId);
        if ( $_threadId != null )
        {
            $threads = self::tmpBuildTicketData($_threadId);
        }
        else
        {
            $threads = self::tmpBuildTicketData();
        }

        $createdTicketId = 0;

        // $arrUpdateTickets = Array();
        // $_msg = '';
        
        DB::beginTransaction();

        try {

            $createdTickets = Array();
            // $users          = User::faeAgents()->get();
            // $users          = User::faeAgents()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
            //tmp set all to tickets to Tin - 11/10/2021
            $users             = User::where('id', 8)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();


            $autoTicketDistribution = Setting::active()->where('name', Setting::AUTO_TICKET_DISTRIBUTION)->count();
            $ticketStatus           = TicketStatus::STATUS_UNASSIGNED;
            $_ticketId = '';

            if ( $autoTicketDistribution )
            {
                $ticketStatus = TicketStatus::STATUS_PENDING;
            }
            // dd($threads);
            foreach ($threads as $thread) {

                // if ($thread['thread_id'] == '1754ff12da959918')
                // {
                //     dd($thread);
                // }
                

                if ( self::threadExists($thread['thread_id']) === false )
                {
                    // $createTicket = Ticket::create([
                    //     'thread_id'         => $thread['thread_id'],
                    //     'history_id'        => $thread['history_id'],
                    //     'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                    //     'origin_id'         => self::identifyOriginId($thread['requester']),
                    //     'subject'           => trim($thread['subject']),
                    //     'snippet'           => trim($thread['snippet']),
                    //     // 'requester'         => $thread['requester'],
                    //     'requester'         => str_replace(array('\'','"'), '', $thread['requester']),
                    //     'receiver'          => $thread['receiver'],
                    //     // 'thread_started_at' => self::formatThreadDate($thread['date']),
                    //     'thread_started_at' => $thread['date'],
                    //     'status_id'         => $ticketStatus,
                    //     'priority_id'       => (int)TicketPriority::PRIORITY_NORMAL, // default for now
                    //     'type_id'           => (int)TicketType::TYPE_QUESTION, // default for now
                    // ]);

                    $tmpTicketType = TicketType::TYPE_QUESTION;
                    if ( self::$awaitingFulfillmentOrShipment == 'Awaiting Fulfillment')
                    {
                        $tmpTicketType = TicketType::active()->where('name','Awaiting Fulfillment')->first()->id;
                    }
                    else if ( self::$awaitingFulfillmentOrShipment == 'Awaiting Shipment')
                    {
                        $tmpTicketType = TicketType::active()->where('name','Awaiting Shipment')->first()->id;
                    }

                    $createTicket = new Ticket([
                        'thread_id'         => $thread['thread_id'],
                        'history_id'        => $thread['history_id'],
                        'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                        'origin_id'         => self::identifyOriginId($thread['requester']),
                        'subject'           => trim($thread['subject']),
                        'snippet'           => trim($thread['snippet']),
                        // 'requester'         => $thread['requester'],
                        'requester'         => str_replace(array('\'','"'), '', $thread['requester']),
                        'receiver'          => $thread['receiver'],
                        // 'thread_started_at' => self::formatThreadDate($thread['date']),
                        'thread_started_at' => $thread['date'],
                        'status_id'         => $ticketStatus,
                        // 'priority_id'       => (int)TicketPriority::PRIORITY_NORMAL, // default for now
                        'type_id'           => (int)$tmpTicketType, // default for now
                    ]);

                    $createTicket->save();

                    $_ticketId = $createTicket->id;

                    $createdTickets[] = $createTicket->fresh();
                }
                // else
                // {
                    
                //     $updateTicket = Ticket::where('thread_id', $thread['thread_id'])
                //                             ->update([
                //                                 'origin_id' => self::identifyOriginId($thread['requester']),
                //                             ]);

                //     // $_ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;

                // }

                foreach ($thread['messages'] as $message)
                {

                    if ( self::messageExists($message['message_id']) === false )
                    {

                        $__ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;

                        // $date = date('Y-m-d H:i:s', $message['internal_date']);
                        // $storeMessage = Message::create([
                        //     // 'ticket_id'     => $createTicket->id,
                        //     'ticket_id'     => $__ticketId,
                        //     'message_id'    => $message['message_id'],
                        //     'message'       => self::messageEncode($message['message'], $message['attachments']),
                        //     // 'message'       => $message['message'],
                        //     'from'          => $message['from'],
                        //     'to'            => $message['to'],
                        //     // 'internal_date' => self::formatInternalDate($message['internal_date']),
                        //     'internal_date' => $message['internal_date'],
                        //     'created_at'    => \Carbon\Carbon::now(),
                        //     'updated_at'    => \Carbon\Carbon::now(),
                        // ]);

                        $storeMessage = new Message([
                            // 'ticket_id'     => $createTicket->id,
                            'ticket_id'     => $__ticketId,
                            'message_id'    => $message['message_id'],
                            'message'       => self::messageEncode($message['message'], $message['attachments']),
                            // 'message'       => $message['message'],
                            'from'          => $message['from'],
                            'to'            => $message['to'],
                            // 'internal_date' => self::formatInternalDate($message['internal_date']),
                            'internal_date' => $message['internal_date'],
                            'created_at'    => \Carbon\Carbon::now(),
                            'updated_at'    => \Carbon\Carbon::now(),
                        ]);

                        $storeMessage->save();
                        

                        $emailSupportAddress = EmailSupportAddress::where('email', 'like', '%'.$message['from'].'%')->get()->count();

                        $ticket       = Ticket::find( $__ticketId );

                        if ( !$emailSupportAddress )
                        {
                            $ticket->read = false;
                        }

                        // set ticket status to pending if theres new customer message for solved/clsoed tickets and tickets not == facebook or ebay
                        if ( ( $ticket->status_id == Ticket::STATUS_SOLVED || $ticket->status_id == Ticket::STATUS_CLOSED || $ticket->status_id == Ticket::STATUS_PENDING ) && ( $ticket->origin_id != TicketOrigin::ORIGIN_FACEBOOK || $ticket->origin_id != TicketOrigin::ORIGIN_EBAY ) && ( !$emailSupportAddress ) )
                        {
                            $ticket->status_id = (int)Ticket::STATUS_PENDING;
                            $ticket->read = 0;
                        }

                        $ticket->save();

                        //save attachments
                        $getMessage = Message::where('message_id', $message['message_id'])->first(); // prevent duplicate saving of attachments
                        $attachments = $message['attachments'];
                        if ( !empty( $attachments ) )
                        {
                            // dump($message);
                            // dd($attachments);
                            $fileIds = [];
                            $fileNames = [];
                            foreach ( $attachments as $key => $file )
                            {
                                
                                $_file = \App\File::where('name', $file['filename']);
                                $fileExists = $_file->exists();
                                if ( $file['for_attachment'] == true )
                                {

                                    $createdFile = false;
                                    if ( !$fileExists )
                                    {
                                        $ticketModel = new Ticket;
                                        $createdFile = $ticketModel->saveAttachment( [ 'data' => $file['data'], 'name' => $file['filename'] ] );
                                    }

                                    //validate if file exists in db, create file if not yet exists and add to array records to avoid duplicates file_ids on each messages
                                    if ( $createdFile != false )
                                    {

                                        $_fileName = $createdFile['filename'];
                                        $ext = explode( '.', $_fileName );
                                        $ext = end($ext);
                                        
                                        $file = \App\File::create([
                                            'name'      => $_fileName,
                                            'extension' => $ext,
                                            'path'      => $createdFile['path'],
                                        ]);

                                        array_push($fileIds, $file->id);
                                        array_push($fileNames, $file['filename']);

                                    }
                                    else if ( !in_array($file['filename'], $fileNames) )
                                    {
                                        array_push($fileIds, $_file->first()->id);
                                        array_push($fileNames, $file['filename']);
                                    }

                                }

                            }

                            Message::where('message_id', $message['message_id'])->update(['file_ids' => json_encode($fileIds)]);

                        }

                        $ticket = Ticket::find( $__ticketId );
                        $ticket->updated_at = \Carbon\Carbon::now();
                        $ticket->save();

                    }
                    // else
                    // {
                    //     //update message, because there are some cases that messages as null..
                    //     // this will be called from minute cronjob
                    //     $updateMessage = Message::where('message_id', $message['message_id'])
                    //                     ->update([
                    //                         'message' => $message['message'],
                    //                     ]);

                    // }

                }

            }


            if ( !empty($createdTickets) && $autoTicketDistribution && !self::$isComposedMessage )
            {
                $ticket = new Ticket;

                $ticket->assignTickets($random = true, $createdTickets, $users->toArray());
            }
            else if ( self::$isComposedMessage )
            {
                //on composed message, assign to the composer
                $ticket = new Ticket;

                $ticket->assignTickets($random = true, $createdTickets, \Auth::user()->toArray());
            }

            // Log::info('Message Ids that can be updated: '.implode(',',$arrUpdateTickets));
            // Log::info('Message Id 1750017eb73b291d message: '.$_msg);
            // dd();
            DB::commit();

        }
        catch (exception $e)
        {
            DB::rollback();
        }

        return $_ticketId;

    }

    public static function threadExists($threadId)
    {

        if ( Ticket::where('thread_id', $threadId)->count() > 0 )
        {
            return true;
        }

        return false;

    }

    public static function messageExists($messageId)
    {

        if ( Message::where('message_id', $messageId)->count() > 0 )
        {
            return true;
        }

        return false;

    }

    public static function formatInternalDate($internalDate)
    {
        //date from api is AST timezone
        $seconds = $internalDate / 1000;
        $d = date("Y-m-d H:i:s P", $seconds);

        $date = new DateTime($d, new DateTimeZone('Canada/Atlantic'));
        // echo $date->format('Y-m-d H:i:s') . "<br>";

        $date->setTimezone(new DateTimeZone('Australia/Sydney'));
        $messageDateSydney = $date->format('Y-m-d H:i:s');

        return $messageDateSydney;
    }

    public static function formatThreadDate($date)
    {
        //date from api is from the apps timezone
        return date('Y-m-d h:i:s', strtotime($date));

    }

    public static function tmpGetAttachmentData($message_id, $attachmentId)
    {

        $emailSupportAddresses = EmailSupportAddress::active()->get();
        $service = '';
        //loop throug hactive email support address and get their email threads
        foreach ( $emailSupportAddresses as $emailSupportAddress )
        {

            // // Get the API client and construct the service object.
            $client  = self::getClient( $emailSupportAddress->id );
            $service = new Google_Service_Gmail($client);

        }

        $attachment = $service->users_messages_attachments->get('me', $message_id, $attachmentId);
        // dump(000);
        // dd($attachment);
        $data = str_replace(array('-','_'),array('+','/'), $attachment->data);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return $data;

    }

    public static function buildTicketData($_threadId = null)
    {

        $data    = Array();
        $i       = 0;
        $k       = 0;
        $threads = [];

        //get active email support address
        $emailSupportAddresses = EmailSupportAddress::active()->get();

        //loop throug hactive email support address and get their email threads
        foreach ( $emailSupportAddresses as $emailSupportAddress )
        {

            // // Get the API client and construct the service object.
            $client  = self::getClient( $emailSupportAddress->id );
            $service = new Google_Service_Gmail($client);
            $user    = 'me';

            $timeStart = microtime(true);
            
            if ( $_threadId != null)
            {
                // logger('--1111111--');
                // logger('--'.$_threadId.'--');
                $threads = self::getThread($service, $user, $_threadId);
                // dd($threads);
            }
            else if ( !self::$isComposedMessage )
            {
                // logger('--2222222--');
                $threads = self::listThreads($service, $user);
            }
            else
            {
                // logger('--3333333--');
                $threads = self::getThread($service, $user, self::$threadId);
            }

            logger('List/Get Threads: ' . (microtime(true) - $timeStart));


            // $threads = self::getThread($service, $user, self::$threadId);

            $timeStart = microtime(true);

            // logger(['RDC' => $threads]);
            // logger(['RDC COUNT' => count($threads)]);

            foreach($threads as $thread) {
               
                // logger([$thread]);
                // logger('--444444--');
                $j = 0;
                
                // logger('--555555--');

                $thread = self::getThread($service, $user, $thread['id']);

                // logger('Run Me');
                // if ( \Auth::id() == 1 )
                // {
                //     dump($thread->messages);
                // }
                
                $data[$i]['thread_id']      = $thread['id'];
                $data[$i]['history_id']     = $thread['historyId'];
                $data[$i]['subject']        = self::getSubject($thread->messages[0]->payload->headers);
                // logger('--666666--');
                
                $threadFrom    = self::getFrom($thread->messages[0]->payload->headers);
                $threadTo      = self::getFrom($thread->messages[0]->payload->headers, 'To');
                $threadReplyTo = self::getFrom($thread->messages[0]->payload->headers, 'Reply-To');
                // logger('--777777--');

                $isEmailSupportAddressFrom  = EmailSupportAddress::active()->whereIn('email', [$threadFrom])->get()->count();
                // $isEmailSupportAddressTo    = EmailSupportAddress::active()->whereIn('email', [$threadTo])->get()->count();

                if ( !self::$isComposedMessage )
                {
                    $data[$i]['requester'] = ( !$isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                    $data[$i]['receiver']  = ( $isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                    $data[$i]['reply-to']  = ($threadReplyTo == 'sales@frankiesautoelectrics.com.au' || $threadReplyTo == 'support@frankiesautoelectrics.com.au') ? '' : $threadReplyTo;
                }
                // else if ( self::$isComposedMessage && self::$awaitingFulfillmentOrShipment )
                // {
                //     $data[$i]['requester'] = $threadFrom;
                //     $data[$i]['receiver']  = $threadTo;
                // }
                else
                {
                    $data[$i]['requester'] = $threadTo;
                    $data[$i]['receiver']  = $threadFrom;
                    $data[$i]['reply-to']  = '';
                }
                
                $lastIndexOfMessage         = count($thread->messages) - 1;
                $data[$i]['snippet']        = ( empty($thread->messages[$lastIndexOfMessage]->snippet) ) ? self::getSubject($thread->messages[0]->payload->headers) : $thread->messages[$lastIndexOfMessage]->snippet;
                $date                       = self::getDate($thread->messages[0]->payload->headers);
                $date                       = date('Y-m-d H:i:s', strtotime($date));
                $data[$i]['date']           = $date;
                
                $messages = $thread->messages;
                // dump($messages);dd();
                $_message = $textHtml = $textPlain = '';
                $existingAttachments = [];
                foreach ($messages as $key => $message) {

                    $attachments = $messageAttachments = [];
                    $_message    = '';

                    // if(strpos( self::getSubject($thread->messages[0]->payload->headers), '(#87317)' ) !== false){
                    //     dd($message);
                    // }

                    // dump($message);
                    // if($message->id == '17968ed461289d2d')
                    // {
                    //     dump($message);
                    // }
                    
                    // if($message->id == '179405af6e414c60')
                    // {

                        if (isset($message->payload->parts)) {

                            foreach ( $message->payload->parts as $part )
                            {

                                if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                                {

                                    // $attachments = self::getAttachments( $message->id, $message->payload->parts, $service ); 
                                    $messageAttachments = self::getAllAttachments( $message->id, $message->payload->parts, $service, true );
                                    // $existingAttachmentsId = self::getAllAttachmentsId( $message->id, $message->payload->parts, $service );
                                    
                                    // dump('--');
                                    // dump($messageAttachments);
                                    // dump('--');
                                    // self::getAllAttachments( $message->id, $message->payload->parts, $service );

                                    // dd($attachments);
                                    // dump('--attachment--');
                                    // dump($part->getBody()->getAttachmentId());
                                    // dump('--end attachment--');
                                    // if( $message->id == '17968ed461289d2d' )
                                    // {
                                    //     dd($attachments);
                                    // }
                                }
                                else
                                {
                                    if ( isset($part->parts) )
                                    {

                                        $getParts = self::getParts($message->payload);
                                        $_data = array();
                                        array_walk_recursive($getParts, function ($value, $key) use (&$_data){
                                            if ($value)
                                                $_data[] = $value;
                                        }, $_data);
                                        
                                        logger($message->id);
                                        // logger(json_encode($_data));

                                        if ( !empty($_data) )
                                        {
                                            $_message = $_data[ count($_data) - 1 ];

                                            $messageAttachments = self::getAllAttachments( $message->id, $part->parts, $service, true ); // apply this on 04/11/22
                                        }

                                    }
                                    else
                                    {

                                        //get message that has no attachment - payload parts changes if the message has attachment.

                                        //store each text/plain and text/html contents in a variable
                                        // make a condition if text/html is empy then use text/plain if not empty..
        
                                        if ( empty( $_message ) )
                                        {
        
                                            if ( $part->getMimeType() == 'text/html' && $part->getBody()->data != null )
                                            {
                                                // $textHtml = $part->getBody()->data;
                                                $_message = $part->getBody()->data;
                                                // $mimeType = $part->getMimeType();
                                            }
                                            
                                            if ( $part->getMimeType() == 'text/plain' && $part->getBody()->data != null && empty( $textPlain ) )
                                            {
                                                $textPlainData = trim( base64_decode($part->getBody()->data) );
                                                if ( !empty($textPlainData) )
                                                {
                                                    $textPlain = $part->getBody()->data;
                                                }
                                            }

                                            //add a another variable for plain/text if ever msg is empty
                                        }
                                        
                                    }
                                }
                            }

                        }
                        else
                        {
                            // $_message = $message->payload->body->data;
                            $_message = $message->payload->body->data;
                        }
                        

                    // }

                    //if decoded message html is empty, get text plain.
                    $_messageData = trim( base64_decode($_message) );
                    if ( empty($_messageData) )
                    {
                        $_textPlainData = trim( base64_decode($textPlain) );
                        if ( !empty($_textPlainData) )
                        {
                            $_message = $textPlain;
                        }
                    }

                    $data[$i]['messages'][$j]['message_id']    = $message->id;
                    $_date                                     = date('Y-m-d H:i:s', strtotime(self::getDate($message->payload->headers)));
                    $data[$i]['messages'][$j]['internal_date'] = $_date;
                    // $data[$i]['messages'][$j]['internal_date'] = $message->internalDate;
                    // $data[$i]['messages'][$j]['from']          = self::getFrom($message->payload->headers);
                    $data[$i]['messages'][$j]['from']        = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers) );
                    $data[$i]['messages'][$j]['to']          = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers, 'To') );
                    $data[$i]['messages'][$j]['message']     = $_message;
                    $data[$i]['messages'][$j]['attachments'] = $messageAttachments;
                    // $data[$i]['messages'][$j]['all_attachments'] = $messageAttachments;
                    // $attachments = [];
                    $j++;
                }

                $i++;

                // logger('--000000forEach--');

            }

            logger('Set Thread Data: ' . (microtime(true) - $timeStart));

        }
        // foreach($data[0]['messages'] as $val)
        // {
        //     echo base64_decode( self::messageEncode( $val['message'] ) ) .'<br/>-------------------------------------------</br>';
        // }
        // dd();
        // dd($data);
        return $data;

    }

    public static function buildSingleTicketData($_threadId = null)
    {

        $data    = Array();
        $i       = 0;
        $k       = 0;
        $threads = [];

        //get active email support address
        $emailSupportAddresses = EmailSupportAddress::active()->get();

        //loop throug hactive email support address and get their email threads
        foreach ( $emailSupportAddresses as $emailSupportAddress )
        {

            // // Get the API client and construct the service object.
            $client  = self::getClient( $emailSupportAddress->id );
            $service = new Google_Service_Gmail($client);
            $user    = 'me';

            $timeStart = microtime(true);
            
            if ( $_threadId != null)
            {
                logger('--1111111--');
                logger('--'.$_threadId.'--');
                $threads = self::getThread($service, $user, $_threadId);
                // dd($threads);
            }
            else if ( !self::$isComposedMessage )
            {
                logger('--2222222--');
                $threads = self::listThreads($service, $user);
            }
            else
            {
                logger('--3333333--');
                $threads = self::getThread($service, $user, self::$threadId);
            }

            logger('List/Get Threads: ' . (microtime(true) - $timeStart));


            // $threads = self::getThread($service, $user, self::$threadId);

            $timeStart = microtime(true);

            $tmpCtr = 0;

            foreach($threads as $thread) {

                $j = 0;

                if ( $tmpCtr == 0 )
                {

                    $thread                     = self::getThread($service, $user, $thread['id']);

                    $data[$i]['thread_id']      = $thread['id'];
                    $data[$i]['history_id']     = $thread['historyId'];
                    $data[$i]['subject']        = self::getSubject($thread->messages[0]->payload->headers);
                    // logger('--666666--');
                    
                    $threadFrom    = self::getFrom($thread->messages[0]->payload->headers);
                    $threadTo      = self::getFrom($thread->messages[0]->payload->headers, 'To');
                    $threadReplyTo = self::getFrom($thread->messages[0]->payload->headers, 'Reply-To');
                    // logger('--777777--');

                    $isEmailSupportAddressFrom  = EmailSupportAddress::active()->whereIn('email', [$threadFrom])->get()->count();
                    // $isEmailSupportAddressTo    = EmailSupportAddress::active()->whereIn('email', [$threadTo])->get()->count();

                    if ( !self::$isComposedMessage )
                    {
                        $data[$i]['requester'] = ( !$isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                        $data[$i]['receiver']  = ( $isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                        $data[$i]['reply-to']  = ($threadReplyTo == 'sales@frankiesautoelectrics.com.au' || $threadReplyTo == 'support@frankiesautoelectrics.com.au') ? '' : $threadReplyTo;
                    }
                    // else if ( self::$isComposedMessage && self::$awaitingFulfillmentOrShipment )
                    // {
                    //     $data[$i]['requester'] = $threadFrom;
                    //     $data[$i]['receiver']  = $threadTo;
                    // }
                    else
                    {
                        $data[$i]['requester'] = $threadTo;
                        $data[$i]['receiver']  = $threadFrom;
                        $data[$i]['reply-to']  = '';
                    }
                    
                    $lastIndexOfMessage         = count($thread->messages) - 1;
                    $data[$i]['snippet']        = ( empty($thread->messages[$lastIndexOfMessage]->snippet) ) ? self::getSubject($thread->messages[0]->payload->headers) : $thread->messages[$lastIndexOfMessage]->snippet;
                    $date                       = self::getDate($thread->messages[0]->payload->headers);
                    $date                       = date('Y-m-d H:i:s', strtotime($date));
                    $data[$i]['date']           = $date;
                    
                    $messages = $thread->messages;
                    // dump($messages);dd();
                    $_message = $textHtml = $textPlain = '';
                    $existingAttachments = [];
                    foreach ($messages as $key => $message) {

                        $attachments = $messageAttachments = [];
                        $_message    = '';

                        // if(strpos( self::getSubject($thread->messages[0]->payload->headers), '(#87317)' ) !== false){
                        //     dd($message);
                        // }

                        // dump($message);
                        // if($message->id == '17968ed461289d2d')
                        // {    
                        //     dump($message);
                        // }
                        
                        // if($message->id == '179405af6e414c60')
                        // {

                            if (isset($message->payload->parts)) {

                                foreach ( $message->payload->parts as $part )
                                {

                                    if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                                    {

                                        // $attachments = self::getAttachments( $message->id, $message->payload->parts, $service ); 
                                        $messageAttachments = self::getAllAttachments( $message->id, $message->payload->parts, $service, true );
                                        // $existingAttachmentsId = self::getAllAttachmentsId( $message->id, $message->payload->parts, $service );
                                        
                                        // dump('--');
                                        // dump($messageAttachments);
                                        // dump('--');
                                        // self::getAllAttachments( $message->id, $message->payload->parts, $service );

                                        // dd($attachments);
                                        // dump('--attachment--');
                                        // dump($part->getBody()->getAttachmentId());
                                        // dump('--end attachment--');
                                        // if( $message->id == '17968ed461289d2d' )
                                        // {
                                        //     dd($attachments);
                                        // }
                                    }
                                    else
                                    {
                                        if ( isset($part->parts) )
                                        {

                                            $getParts = self::getParts($message->payload);
                                            $_data = array();
                                            array_walk_recursive($getParts, function ($value, $key) use (&$_data){
                                                if ($value)
                                                    $_data[] = $value;
                                            }, $_data);
                                            
                                            logger($message->id);
                                            // logger(json_encode($_data));

                                            if ( !empty($_data) )
                                            {
                                                $_message = $_data[ count($_data) - 1 ];

                                                $messageAttachments = self::getAllAttachments( $message->id, $part->parts, $service, true ); // apply this on 04/11/22
                                            }

                                        }
                                        else
                                        {

                                            //get message that has no attachment - payload parts changes if the message has attachment.

                                            //store each text/plain and text/html contents in a variable
                                            // make a condition if text/html is empy then use text/plain if not empty..
            
                                            if ( empty( $_message ) )
                                            {
            
                                                if ( $part->getMimeType() == 'text/html' && $part->getBody()->data != null )
                                                {
                                                    // $textHtml = $part->getBody()->data;
                                                    $_message = $part->getBody()->data;
                                                    // $mimeType = $part->getMimeType();
                                                }
                                                
                                                if ( $part->getMimeType() == 'text/plain' && $part->getBody()->data != null && empty( $textPlain ) )
                                                {
                                                    $textPlainData = trim( base64_decode($part->getBody()->data) );
                                                    if ( !empty($textPlainData) )
                                                    {
                                                        $textPlain = $part->getBody()->data;
                                                    }
                                                }

                                                //add a another variable for plain/text if ever msg is empty
                                            }
                                            
                                        }
                                    }
                                }

                            }
                            else
                            {
                                // $_message = $message->payload->body->data;
                                $_message = $message->payload->body->data;
                            }
                            

                        // }

                        //if decoded message html is empty, get text plain.
                        $_messageData = trim( base64_decode($_message) );
                        if ( empty($_messageData) )
                        {
                            $_textPlainData = trim( base64_decode($textPlain) );
                            if ( !empty($_textPlainData) )
                            {
                                $_message = $textPlain;
                            }
                        }

                        $data[$i]['messages'][$j]['message_id']    = $message->id;
                        $_date                                     = date('Y-m-d H:i:s', strtotime(self::getDate($message->payload->headers)));
                        $data[$i]['messages'][$j]['internal_date'] = $_date;
                        // $data[$i]['messages'][$j]['internal_date'] = $message->internalDate;
                        // $data[$i]['messages'][$j]['from']          = self::getFrom($message->payload->headers);
                        $data[$i]['messages'][$j]['from']        = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers) );
                        $data[$i]['messages'][$j]['to']          = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers, 'To') );
                        $data[$i]['messages'][$j]['message']     = $_message;
                        $data[$i]['messages'][$j]['attachments'] = $messageAttachments;
                        // $data[$i]['messages'][$j]['all_attachments'] = $messageAttachments;
                        // $attachments = [];
                        $j++;
                    }

                    $i++;

                }
                
                $tmpCtr++;

            }

            logger('Set Thread Data: ' . (microtime(true) - $timeStart));

        }

        return $data;

    }

    public static function buildTicketDataToUpdateMessages($_threadId = null)
    {

        $data    = Array();
        $i       = 0;
        $k       = 0;

        //get active email support address
        $emailSupportAddresses = EmailSupportAddress::active()->get();

        //loop throug hactive email support address and get their email threads
        foreach ( $emailSupportAddresses as $emailSupportAddress )
        {

            // // Get the API client and construct the service object.
            $client  = self::getClient( $emailSupportAddress->id );
            $service = new Google_Service_Gmail($client);
            $user    = 'me';
            
            if ( $_threadId != null)
            {

                $threads = self::getThread($service, $user, $_threadId);
                // dd($threads);
            }
            else
            {
                // dd(2);
                $threads = self::listThreadsToUpdateMessages($service, $user);
            }

            // $threads = self::getThread($service, $user, self::$threadId);

            
            foreach($threads as $thread) {

                $j = 0;

                if ( $_threadId != null)
                {
                    $thread = $threads;
                }
                else
                {
                    $thread = self::getThread($service, $user, $thread['id']);
                }

                // $thread                     = self::getThread($service, $user, $thread['id']);
                // $thread                     = $threads;
                // dd($thread);
                $data[$i]['thread_id']      = $thread['id'];
                $data[$i]['history_id']     = $thread['historyId'];
                $data[$i]['subject']        = self::getSubject($thread->messages[0]->payload->headers);
                
                $threadFrom = self::getFrom($thread->messages[0]->payload->headers);
                $threadTo   = self::getFrom($thread->messages[0]->payload->headers, 'To');

                $isEmailSupportAddressFrom  = EmailSupportAddress::active()->whereIn('email', [$threadFrom])->get()->count();
                // $isEmailSupportAddressTo    = EmailSupportAddress::active()->whereIn('email', [$threadTo])->get()->count();

                
                $data[$i]['requester'] = ( !$isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                $data[$i]['receiver']  = ( $isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                
                
                $lastIndexOfMessage         = count($thread->messages) - 1;
                $data[$i]['snippet']        = ( empty($thread->messages[$lastIndexOfMessage]->snippet) ) ? self::getSubject($thread->messages[0]->payload->headers) : $thread->messages[$lastIndexOfMessage]->snippet;
                $date                       = self::getDate($thread->messages[0]->payload->headers);
                $date                       = date('Y-m-d H:i:s', strtotime($date));
                $data[$i]['date']           = $date;
                
                $messages = $thread->messages;
                // dump($messages);dd();
                $_message = $textHtml = $textPlain = '';
                $existingAttachments = [];
                foreach ($messages as $key => $message) {

                    $attachments = $messageAttachments = [];
                    $_message    = '';

                    // if(strpos( self::getSubject($thread->messages[0]->payload->headers), '(#87317)' ) !== false){
                    //     dd($message);
                    // }

                    // dump($message);
                    // if($message->id == '17968ed461289d2d')
                    // {
                    //     dump($message);
                    // }
                    
                    // if($message->id == '179405af6e414c60')
                    // {

                        if (isset($message->payload->parts)) {

                            foreach ( $message->payload->parts as $part )
                            {

                                if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                                {

                                    // $attachments = self::getAttachments( $message->id, $message->payload->parts, $service ); 
                                    $messageAttachments = self::getAllAttachments( $message->id, $message->payload->parts, $service, true );
                                    // $existingAttachmentsId = self::getAllAttachmentsId( $message->id, $message->payload->parts, $service );
                                    
                                    // dump('--');
                                    // dump($messageAttachments);
                                    // dump('--');
                                    // self::getAllAttachments( $message->id, $message->payload->parts, $service );

                                    // dd($attachments);
                                    // dump('--attachment--');
                                    // dump($part->getBody()->getAttachmentId());
                                    // dump('--end attachment--');
                                    // if( $message->id == '17968ed461289d2d' )
                                    // {
                                    //     dd($attachments);
                                    // }
                                }
                                else
                                {
                                    if ( isset($part->parts) )
                                    {

                                        $getParts = self::getParts($message->payload);
                                        $_data = array();
                                        array_walk_recursive($getParts, function ($value, $key) use (&$_data){
                                            if ($value)
                                                $_data[] = $value;
                                        }, $_data);
                                        $_message = $_data[ count($_data) - 1 ];

                                        $messageAttachments = self::getAllAttachments( $message->id, $part->parts, $service, true ); // apply this on 04/11/22

                                    }
                                    else
                                    {

                                        //get message that has no attachment - payload parts changes if the message has attachment.

                                        //store each text/plain and text/html contents in a variable
                                        // make a condition if text/html is empy then use text/plain if not empty..
        
                                        if ( empty( $_message ) )
                                        {
        
                                            if ( $part->getMimeType() == 'text/html' && $part->getBody()->data != null )
                                            {
                                                // $textHtml = $part->getBody()->data;
                                                $_message = $part->getBody()->data;
                                                // $mimeType = $part->getMimeType();
                                            }
                                            
                                            if ( $part->getMimeType() == 'text/plain' && $part->getBody()->data != null && empty( $textPlain ) )
                                            {
                                                $textPlainData = trim( base64_decode($part->getBody()->data) );
                                                if ( !empty($textPlainData) )
                                                {
                                                    $textPlain = $part->getBody()->data;
                                                }
                                            }

                                            //add a another variable for plain/text if ever msg is empty
                                        }
                                        
                                    }
                                }
                            }

                        }
                        else
                        {
                            // $_message = $message->payload->body->data;
                            $_message = $message->payload->body->data;
                        }
                        

                    // }

                    //if decoded message html is empty, get text plain.
                    $_messageData = trim( base64_decode($_message) );
                    if ( empty($_messageData) )
                    {
                        $_textPlainData = trim( base64_decode($textPlain) );
                        if ( !empty($_textPlainData) )
                        {
                            $_message = $textPlain;
                        }
                    }

                    $data[$i]['messages'][$j]['message_id']    = $message->id;
                    $_date                                     = date('Y-m-d H:i:s', strtotime(self::getDate($message->payload->headers)));
                    $data[$i]['messages'][$j]['internal_date'] = $_date;
                    // $data[$i]['messages'][$j]['internal_date'] = $message->internalDate;
                    // $data[$i]['messages'][$j]['from']          = self::getFrom($message->payload->headers);
                    $data[$i]['messages'][$j]['from']        = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers) );
                    $data[$i]['messages'][$j]['to']          = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers, 'To') );
                    $data[$i]['messages'][$j]['message']     = $_message;
                    $data[$i]['messages'][$j]['attachments'] = $messageAttachments;
                    // $data[$i]['messages'][$j]['all_attachments'] = $messageAttachments;
                    // $attachments = [];
                    $j++;
                }

                $i++;

            }

            // dd($data);
            // dump($attachments);
            // foreach($attachments as $attachment)
            // {
            //     echo '<img src="data:image/png;base64,' . $attachment['data'] . '" />' . '<br>';
            // }

        }
        // foreach($data[0]['messages'] as $val)
        // {
        //     echo base64_decode( self::messageEncode( $val['message'] ) ) .'<br/>-------------------------------------------</br>';
        // }
        // dd();
        // dd($data);
        return $data;

    }

    public static function tmpBuildTicketData($_threadId = null)
    {

        $data    = Array();
        $i       = 0;
        $k       = 0;

        //get active email support address
        $emailSupportAddresses = EmailSupportAddress::active()->get();

        //loop throug hactive email support address and get their email threads
        foreach ( $emailSupportAddresses as $emailSupportAddress )
        {

            // // Get the API client and construct the service object.
            $client  = self::getClient( $emailSupportAddress->id );
            $service = new Google_Service_Gmail($client);
            $user    = 'me';
            
            // if ( $_threadId != null)
            // {
            //     $threads = self::getThread($service, $user, $_threadId);
            //     // dd($threads);
            // }
            // else if ( !self::$isComposedMessage )
            // {
                // $threads = self::tmpListThreads($service, $user);
            $thread                 = self::getThread($service, $user, $_threadId);
            dump($thread->messages[0]);
            // dump($thread->messages[1]);
            // }
            // else
            // {
            //     $threads = self::getThread($service, $user, self::$threadId);
            // }

            // $threads = self::getThread($service, $user, self::$threadId);

            // dump($threads);
            // foreach($threads as $thread) {
                
                $j = 0;
                
                // $thread                     = self::getThread($service, $user, $thread['id']);
                
                $data[$i]['thread_id']      = $thread['id'];
                $data[$i]['history_id']     = $thread['historyId'];
                $data[$i]['subject']        = self::getSubject($thread->messages[0]->payload->headers);
                
                $threadFrom = self::getFrom($thread->messages[0]->payload->headers);
                $threadTo   = self::getFrom($thread->messages[0]->payload->headers, 'To');

                $isEmailSupportAddressFrom  = EmailSupportAddress::active()->whereIn('email', [$threadFrom])->get()->count();
                // $isEmailSupportAddressTo    = EmailSupportAddress::active()->whereIn('email', [$threadTo])->get()->count();

                if ( !self::$isComposedMessage )
                {
                    $data[$i]['requester'] = ( !$isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                    $data[$i]['receiver']  = ( $isEmailSupportAddressFrom ) ? $threadFrom : $threadTo;
                }
                else
                {
                    $data[$i]['requester'] = $threadTo;
                    $data[$i]['receiver']  = $threadFrom;
                }
                
                $lastIndexOfMessage         = count($thread->messages) - 1;
                $data[$i]['snippet']        = ( empty($thread->messages[$lastIndexOfMessage]->snippet) ) ? self::getSubject($thread->messages[0]->payload->headers) : $thread->messages[$lastIndexOfMessage]->snippet;
                $date                       = self::getDate($thread->messages[0]->payload->headers);
                $date                       = date('Y-m-d H:i:s', strtotime($date));
                $data[$i]['date']           = $date;
                
                $messages = $thread->messages;
                $message = $messages[0];
                dd($message);
                $_message = $textHtml = $textPlain = '';
                $existingAttachments = [];
                // foreach ($messages as $key => $message) {

                    $attachments = $messageAttachments = [];
                    $_message    = '';

                    // if(strpos( self::getSubject($thread->messages[0]->payload->headers), '(#87317)' ) !== false){
                    //     dd($message);
                    // }

                    // dump($message);
                    // if($message->id == '17968ed461289d2d')
                    // {
                    //     dump($message);
                    // }
                    
                    // if($message->id == '179405af6e414c60')
                    // {

                        if (isset($message->payload->parts)) {

                            foreach ( $message->payload->parts as $part )
                            {
                                // dump($part);
                                // dump($part->getBody()->getAttachmentId());
                                if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                                {
                                    // dd($part);
                                    // $attachments = self::getAttachments( $message->id, $message->payload->parts, $service ); 
                                    $messageAttachments = self::getAllAttachments( $message->id, $message->payload->parts, $service, true );
                                    // $existingAttachmentsId = self::getAllAttachmentsId( $message->id, $message->payload->parts, $service );
                                    
                                    // dump('--');
                                    // dump($messageAttachments);
                                    // dump('--');
                                    // self::getAllAttachments( $message->id, $message->payload->parts, $service );

                                    // dd($attachments);
                                    // dump('--attachment--');
                                    // dump($part->getBody()->getAttachmentId());
                                    // dump('--end attachment--');
                                    // if( $message->id == '17968ed461289d2d' )
                                    // {
                                    //     dd($attachments);
                                    // }
                                }
                                else
                                {
                                    if ( isset($part->parts) )
                                    {

                                        $getParts = self::getParts($message->payload);

                                        $_data = array();
                                        array_walk_recursive($getParts, function ($value, $key) use (&$_data){
                                            if ($value)
                                                $_data[] = $value;
                                        }, $_data);
                                        $_message = $_data[ count($_data) - 1 ];

                                        $messageAttachments = self::tmpGetAllAttachments( $message->id, $part->parts, $service, true );

                                    }
                                    else
                                    {

                                        //get message that has no attachment - payload parts changes if the message has attachment.

                                        //store each text/plain and text/html contents in a variable
                                        // make a condition if text/html is empy then use text/plain if not empty..
        
                                        if ( empty( $_message ) )
                                        {
        
                                            if ( $part->getMimeType() == 'text/html' && $part->getBody()->data != null )
                                            {
                                                // $textHtml = $part->getBody()->data;
                                                $_message = $part->getBody()->data;
                                                // $mimeType = $part->getMimeType();
                                            }
                                            
                                            if ( $part->getMimeType() == 'text/plain' && $part->getBody()->data != null && empty( $textPlain ) )
                                            {
                                                $textPlainData = trim( base64_decode($part->getBody()->data) );
                                                if ( !empty($textPlainData) )
                                                {
                                                    $textPlain = $part->getBody()->data;
                                                }
                                            }

                                            //add a another variable for plain/text if ever msg is empty
                                        }
                                        
                                    }
                                }
                            }

                        }
                        else
                        {
                            // $_message = $message->payload->body->data;
                            $_message = $message->payload->body->data;
                        }
                        

                    // }

                    //if decoded message html is empty, get text plain.
                    $_messageData = trim( base64_decode($_message) );
                    if ( empty($_messageData) )
                    {
                        $_textPlainData = trim( base64_decode($textPlain) );
                        if ( !empty($_textPlainData) )
                        {
                            $_message = $textPlain;
                        }
                    }

                    $data[$i]['messages'][$j]['message_id']    = $message->id;
                    $_date                                     = date('Y-m-d H:i:s', strtotime(self::getDate($message->payload->headers)));
                    $data[$i]['messages'][$j]['internal_date'] = $_date;
                    // $data[$i]['messages'][$j]['internal_date'] = $message->internalDate;
                    // $data[$i]['messages'][$j]['from']          = self::getFrom($message->payload->headers);
                    $data[$i]['messages'][$j]['from']        = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers) );
                    $data[$i]['messages'][$j]['to']          = str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers, 'To') );
                    $data[$i]['messages'][$j]['message']     = $_message;
                    $data[$i]['messages'][$j]['attachments'] = $messageAttachments;
                    // $data[$i]['messages'][$j]['all_attachments'] = $messageAttachments;
                    // $attachments = [];
                    $j++;
                // }

                $i++;

            // }

            // dd($data);
            // dump($attachments);
            // foreach($attachments as $attachment)
            // {
            //     echo '<img src="data:image/png;base64,' . $attachment['data'] . '" />' . '<br>';
            // }

        }
        // foreach($data[0]['messages'] as $val)
        // {
        //     echo base64_decode( self::messageEncode( $val['message'] ) ) .'<br/>-------------------------------------------</br>';
        // }
        // dd();
        // dd($data);
        // return $data;
        dump($data);

        foreach($data as $thread)
        {

            foreach ($thread['messages'] as $message)
            {

                $arrFileIds = [];
                $attachments = $message['attachments'];
                if ( !empty( $attachments ) )
                {
                    dump('inside not empty attachments');
                    // dd($attachments);
                    $fileIds = [];
                    $fileNames = [];
                    foreach ( $attachments as $key => $file )
                    {
                        dump('inside attachments loop');
                        $_file = \App\File::where('name', $file['filename']);
                        $fileExists = $_file->exists();
                        if ( $file['for_attachment'] == true )
                        {

                            // $createdFile = false;
                            $createdFile = true;
                            if ( !$fileExists )
                            {
                                // $ticketModel = new Ticket;
                                // $createdFile = $ticketModel->saveAttachment( [ 'data' => $file['data'], 'name' => $file['filename'] ] );
                            }

                            //validate if file exists in db, create file if not yet exists and add to array records to avoid duplicates file_ids on each messages
                            if ( $createdFile != false )
                            {
                                dump('innnnnn');
                                dump($file['filename']);
                                dump($file['data']);
                                // $_fileName = $createdFile['filename'];
                                $_fileName = 'testfilename';
                                $ext = explode( '.', $_fileName );
                                $ext = end($ext);
                                
                                // $file = \App\File::create([
                                //     'name'      => $_fileName,
                                //     'extension' => $ext,
                                //     'path'      => $createdFile['path'],
                                // ]);

                                // array_push($fileIds, $file->id);
                                array_push($fileNames, $file['filename']);

                            }
                            else if ( !in_array($file['filename'], $fileNames) )
                            {
                                array_push($fileIds, $_file->first()->id);
                                array_push($fileNames, $file['filename']);
                            }

                        }

                    }

                    // Message::where('message_id', $message['message_id'])->update(['file_ids' => json_encode($fileIds)]);
                    array_push($arrFileIds, ['message_id' => $message['message_id'], 'file_ids' => json_encode($fileIds)]);

                }

            }

        }

        dump($fileNames);
        dd($arrFileIds);


    }

    public static function getParts($obj) {
        $arr = [];
        foreach ($obj->parts as $part) {
            if(count($part) > 0) $arr[$part->body->data] = self::getParts($part);
            else $arr[] = $part->body->data;
        } 

        return empty($arr) ? NULL : $arr; 
    }

    public static function threadSync($threadId)
    {

        if ( !empty($threadId) )
        {

            $data    = Array();
            $i       = 0;
            $k       = 0;

            //get active email support address
            $emailSupportAddresses = EmailSupportAddress::active()->get();

            //loop throug hactive email support address and get their email threads
            foreach ( $emailSupportAddresses as $emailSupportAddress )
            {

                // // Get the API client and construct the service object.
                $client  = self::getClient( $emailSupportAddress->id );
                $service = new Google_Service_Gmail($client);
                $user    = 'me';
                
                $thread = self::getThread($service, $user, $threadId);

                // foreach($threads as $thread) {

                    $j = 0;
                    
                    // $thread                     = self::getThread($service, $user, $thread['id']);
                    
                    $data[$i]['thread_id']      = $thread['id'];
                    
                    $lastIndexOfMessage         = count($thread->messages) - 1;
                    
                    $messages = $thread->messages;
                    // dd($messages);
                    $_message = $textHtml = $textPlain = '';
                    $existingAttachments = [];
                    foreach ($messages as $key => $message) {

                        $attachments = $messageAttachments = [];
                        $_message    = '';

                        if (isset($message->payload->parts)) {

                            foreach ( $message->payload->parts as $part )
                            {

                                if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                                {

                                    $messageAttachments = self::getAllAttachments( $message->id, $message->payload->parts, $service, true );

                                }
                                else
                                {
                                    if ( isset($part->parts) )
                                    {

                                        $getParts = self::getParts($message->payload);
                                        $_data = array();
                                        array_walk_recursive($getParts, function ($value, $key) use (&$_data){
                                            if ($value)
                                                $_data[] = $value;
                                        }, $_data);
                                        $_message = $_data[ count($_data) - 1 ];

                                    }
                                    else
                                    {

                                        //get message that has no attachment - payload parts changes if the message has attachment.

                                        //store each text/plain and text/html contents in a variable
                                        // make a condition if text/html is empy then use text/plain if not empty..
        
                                        if ( empty( $_message ) )
                                        {
        
                                            if ( $part->getMimeType() == 'text/html' && $part->getBody()->data != null )
                                            {
                                                $_message = $part->getBody()->data;
                                            }
                                            
                                            if ( $part->getMimeType() == 'text/plain' && $part->getBody()->data != null && empty( $textPlain ) )
                                            {
                                                $textPlainData = trim( base64_decode($part->getBody()->data) );
                                                if ( !empty($textPlainData) )
                                                {
                                                    $textPlain = $part->getBody()->data;
                                                }
                                            }

                                            //add a another variable for plain/text if ever msg is empty
                                        }
                                        
                                    }
                                }
                            }

                        }
                        else
                        {
                            $_message = $message->payload->body->data;
                        }

                        //if decoded message html is empty, get text plain.
                        $_messageData = trim( base64_decode($_message) );
                        if ( empty($_messageData) )
                        {
                            $_textPlainData = trim( base64_decode($textPlain) );
                            if ( !empty($_textPlainData) )
                            {
                                $_message = $textPlain;
                            }
                        }

                        $data[$i]['messages'][$j]['message_id']  = $message->id;
                        $data[$i]['messages'][$j]['message']     = $_message;
                        $data[$i]['messages'][$j]['attachments'] = $messageAttachments;
                        $j++;
                    }

                    // $i++;

                // }

            }

            $threads =  $data;
            // dd($threads);

            /*
             * update ticket/thread messages
            */

            // $createdTicketId = 0;
            
            // DB::beginTransaction();

            // try {

                foreach ($threads as $thread) {

                    foreach ($thread['messages'] as $message)
                    {

                        if ( self::messageExists($message['message_id']) === true )
                        {

                            // $__ticketId = Ticket::where('thread_id', $thread['thread_id'])->first()->id;

                            // $updateMessage = Message::where('message_id', $message['message_id'])->first();

                            // $updateMessage->message = self::messageEncode($message['message'], $message['attachments']);

                            // $updateMessage->save();

                            // dump($message['message_id'], self::messageEncode($message['message'], $message['attachments']));

                            $d = base64_decode( self::messageEncode($message['message'], $message['attachments']) );

                            var_dump(mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'ISO-8859-5']));
                            var_dump(mb_detect_encoding($str, ['ISO-8859-1', 'ISO-8859-5', 'UTF-8']));
                            var_dump(mb_detect_encoding($str, ['ISO-8859-5', 'UTF-8', 'ISO-8859-1']));

                        }

                    }

                }


            //     DB::commit();

            // }
            // catch (exception $e)
            // {
            //     DB::rollback();
            // }

        }

    }

    public static function threadDataDump($threadId)
    {
        $data    = Array();
        $i       = 0;
        $k       = 0;

        //get active email support address
        $emailSupportAddresses = EmailSupportAddress::active()->get();

        //loop throug hactive email support address and get their email threads
        foreach ( $emailSupportAddresses as $emailSupportAddress )
        {

            // // Get the API client and construct the service object.
            $client  = self::getClient( $emailSupportAddress->id );
            $service = new Google_Service_Gmail($client);
            $user    = 'me';

            /*$attachment = $service->users_messages_attachments->get('me', '18658f047ee09b5c', 'ANGjdJ9QZT0YAgHnm0197sh-cJaHfWraBfCVES0cnST9YDCt-7Rg-P5oLIrHH65M2tqmuuwjHR8ZeQznB69knTuZxQs_FIs8rW5jzBwGNKduuN1ssT4ZUKcctF7MnONHdhZuNaB2TaxF1vfC6TBDkGaKT5NhnuaV61GDjx7b0XXuePfEwUNrmpRIFaiLyTjBCw-fPOLGkw690BjmnyRDLiRlbrVYpom93kWqxtQCHgekyHPeVY1iUBOmDURJLiVC6Wa-RnvkePHVMeHdqNfzLf-CFN1f4hNbNLdcSyOpO2mLGSNav8KXwCD_EZYidizyKvPoIAYIiYSUSaR-KbveB9ohNSCXYFC6CrWVLoJ7FqrP4Ayz0mgfe3B9dnPjNpxzqzUY85QFMj7yZ8psQ8U1');

            $data = str_replace(array('-','_'),array('+','/'), $attachment->data);
            $mod4 = strlen($data) % 4;
            if ($mod4) {
                $data .= substr('====', $mod4);
            }
            dd($data);*/
            

            $thread                 = self::getThread($service, $user, $threadId);

            return $thread;

            $data[$i]['thread_id']  = $thread['id'];
            $data[$i]['history_id'] = $thread['historyId'];
            $data[$i]['subject']    = self::getSubject($thread->messages[0]->payload->headers);
            $data[$i]['requester']  = (self::$isComposedMessage) ? self::getFrom($thread->messages[0]->payload->headers, 'To') : self::getFrom($thread->messages[0]->payload->headers);
            //if true we use $receiver since there is no payload data yet on composed message
            $data[$i]['receiver']   = (self::$isComposedMessage) ? self::getFrom($thread->messages[0]->payload->headers) : self::getFrom($thread->messages[0]->payload->headers, 'To');
            $lastIndexOfMessage     = count($thread->messages) - 1;
            $data[$i]['snippet']    = $thread->messages[$lastIndexOfMessage]->snippet;
            $data[$i]['date']       = self::getDate($thread->messages[0]->payload->headers);
            

                dump('-thread-');
                dump($thread);

                dump('-messages-');
                // dump($thread->messages);
                foreach($thread->messages as $_key => $_message)
                {
                    dump($_message);
                }

                dump('-messages encode-');
                dd(self::messageEncode('PGRpdj48YnI-PC9kaXY-PGRpdj48YnI-PGRpdiBjbGFzcz0iZ21haWxfcXVvdGUiPjxkaXYgZGlyPSJsdHIiIGNsYXNzPSJnbWFpbF9hdHRyIj5PbiBTdW4sIDI0IE9jdCAyMDIxIGF0IDEyOjUyIHBtLCBGcmFua2llcyBBdXRvIEVsZWN0cmljcyAmbHQ7PGEgaHJlZj0ibWFpbHRvOnN1cHBvcnRAZnJhbmtpZXNhdXRvZWxlY3RyaWNzLmNvbS5hdSI-c3VwcG9ydEBmcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1PC9hPiZndDsgd3JvdGU6PGJyPjwvZGl2PjxibG9ja3F1b3RlIGNsYXNzPSJnbWFpbF9xdW90ZSIgc3R5bGU9Im1hcmdpbjowcHggMHB4IDBweCAwLjhleDtib3JkZXItbGVmdC13aWR0aDoxcHg7Ym9yZGVyLWxlZnQtc3R5bGU6c29saWQ7cGFkZGluZy1sZWZ0OjFleDtib3JkZXItbGVmdC1jb2xvcjpyZ2IoMjA0LDIwNCwyMDQpIj48dT48L3U-DQoNCiAgDQogIA0KDQo8ZGl2IGxhbmc9ImVuLXVzIiBzdHlsZT0ibWFyZ2luOjBweDtwYWRkaW5nOjBweDt3aWR0aDoxMDAlIWltcG9ydGFudCI-DQogIDxkaXYgc3R5bGU9InBhZGRpbmc6MTBweDtsaW5lLWhlaWdodDoxOHB4O2ZvbnQtZmFtaWx5OiZxdW90O0x1Y2lkYSBHcmFuZGUmcXVvdDssVmVyZGFuYSxBcmlhbCxzYW5zLXNlcmlmO2ZvbnQtc2l6ZToxMnB4O2NvbG9yOnJnYig2OCw2OCw2OCkiPg0KICAgIDxkaXYgc3R5bGU9ImZvbnQtZmFtaWx5OiZxdW90O0x1Y2lkYSBHcmFuZGUmcXVvdDssVmVyZGFuYSxBcmlhbCxzYW5zLXNlcmlmO2NvbG9yOnJnYigxODEsMTgxLDE4MSkiPiMjLSBQbGVhc2UgdHlwZSB5b3VyIHJlcGx5IGFib3ZlIHRoaXMgbGluZSAtIyM8L2Rpdj4NCiAgICA8cCBkaXI9Imx0ciIgc3R5bGU9ImZvbnQtZmFtaWx5OiZxdW90O0x1Y2lkYSBHcmFuZGUmcXVvdDssVmVyZGFuYSxBcmlhbCxzYW5zLXNlcmlmIj5Zb3VyIHJlcXVlc3QgKDE3NzAxOSkgaGFzIGJlZW4gcmVjZWl2ZWQgYW5kIGlzIGJlaW5nIHJldmlld2VkIGJ5IG91ciBzdXBwb3J0IHN0YWZmLjwvcD48cCBkaXI9Imx0ciIgc3R5bGU9ImZvbnQtZmFtaWx5OiZxdW90O0x1Y2lkYSBHcmFuZGUmcXVvdDssVmVyZGFuYSxBcmlhbCxzYW5zLXNlcmlmIj5UbyBhZGQgYWRkaXRpb25hbCBjb21tZW50cywgcmVwbHkgdG8gdGhpcyBlbWFpbC48L3A-DQogIDwvZGl2Pg0KICA8ZGl2IHN0eWxlPSJwYWRkaW5nOjEwcHg7bGluZS1oZWlnaHQ6MThweDtmb250LWZhbWlseTomcXVvdDtMdWNpZGEgR3JhbmRlJnF1b3Q7LFZlcmRhbmEsQXJpYWwsc2Fucy1zZXJpZjtmb250LXNpemU6MTJweDttYXJnaW46MTBweCAwcHggMTRweDtib3JkZXItdG9wLXdpZHRoOjFweDtib3JkZXItdG9wLXN0eWxlOnNvbGlkO2JvcmRlci10b3AtY29sb3I6cmdiKDIzOCwyMzgsMjM4KTtjb2xvcjpyZ2IoMTcwLDE3MCwxNzApIj4NCiAgICBUaGlzIGVtYWlsIGlzIGEgc2VydmljZSBmcm9tIEZyYW5raWVzIEF1dG8gRWxlY3RyaWNzLiBEZWxpdmVyZWQgYnkgPGEgaHJlZj0iaHR0cHM6Ly93d3cuemVuZGVzay5jb20vc3VwcG9ydC8_dXRtX2NhbXBhaWduPXRleHQmYW1wO3V0bV9jb250ZW50PUZyYW5raWVzK0F1dG8rRWxlY3RyaWNzJmFtcDt1dG1fbWVkaXVtPXBvd2VyZWRieXplbmRlc2smYW1wO3V0bV9zb3VyY2U9ZW1haWwtbm90aWZpY2F0aW9uIiBzdHlsZT0iZm9udC1mYW1pbHk6JnF1b3Q7THVjaWRhIEdyYW5kZSZxdW90OyxWZXJkYW5hLEFyaWFsLHNhbnMtc2VyaWY7Y29sb3I6YmxhY2siIHRhcmdldD0iX2JsYW5rIj5aZW5kZXNrPC9hPg0KICA8L2Rpdj4NCjxzcGFuIHN0eWxlPSJjb2xvcjpyZ2IoMjU1LDI1NSwyNTUpIiBhcmlhLWhpZGRlbj0idHJ1ZSI-WzVMM09NUC1FWEwyXTwvc3Bhbj4NCg0KPC9kaXY-DQo8L2Jsb2NrcXVvdGU-PC9kaXY-PC9kaXY-SSBoYXZlIGJlZW4gd2FpdGluZyB0byBoZXJlIGFib3V0IG15IGFtcCBhbmQgbm8gb25lIGhhcyBnb3QgYmFjayB0byBtZcKgDQo=', null));
               /* dump('-messages-');
                dd($thread->messages);
                // dump('-----');
                foreach ( $thread->messages as $key => $message )
                {

                    // if($message->id != '17968ed461289d2d') { dd($message); }

                    // dump($message);
                    $messageText = '';
                    $textHtml    = $textPlain = '';
                    $mimeType    = '';

                    if ( isset( $message->payload->parts ) )
                    {
                        // dump(self::getParts($message->payload));
                        // dump($message->payload->parts);
                        // dd(1);


                        foreach ( $message->payload->parts as $part )
                        {

                            if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                            {
                                $attachments = self::getAttachments( $message->id, $message->payload->parts, $service );
                                dump($attachments);
                                // dump('-with attachments-');
                                // dump($attachments);
                                // dump('--');
    
                            }
                            else
                            {

                                if ( isset($part->parts) )
                                {

                                    $getParts = self::getParts($message->payload);
                                    $data = array();
                                    array_walk_recursive($getParts, function ($value, $key) use (&$data){
                                        if ($value)
                                            $data[] = $value;
                                    }, $data);
                                    $messageText = $data[ count($data) - 1 ];

                                    // dump($messageText);

                                    // dd( $getParts[count($getParts) - 1] );
                                    // foreach($part->parts as $_part)
                                    // {
                                    //     //store each text/plain and text/html contents in a variable
                                    //     // make a condition if text/html is empy then use text/plain if not empty..
    
                                    //     if ( $_part->getMimeType() == 'text/html' && !empty( $_part->getBody()->data ) && empty( $textHtml ) )
                                    //     {
                                    //         $textHtml = base64_decode( self::messageEncode($_part->getBody()->data) );
                                    //         $mimeType = $_part->getMimeType();
                                    //     }
                                    //     else if ( $_part->getMimeType() == 'text/plain' && !empty( $_part->getBody()->data ) && empty( $textPlain ) )
                                    //     {
                                    //         $textPlain = base64_decode( self::messageEncode($_part->getBody()->data) );
                                    //         $mimeType = $_part->getMimeType();
                                    //     }
                                        
                                    // }

                                    // $messageText = ( !empty( $textHtml ) ) ? $textHtml : $textPlain;
    
                                }
                                else
                                {
    
                                    //store each text/plain and text/html contents in a variable
                                    // make a condition if text/html is empy then use text/plain if not empty..
                                    // dump($parts);
                                    if ( empty( $messageText ) )
                                    {
    
                                        if ( $part->getMimeType() == 'text/html' && !empty( $part->getBody()->data ) )
                                        {
                                            // $messageText = base64_decode( self::messageEncode($part->getBody()->data) );
                                            $messageText = $part->getBody()->data;
                                            $mimeType = $part->getMimeType();
                                        }
                                        else if ( $part->getMimeType() == 'text/plain' && !empty( $part->getBody()->data ) )
                                        {
                                            // $messageText = base64_decode( self::messageEncode($part->getBody()->data) );
                                            $messageText = $part->getBody()->data;
                                            $mimeType = $part->getMimeType();
                                        }
    
                                    }
    
                                }
    
                            } 

                        }
                    }
                    else
                    {
                        // $messageText = base64_decode( self::messageEncode($message->payload->body->data) );
                        $messageText = $message->payload->body->data;
                    }
                    
                    dd();
                    // dump($messageText);
                    // $d = base64_decode( self::messageEncode($messageText, $attachments) );
                    // echo $d;
                }
                dd(1);
*/
                
                $attachment = $service->users_messages_attachments->get('me', '17ac3a2f3b67e8bc', 'ANGjdJ8eeghfWqyp4KOR4TfOK_bKkRkSt6WXqRMoHfyyniGOwikIaELdFsPMd0r3F8QkHy-0a1e_W5jseRS3OcEYIgkkl_wub0MxIZY3ufG-6oPN7VLljywVK1YDvmbhF6SJm3RPOBhdz8kr0Lt5ml8ziPiQjoO9m5crhkdy63fn5tqEf72CBDPoKqnMpOdg_1KdmETAK7nj_NlhTFewbAXxJ7ldgJcBBwyFTfNsCWg7oJQwyxAK0UdqXzD5CJoamGC4Up75H7zh0C2bp8Ej8qjT5ZYQ0nnf_KWyXA3yBjakpKxVj0TKwD3qNmKlnGmxKHcrAoLhnIIpssuTfmDRKr9oPayf22wmfLsVniuRoGDSgXVhTpVQ3a9_B5w8R4wREcIC_Da6TaplIlvzQu2D');
                $data = str_replace(array('-','_'),array('+','/'), $attachment->data);
                $mod4 = strlen($data) % 4;
                if ($mod4) {
                    $data .= substr('====', $mod4);
                }

                return [
                        'attachment_id'  => '17CBF176-DBF7-4120-8C0F-CCC1B19A42A1-L0-001',
                        'message_id'     => '17ac3a2f3b67e8bc',
                        'filename'       => 'image0.jpeg',
                        'mimeType'       => 'image/jpeg',
                        'data'           => $data,
                        'for_attachment' => true, // add for attachment for additional identifier since gmails also adding previous messages attachments which is not needed for the subsequent messages.
                    ];

                $_msg = '';
                $attachedFiles = [];

                if (isset($message->payload->parts)) {

                    foreach ( $message->payload->parts as $part )
                    {

                        if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                        {
                            $attachedFiles = self::getAllAttachments( $message->id, $message->payload->parts, $service, true );
                        }
                    }

                }

                return $attachedFiles;

                /*foreach( $thread->messages as $key => $message )
                {
                    // if ( $key == 0 )
                    // {
                    //     $threadFrom = self::getFrom($thread->messages[0]->payload->headers);
                    //     $threadTo   = self::getFrom($thread->messages[0]->payload->headers, 'To');
                    //     dump($threadFrom);
                    //     dump($threadTo);
                    // }

                    // if ( $message->id == '1793621f8e041ff1' || $message->id == '17936097b7238951' || $message->id == '1793603b930ab090' || $message->id == '17935f19922a7be4' )
                    // if ( $key > 9 )
                    // if ( $message->id == '178c87e31f101a40' ) // marcelo
                    // {
                        // dd($thread->messages);
                        // $_msg = $message;
                        dump('--message--');
                        dump($message);
                        dump('-----------');

                        $attachments = self::getAttachments( $message->id, $message->payload->parts, $service );
                        // dump('--attachments--');
                        // dump($attachments);
                        // dump('--end attachments--');
    
                        foreach(  $message->payload->parts as $part )
                        {
                            if ( !empty($part->filename) )
                            {
                                
                                $attachments = $service->users_messages_attachments->get('me', $message->id, $part->body->attachmentId);

                                // dump('1');
                                // dump($attachments);
                                // dump('2');
                                // dump('--attached: '.$part->filename.'--');
                                // dump(strtr($attachments->data, '-_', '+/')); //strstr for image
                                // dump($attachments->data);
                                // dump('-----------');
                                
                                // return strtr($attachments->getData(), array('-' => '+', '_' => '/'));
    
                                // dd($attachments->getData());
                                $data = str_replace(array('-','_'),array('+','/'), $attachments->getData());
                                $mod4 = strlen($data) % 4;
                                if ($mod4) {
                                    $data .= substr('====', $mod4);
                                }
                                
                                // return ['data' => $data, 'name' => $part->filename];
                                $attachmentId    = self::getAttachmentId($part->headers);

                                if ( !empty($attachedFiles) )
                                {
                                    $filenames = array_column($attachedFiles, 'filename');
                                    
                                    if ( !in_array($part->filename, $filenames) ) // if file is not yet  recorded
                                    {
                                        $attachedFiles[] = ['data' => $data, 'filename' => $part->filename, 'attachment_id' => $attachmentId];
                                    }
                                }
                                else
                                {
                                    $attachedFiles[] = ['data' => $data, 'filename' => $part->filename, 'attachment_id' => $attachmentId];
                                }
    
                            }
                        }

                        return $attachedFiles;
                        
                    // }
                }*/

                // return ['attachmentData' => $attachments->data];
            // dd();
                
            $messages = $thread->messages;
            $_message = '';

            foreach ($messages as $key => $message) {

                if (isset($message->payload->parts)) {

                    if ( isset( $message->payload->parts[1]->body->data ) && $message->payload->parts[1]->body->data != null )
                    {
                        $_message = $message->payload->parts[1]->body->data;
                    }
                    else if ( isset( $message->payload->parts[0]->body->data ) && $message->payload->parts[0]->body->data != null )
                    {
                        $_message = $message->payload->parts[0]->body->data;
                    }

                }
                else
                {
                    $_message = $message->payload->body->data;
                }

                // dump($message->id); // message id
                // dump($message->internalDate); //message internal date
                // dump(str_replace( ['"', "'"], ['', ''], self::getFrom($message->payload->headers) )); // from
                // dump($_message); // message
            }
            
        }
    }

    public static function getAttachments($message_id, $parts, $service)
    {
        $attachments = [];

        foreach ($parts as $part)
        {
            if (!empty($part->body->attachmentId))
            {

                $attachment = $service->users_messages_attachments->get('me', $message_id, $part->body->attachmentId);

                $data = str_replace(array('-','_'),array('+','/'), $attachment->data);
                $mod4 = strlen($data) % 4;
                if ($mod4) {
                    $data .= substr('====', $mod4);
                }
                
                $attachmentId = self::getAttachmentId($part->headers);

                if ( !empty( $attachments ) )
                {
                    $filenames = array_column($attachments, 'filename');

                    if ( !in_array( $part->filename, $filenames ) ) //prevent duplicate attachments
                    {
                        $attachments[] = [
                            'attachment_id' => $attachmentId,
                            'message_id'    => $message_id,
                            'filename'      => $part->filename,
                            'mimeType'      => $part->mimeType,
                            'data'          => $data
                            // 'data'     => strtr($attachment->data, '-_', '+/')
                        ];
                    }
                }
                else
                {
                    $attachments[] = [
                        'attachment_id' => $attachmentId,
                        'message_id'    => $message_id,
                        'filename'      => $part->filename,
                        'mimeType'      => $part->mimeType,
                        'data'          => $data
                        // 'data'     => strtr($attachment->data, '-_', '+/')
                    ];
                }

            }
            // else if (!empty($part->parts))
            // {
            //     $attachments = array_merge($attachments, self::getAttachments($message_id, $part->parts, $service));
            // }
        }

        return $attachments;

    }

    public static function getAllAttachments($message_id, $parts, $service, $forAttachment)
    {
        $attachments = [];

        foreach ($parts as $part)
        {
            if (!empty($part->body->attachmentId))
            {

                $attachment = $service->users_messages_attachments->get('me', $message_id, $part->body->attachmentId);
                // dump(000);
                // dd($attachment);
                $data = str_replace(array('-','_'),array('+','/'), $attachment->data);
                $mod4 = strlen($data) % 4;
                if ($mod4) {
                    $data .= substr('====', $mod4);
                }
                
                $attachmentId = self::getAttachmentId($part->headers);

                // if ( !empty( $attachments ) )
                // {
                //     $filenames = array_column($attachments, 'filename');

                //     if ( !in_array( $part->filename, $filenames ) ) //prevent duplicate attachments
                //     {
                //         $attachments[] = [
                //             'attachment_id'  => $attachmentId,
                //             'message_id'     => $message_id,
                //             'filename'       => $part->filename,
                //             'mimeType'       => $part->mimeType,
                //             'data'           => $data,
                //             'for_attachment' => true,
                //         ];
                //     }
                // }
                // else
                // {

                    if( !empty($part->filename) )
                    {

                        $attachments[] = [
                            'attachment_id'  => $attachmentId,
                            'message_id'     => $message_id,
                            'filename'       => $part->filename,
                            'mimeType'       => $part->mimeType,
                            'data'           => $data,
                            'for_attachment' => $forAttachment, // add for attachment for additional identifier since gmails also adding previous messages attachments which is not needed for the subsequent messages.
                        ];

                    }

                // }

            }
            else if (!empty($part->parts))
            {
                $attachments = array_merge($attachments, self::getAllAttachments($message_id, $part->parts, $service, false));
            }
        }

        return $attachments;

    }

    public static function tmpGetAllAttachments($message_id, $parts, $service, $forAttachment)
    {
        $attachments = [];

        foreach ($parts as $part)
        {
            if (!empty($part->body->attachmentId))
            {

                $attachment = $service->users_messages_attachments->get('me', $message_id, $part->body->attachmentId);
                // dump(000);
                // dd($attachment);
                $data = str_replace(array('-','_'),array('+','/'), $attachment->data);
                $mod4 = strlen($data) % 4;
                if ($mod4) {
                    $data .= substr('====', $mod4);
                }
                
                $attachmentId = self::tmpGetAttachmentId($part->headers);

                // if ( !empty( $attachments ) )
                // {
                //     $filenames = array_column($attachments, 'filename');

                //     if ( !in_array( $part->filename, $filenames ) ) //prevent duplicate attachments
                //     {
                //         $attachments[] = [
                //             'attachment_id'  => $attachmentId,
                //             'message_id'     => $message_id,
                //             'filename'       => $part->filename,
                //             'mimeType'       => $part->mimeType,
                //             'data'           => $data,
                //             'for_attachment' => true,
                //         ];
                //     }
                // }
                // else
                // {
                    $attachments[] = [
                        'attachment_id'  => $attachmentId,
                        'message_id'     => $message_id,
                        'filename'       => $part->filename,
                        'mimeType'       => $part->mimeType,
                        'data'           => $data,
                        'for_attachment' => $forAttachment, // add for attachment for additional identifier since gmails also adding previous messages attachments which is not needed for the subsequent messages.
                    ];
                // }

            }
            else if (!empty($part->parts))
            {
                $attachments = array_merge($attachments, self::getAllAttachments($message_id, $part->parts, $service, false));
            }
        }

        return $attachments;

    }

    public static function getAllAttachmentsId($message_id, $parts, $service)
    {
        $attachments = [];

        foreach ($parts as $part)
        {
            if (!empty($part->body->attachmentId))
            {

                $attachment = $service->users_messages_attachments->get('me', $message_id, $part->body->attachmentId);
                
                $attachmentId = self::getAttachmentId($part->headers);

                if ( !empty( $attachments ) )
                {
                    $filenames = array_column($attachments, 'filename');

                    if ( !in_array( $part->filename, $filenames ) ) //prevent duplicate attachments
                    {
                        $attachments[] = [
                            'attachment_id' => $attachmentId,
                            'filename'      => $part->filename,
                        ];
                    }
                }
                else
                {
                    $attachments[] = [
                        'attachment_id' => $attachmentId,
                        'filename'      => $part->filename,
                    ];
                }

            }
            else if (!empty($part->parts))
            {
                $attachments = array_merge($attachments, self::getAttachments($message_id, $part->parts, $service));
            }
        }
        // dd($attachments);
        $attachmentIds = array_column($attachments, 'attachment_id');
        return $attachmentIds;

    }
    
    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    public static function getClient($suffix = '')
    {

        //condition for send message. to get proper suffix for credentials.json

        //
        
        $suffix = (string)$suffix;

        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes([Google_Service_Gmail::GMAIL_READONLY, Google_Service_Gmail::GMAIL_SETTINGS_BASIC]);
        // $client->setScopes(Array(Google_Service_Gmail::GMAIL_READONLY,'https://mail.google.com','https://www.googleapis.com/auth/gmail.settings.basic'));

        // $path = 'credentials/credentials'.$suffix.'.json';
        $path = 'credentials/credentials.json';

        $client->setAuthConfig( Storage::path($path) );
        // $client->setAuthConfig('credentials'.$suffix.'.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.

        $_path = 'tokens/token'.$suffix.'.json';
        // $tokenPath = 'token'.$suffix.'.json';
        $tokenPath = Storage::path($_path);
        // $tokenPath = 'token'.$suffix.'.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }
        
        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    public static function getEmailSpamFilter() //
    {
        $spamFilters = SpamFilter::emails()->get('keyword')->toArray();
        $spamFilters = array_column($spamFilters, 'keyword');
        // $spamFilters = array_map(function($value) { return '!from:'.$value; }, $spamFilters);

        return implode(' ', array_map(function($value) { return '!from:'.$value; }, $spamFilters));
    }


    /**
     * Get all Threads in the user's mailbox.
     *
     * @param  Google_Service_Gmail $service Authorized Gmail API instance.
     * @param  string $userId User's email address. The special value 'me'
     * can be used to indicate the authenticated user.
     * @return array Array of Threads.
     */
    public static function listThreads($service, $userId) {

        $threads = array();
        $pageToken = NULL;

        do {
            try {

                $spamFilter = self::getEmailSpamFilter();

                // $opt_param = array('q' => '!in:chat !in:draft from:rodney@frankiesautoelectrics.com.au after:2020/07/01');
                if ( !self::$isComposedMessage )
                {
                    $opt_param = array('q' => '!in:chat !in:draft '.$spamFilter.' !from:ricardoramos@lenidy.com !from:chesterjames@checkmed.ir !from:ramoncastro@caribbeanrcoevo.com -themanshake -pizza -nachos -domino -domino\'s -eMagazine after:'.strtotime(date('Y-m-d H:i:s', strtotime('-60 minutes'))).'');
                    // $opt_param = array('q' => '!in:chat !in:draft '.$spamFilter.' !from:ricardoramos@lenidy.com !from:chesterjames@checkmed.ir !from:ramoncastro@caribbeanrcoevo.com -themanshake -pizza -nachos -domino -domino\'s -eMagazine after:'.strtotime(date('Y-m-d H:i:s', strtotime('-2 days'))).'');
                    // $opt_param = array('q' => '!in:chat !in:draft -themanshake -pizza -nachos -domino -domino\'s after:2022/05/05 before:2022/05/10');
                    // $opt_param = array('q' => '!in:chat !in:draft -twitter -pizza -nachos -domino -domino\'s after:'.strtotime(date('Y-m-d H:i:s', strtotime('-4 days'))).'');
                    // $opt_param = array('q' => '!in:chat !in:draft after:'.strtotime(date('Y-m-d H:i:s', strtotime('-10 minutes'))).'');
                }
                else
                {
                    //if action is agent composed a message, will then get the recent messages
                    $opt_param = array('q' => '!in:chat !in:draft !from:ricardoramos@lenidy.com !from:chesterjames@checkmed.ir !from:ramoncastro@caribbeanrcoevo.com -pizza -nachos -domino -domino\'s -eMagazine after:'.strtotime(date('Y-m-d H:i:s', strtotime('-5 minutes'))).'');
                }
                // $opt_param = array();
                if ($pageToken) { 
                    $opt_param['pageToken'] = $pageToken;
                    // $opt_param['format'] = 'FULL';
                }

                $threadsResponse = $service->users_threads->listUsersThreads($userId, $opt_param);
                if ($threadsResponse->getThreads()) {
                    $threads = array_merge($threads, $threadsResponse->getThreads());
                    $pageToken = $threadsResponse->getNextPageToken();
                }

            } catch (Exception $e) {
                print 'An error occurred: ' . $e->getMessage();
                $pageToken = NULL;
            }

        } while ($pageToken);
    
        // foreach ($threads as $thread) {
        //     print 'Thread with ID: ' . $thread->getId() . '<br/>';
        // }
    
        return $threads;

    }

    public static function listThreadsToUpdateMessages($service, $userId) {

        $threads = array();
        $pageToken = NULL;

        do {
            try {

                
                // $opt_param = array('q' => '!in:chat !in:draft -themanshake -pizza -nachos -domino -domino\'s after:'.strtotime(date('Y-m-d H:i:s', strtotime('-60 minutes'))).'');
                $opt_param = array('q' => '!in:chat !in:draft -themanshake -pizza -nachos -domino -domino\'s after:2022/05/31 before:2022/06/02');
                // $opt_param = array('q' => '!in:chat !in:draft -twitter -pizza -nachos -domino -domino\'s after:'.strtotime(date('Y-m-d H:i:s', strtotime('-4 days'))).'');
                // $opt_param = array('q' => '!in:chat !in:draft after:'.strtotime(date('Y-m-d H:i:s', strtotime('-10 minutes'))).'');
                
                // $opt_param = array();
                if ($pageToken) { 
                    $opt_param['pageToken'] = $pageToken;
                    // $opt_param['format'] = 'FULL';
                }

                $threadsResponse = $service->users_threads->listUsersThreads($userId, $opt_param);
                if ($threadsResponse->getThreads()) {
                    $threads = array_merge($threads, $threadsResponse->getThreads());
                    $pageToken = $threadsResponse->getNextPageToken();
                }

            } catch (Exception $e) {
                print 'An error occurred: ' . $e->getMessage();
                $pageToken = NULL;
            }

        } while ($pageToken);
    
        // foreach ($threads as $thread) {
        //     print 'Thread with ID: ' . $thread->getId() . '<br/>';
        // }
    
        return $threads;

    }

    public static function tmpListThreads($service, $userId) {

        $threads = array();
        $pageToken = NULL;

        do {
            try {

                // $opt_param = array('q' => '!in:chat !in:draft from:rodney@frankiesautoelectrics.com.au after:2020/07/01');
                if ( !self::$isComposedMessage )
                {
                    // $opt_param = array('q' => '!in:chat !in:draft -twitter -pizza -nachos -domino after:'.date('Y/m/d h:i:s', strtotime('-120 minutes')).'');
                    $opt_param = array('q' => 'from:awoodley@agcoombs.com.au !in:chat !in:draft -pizza -nachos -domino -domino\'s after:'.strtotime(date('Y-m-d H:i:s', strtotime('-3 days'))).'');
                    // $opt_param = array('q' => '!in:chat !in:draft -twitter -pizza -nachos -domino -domino\'s after:'.strtotime(date('Y-m-d H:i:s', strtotime('-4 days'))).'');
                    // $opt_param = array('q' => '!in:chat !in:draft after:'.strtotime(date('Y-m-d H:i:s', strtotime('-10 minutes'))).'');
                }
                else
                {
                    //if action is agent composed a message, will then get the recent messages
                    $opt_param = array('q' => '!in:chat !in:draft -twitter -pizza -nachos -domino -domino\'s after:'.strtotime(date('Y-m-d H:i:s', strtotime('-5 minutes'))).'');
                }
                // $opt_param = array();
                if ($pageToken) { 
                    $opt_param['pageToken'] = $pageToken;
                    // $opt_param['format'] = 'FULL';
                }

                $threadsResponse = $service->users_threads->listUsersThreads($userId, $opt_param);
                if ($threadsResponse->getThreads()) {
                    $threads = array_merge($threads, $threadsResponse->getThreads());
                    $pageToken = $threadsResponse->getNextPageToken();
                }

            } catch (Exception $e) {
                print 'An error occurred: ' . $e->getMessage();
                $pageToken = NULL;
            }

        } while ($pageToken);
    
        // foreach ($threads as $thread) {
        //     print 'Thread with ID: ' . $thread->getId() . '<br/>';
        // }
    
        return $threads;

    }

    /**
     * Get Thread with given ID.
     *
     * @param  Google_Service_Gmail $service Authorized Gmail API instance.
     * @param  string $userId User's email address. The special value 'me'
     * can be used to indicate the authenticated user.
     * @param  string $threadId ID of Thread to get.
     * @return Google_Service_Gmail_Thread Retrieved Thread.
     */
    public static function getThread($service, $userId, $threadId) {

        try {

            $thread = $service->users_threads->get($userId, $threadId);
            // if (\Auth::id() == 1 )
            // {
            //     $thread = $service->users_threads->get('me', '18b8d8cd7c809b5e');
            //     logger(['GETTHREAD', 'me', '18b8d8cd7c809b5e', $thread]);
            // }
            // $messages = $thread->getMessages();
            // $msgCount = count($messages);
            // print 'Number of Messages in the Thread: ' . $msgCount;
            return $thread;
        } catch (Exception $e){
            // print 'An error occurred: ' . $e->getMessage();
        }

        // return $messages;
    }

    public static function listMessages($service, $userId) {
        $pageToken = NULL;
        $messages = array();
        $opt_param = array();
        do {
          try {
            if ($pageToken) {
              $opt_param['pageToken'] = $pageToken;
            }
            $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
            if ($messagesResponse->getMessages()) {
              $messages  = array_merge($messages, $messagesResponse->getMessages());
              $pageToken = $messagesResponse->getNextPageToken();
            }
          } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
          }
        } while ($pageToken);
      
        $i = 0;
        $data = Array();
        foreach ($messages as $message) {
    
            if ($i <= 20)
            {
                // print 'Message with ID: ' . $message->getId() . '<br/>';
                $message = $service->users_messages->get('me', $message->getId());

                //search and get index where subject header is.
                $subjectIndex = array_search('Subject', array_column($message->payload->headers, 'name'));
                $fromIndex    = array_search('From', array_column($message->payload->headers, 'name'));

                $data[$i]['message_id'] = $message->getId();
                $data[$i]['subject']    = $message->payload->headers[$subjectIndex]['value'];
                $data[$i]['from']       = $message->payload->headers[$fromIndex]['value'];
                
                $data[$i]['body'] = base64_decode(str_pad(strtr($message->payload->parts[0]->body->data, '-_', '+/'), strlen($message->payload->parts[0]->body->data) % 4, '=', STR_PAD_RIGHT));

                $i++;
            }
            // usleep(1000000);
        }
      
        return $data;
    }

    /**
     * Get Message with given ID.
     *
     * @param  Google_Service_Gmail $service Authorized Gmail API instance.
     * @param  string $userId User's email address. The special value 'me'
     * can be used to indicate the authenticated user.
     * @param  string $messageId ID of Message to get.
     * @return Google_Service_Gmail_Message Message retrieved.
     */
    public static function getMessage($service, $userId, $messageId) {

        try {
            $message = $service->users_messages->get($userId, $messageId);
            // print 'Message with ID: ' . $message->getId() . ' retrieved.';
            return $message;
        } catch (Exception $e) {
        // print 'An error occurred: ' . $e->getMessage();
        }

    }

    public static function getSubject($headers) {

        $index = array_search('Subject', array_column($headers, 'name'));

        return $headers[$index]['value'];
    }

    // public static function getMailedBy($headers) {

    //     $index = array_search('mailed-by', array_column($headers, 'name'));

    //     return $headers[$index]['value'];
    // }

    public static function getAttachmentId($headers) {

        // $index = array_search('X-Attachment-Id', array_column($headers, 'name'));
        $index = array_search('Content-ID', array_column($headers, 'name'));

        if ( empty($index) )
        {
            $index = array_search('Content-Id', array_column($headers, 'name'));
        }

        $val = str_replace(['<','>'], '', $headers[$index]['value']);

        return $val;
    }

    public static function tmpGetAttachmentId($headers) {
        dump($headers);
        // $index = array_search('X-Attachment-Id', array_column($headers, 'name'));
        $index = array_search('Content-ID', array_column($headers, 'name'));
        
        if ( empty($index) )
        {
            $index = array_search('Content-Id', array_column($headers, 'name'));
        }

        dump('header index: ' . $index);
        $val = str_replace(['<','>'], '', $headers[$index]['value']);
        dump($val);
        return $val;
    }

    public static function getFrom($headers, $search = 'From') {

        $index = array_search($search, array_column($headers, 'name'));
        
        if ( $index == false )
        {
            return '';
        }

        return preg_replace('/(.*)<(.*)>(.*)/sm', '\2', $headers[$index]['value']);
    }

    public static function getDate($headers) {

        $index = array_search('Date', array_column($headers, 'name'));

        return $headers[$index]['value'];
    }

    public static function identifyOriginId($requester) {

        $requester = substr(strrchr($requester, "@"), 1);

        $requester = str_replace(['<','>'], '', $requester);

        $requester = explode(".",$requester);

        $requester = strtoupper(current($requester));

        $ticketOrigin = TicketOrigin::where('name', $requester);
        
        if ( $ticketOrigin->count() )
        {
            return $ticketOrigin->first()->id;
        }
        else
        {
            $ticketOrigin = TicketOrigin::create([
                                'name'   => $requester,
                                'status' => TicketOrigin::ACTIVE,
                            ]);
            
            return $ticketOrigin->id;
        }

    }

    public static function messageEncode($messageData, $attachments = null)
    {
        // dump($messageData);
        // dump($attachments);
        $message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $messageData));
        $message = str_replace('’', '\'', $message);
        // $message = quoted_printable_decode($message);
        
        // if (strpos($message, '<div class="gmail_quote">'))
        // {
        //     $message = substr($message, 0, strpos($message, '<div class="gmail_quote">'));
        // }

        // $message = self::linkify($message);

        // if (strpos($message, '<div id="divRplyFwdMsg" dir="ltr">'))
        // {
        //     $message = substr($message, 0, strpos($message, '<div id="divRplyFwdMsg" dir="ltr">'));
        // }
        
        $message = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $message );
        $message = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $message );
        $message = str_replace("progress-bar", "", $message);
        // $message = utf8_decode($message);

        $doc = new \DOMDocument();
        // $doc->encoding = 'utf-8';
        // @$doc->loadHTML($message);
        // @$doc->loadHTML( utf8_decode( $message ) );
        @$doc->loadHTML( $message );

        // $s = new \DOMXPath($doc);

        // foreach($s->query('//div[contains(attribute::class, "gmail_quote")]') as $t )
        // {
        //     $t->parentNode->removeChild($t);
        // }

        $images = $doc->getElementsByTagName('img');

        foreach ($images as $image)
        {
            if ( isset($attachments) )
            {
                
                $old_src = $image->getAttribute('src');
                $old_alt = $image->getAttribute('alt');

                foreach ($attachments as $attachment) //name, data
                {

                    /*logger(json_encode($attachment));
                    logger('old_src: ' . $old_src);
                    logger('old_alt: ' . $old_alt);*/

                    if ( strpos($old_src, $attachment['attachment_id']) !== false || strpos($old_src, 'cid:'.$attachment['attachment_id']) !== false || strpos($old_alt, $attachment['filename']) !== false ) {

                        // $new_src = 'data:image/jpg;base64,' . $attachment['data'];
                        $_image = Image::make($attachment['data']);
                        $_image = self::resizeImage($_image, 540);
                        $_image = $_image->encode('data-url', 70)->encoded;
                        $image->setAttribute('src', $_image);
                        // $image->setAttribute('data-src', $old_src);

                    }
                    // fetch specific attached image that are not inline with mail content

                }

            }
        }

        $html = $doc->saveHTML();
        // $html = utf8_encode($html);
        
        // $html = str_replace( array('&Acirc;', '&acirc;', '&#128;', '&#153;', '3D%22', '%22'), '', $html);
        $html = str_replace( array('&Acirc;', '&acirc;', '&#128;', '&#153;',), '', $html);
        // logger($html);
        return base64_encode($html);

    }

    public static function tmpMessageEncode($messageData, $attachments = null)
    {
        // dump($messageData);
        // dump($attachments);
        $message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $messageData)); 
        $message = quoted_printable_decode($message);
        
        // if (strpos($message, '<div class="gmail_quote">'))
        // {
        //     $message = substr($message, 0, strpos($message, '<div class="gmail_quote">'));
        // }

        // $message = self::linkify($message);

        // if (strpos($message, '<div id="divRplyFwdMsg" dir="ltr">'))
        // {
        //     $message = substr($message, 0, strpos($message, '<div id="divRplyFwdMsg" dir="ltr">'));
        // }
        
        $message = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $message );
        $message = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $message );
        $message = str_replace("progress-bar", "", $message);
        // $message = utf8_decode($message);

        $doc = new \DOMDocument();
        // $doc->encoding = 'utf-8';
        // @$doc->loadHTML($message);
        // @$doc->loadHTML( utf8_decode( $message ) );
        @$doc->loadHTML( $message );
        // @$doc->loadHTML(mb_convert_encoding($message, 'HTML-ENTITIES', 'UTF-8'));

        $s = new \DOMXPath($doc);

        foreach($s->query('//div[contains(attribute::class, "gmail_quote")]') as $t )
        {
            $t->parentNode->removeChild($t);
        }

        $images = $doc->getElementsByTagName('img');

        foreach ($images as $image)
        {
            if ( isset($attachments) )
            {
                
                $old_src = $image->getAttribute('src');
                $old_alt = $image->getAttribute('alt');

                foreach ($attachments as $attachment) //name, data
                {

                    if ( strpos($old_src, $attachment['attachment_id']) !== false || strpos($old_src, 'cid:'.$attachment['attachment_id']) !== false || strpos($old_alt, $attachment['filename']) !== false ) {

                        // $new_src = 'data:image/jpg;base64,' . $attachment['data'];
                        $_image = Image::make($attachment['data']);
                        $_image = self::resizeImage($_image, 540);
                        $_image = $_image->encode('data-url', 70)->encoded;
                        $image->setAttribute('src', $_image);
                        // $image->setAttribute('data-src', $old_src);

                    }
                    // fetch specific attached image that are not inline with mail content

                }

            }
        }

        $html = $doc->saveHTML();
        // $html = utf8_encode($html);
        
        return base64_encode($html);

    }

    public static function linkify($messageData)
    {
        $messageData = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $messageData." ");
        $messageData = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1"  target="_blank">$1</a> ', $messageData." ");

        return $messageData;
    }

    public static function resizeImage($image, $requiredSize)
    {

        $width = (int)$image->width();
        $height = (int)$image->height();
    
        // Check if image resize is required or not
        if ($requiredSize >= $width && $requiredSize >= $height) return $image;
    
        // $newWidth;
        // $newHeight;
    
        // $aspectRatio = $width/$height;
        // if ($aspectRatio >= 1.0) {
        //     $newWidth = $requiredSize;
        //     $newHeight = $requiredSize / $aspectRatio;
        // } else {
        //     $newWidth = $requiredSize * $aspectRatio;
        //     $newHeight = $requiredSize;
        // }
    
        // $image->resize((int)$newWidth, (int)$newHeight);

        // resize the image to a width of 300 and constrain aspect ratio (auto height)
        $image->resize($requiredSize, null, function ($constraint) {
            $constraint->aspectRatio();
        });

        return $image;

    }


}
