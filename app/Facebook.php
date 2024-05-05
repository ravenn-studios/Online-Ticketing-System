<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Session;
use Cookie;
use DateTime;
use DateTimeZone;
use App\Ticket;
use App\TicketOrigin;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\Facebook;
use App\FacebookPage;
use Storage;
use Log;


class Facebook extends Model
{

    public $table = 'facebook';

    static public $fb;
    static public $service;
    static public $accessToken;
    static public $accounts;
    static public $pageBody;
    static private $appId;
    static private $appSecret;


    public static function facebookInstance()
    {
        self::setAppData();
        self::$fb = new \Facebook\Facebook([
            'app_id'                => self::$appId,
            'app_secret'            => self::$appSecret,
            'default_graph_version' => 'v2.4',
          ]);
    }


    private static function setAppData()
    {
        $_fb = self::where('deleted_at', NULL)->first();

        self::$appId = $_fb->app_id;
        self::$appSecret = $_fb->app_secret;
        self::$accessToken = $_fb->access_token;
    }
    
    public static function setAccessToken()
    {
        self::$accessToken = \Session::get('fb-access-token');
    }
    
    public static function emptyAccessToken()
    {
        self::$accessToken = \Session::put('fb-access-token', '');
    }
    
    public static function getAccounts()
    {
        // self::$accounts = self::$fb->get('/me/accounts?fields=picture', self::$accessToken);
        self::$accounts = self::$fb->get('/me/accounts?fields=id,name,access_token,picture,category,category_list', self::$accessToken);
    }
    
    public static function getPageBody()
    {
        self::$pageBody = json_decode(self::$accounts->getBody());
    }

    public static function getAccountPages()
    {

        self::checkAccessToken();
        self::setAccessToken();
        self::getAccounts();
        self::getPageBody();
        
        foreach(self::$pageBody->data as $page_data)
        {

            foreach ( $page_data as $key => $val )
            {
                if ( isset($val->data) )
                {
                    // dump($val);
                    echo '<img src="'.$val->data->url.'"/>';
                }
            }
        
        }
        // dd();
    }

    public static function syncAccountPages()
    {

        self::checkAccessToken();
        self::setAccessToken();
        self::getAccounts();
        self::getPageBody();
        
        $newPageIds      = Array();
        $existingPageIds = Array();

        foreach(self::$pageBody->data as $page_data)
        {

            array_push($newPageIds, (string)$page_data->id);
            $imageUrl          = $page_data->picture->data->url;
            $accountPageExists = FacebookPage::where('page_id', $page_data->id)->count();

            if ( !$accountPageExists )
            {

                $accountDisplayPhoto = self::saveAccountDisplayPhoto($imageUrl);

                //storeas image
                $createFacebookPage = FacebookPage::create([
    
                    'page_id'      => $page_data->id,
                    'name'         => $page_data->name,
                    'access_token' => $page_data->access_token,
                    'image'        => $accountDisplayPhoto,
                    'created_at'   => \Carbon\Carbon::now(),
                    'updated_at'   => \Carbon\Carbon::now(),
    
                ]);

            }
            else
            {

                $accountDisplayPhoto = self::saveAccountDisplayPhoto($imageUrl);

                //storeas image
                $updateFacebookPage = FacebookPage::where('page_id', $page_data->id)
                    ->update([
        
                        'page_id'      => $page_data->id,
                        'name'         => $page_data->name,
                        'access_token' => $page_data->access_token,
                        'image'        => $accountDisplayPhoto,
                        'updated_at'   => \Carbon\Carbon::now(),
        
                    ]);

            }

        }

        //delete
        $existingPageIds = self::getFacebookPagesId();
        $pageIdsToDelete = array_diff( $existingPageIds, $newPageIds );

        FacebookPage::whereIn('page_id', $pageIdsToDelete)->delete();

    }

    public static function saveAccountDisplayPhoto($imageUrl)
    {

        //get image contents
        $contents = file_get_contents($imageUrl);
        //generate random characters for filename
        $randAlphanumeric = random_bytes(8);
        $randAlphanumeric = bin2hex($randAlphanumeric);
        //get image file ext.
        $_imageUrl = explode('?', $imageUrl);
        $ext = '.'.substr($_imageUrl[0], strrpos($_imageUrl[0], '.')+1);
        //set new image filename and store to storage images
        $fileName = 'fba'.$randAlphanumeric.$ext;
        Storage::put('/images/'.$fileName, $contents);

        return $fileName;

    }

    public static function getFacebookPagesId()
    {

        $facebookPagesId  = FacebookPage::all('page_id')->toArray();
        $_facebookPagesId = Array();

        foreach( array_values($facebookPagesId) as $val )
        {
            array_push($_facebookPagesId, $val['page_id']);
        }

        return $_facebookPagesId;

    }

    public static function checkAccessToken()
    {

        $_fb = self::where('deleted_at', NULL)->first();

        // if( empty(\Session::get('fb-access-token')) )
        if( empty($_fb->access_token) )
        {

            $cookieLife  = 30;
            // $cookieValue = str_random(32);
            $cookieValue = \Str::random(32);
            $validUntil  = \Carbon\Carbon::now()->addMinutes($cookieLife)->timestamp;
            \Cookie::queue(\Cookie::make('facebook_auth', $cookieValue, $cookieLife));
            cache(['fb-auth-cookie' => "{$cookieValue}|{$validUntil}"], $cookieLife);
            // dump(\Cache::get('fb-auth-cookie'));
            // $permissions = ['email', 'user_posts', 'pages_read_engagement', 'pages_read_user_content', 'pages_messaging'];  // optional
            $permissions = ['email', 'pages_read_engagement', 'pages_read_user_content', 'pages_messaging', 'pages_manage_metadata'];  // optional
            $callback    = 'https://ots.blackedgedigital.com/channels/facebook/oauth';
            $helper      = self::$fb->getRedirectLoginHelper();
            $loginUrl    = $helper->getLoginUrl($callback, $permissions);

            redirect($loginUrl)->withCookies(\Cookie::getQueuedCookies());

        }

    }

    public static function syncConversations()
    {

        // self::emptyAccessToken();
        self::checkAccessToken();
        self::setAccessToken();
        // self::getAccounts();
        // self::getPageBody();
        
        $pages = FacebookPage::all();
        $pageId = 0;
        // foreach(self::$pageBody->data as $page_data)
        foreach($pages as $page_data)
        {

            $pageId = $page_data->page_id;
            
			$conversations = json_decode(self::$fb->get('/'.$page_data->page_id.'/conversations', $page_data->access_token)->getBody());

            foreach ( $conversations->data as $conversation )
            {
                // dump('conversations id: '.$conversation->id);
                $message = json_decode(self::$fb->get('/'.$conversation->id.'/messages?fields=message,created_time,from,id,tags,to,sticker,attachments', $page_data->access_token)->getBody());
                // dump($message->data);
                // continue;

                // if ( $conversation->id == "t_109139341103671" )
                // {
                //     dd($message->data);
                // }

                $messageCtr    = 0;
                $snippet       = '';
                $requester     = '';
                $ticketId      = 0;
                $threadStarter = end($message->data); $threadStarter = $threadStarter->from->name;
                $snippet       = current($message->data); $snippet   = substr($snippet->message, 0, 60);

                //create ticket if does not exists else get the ticket id
                $ticket = Ticket::where('thread_id', $conversation->id);

                if ( !$ticket->count() )
                {

                    $createTicket = Ticket::create([
                        'thread_id'         => $conversation->id,
                        'page_id'           => $page_data->page_id,
                        'history_id'        => 0,
                        'channel_id'        => TicketOrigin::ORIGIN_FACEBOOK,     // temporarily for GMAIL
                        'origin_id'         => TicketOrigin::ORIGIN_FACEBOOK,
                        'subject'           => $snippet,
                        'snippet'           => $snippet.'..',
                        // 'requester'         => $threadStarter,
                        'requester'         => str_replace( ['"', "'"], ['', ''], $threadStarter ),
                        'thread_started_at' => \Carbon\Carbon::now(),
                        'status_id'         => TicketStatus::STATUS_PENDING,
                        'priority_id'       => TicketPriority::PRIORITY_HIGH,     // default for now
                        'type_id'           => TicketType::TYPE_QUESTION,         // default for now
                        'created_at'        => \Carbon\Carbon::now(),
                        'updated_at'        => \Carbon\Carbon::now(),
                    ]);

                    $ticketId = $createTicket->id;

                }
                else
                {
                    
                    $ticket = $ticket->first();

                    //get ticket id
                    $ticketId = $ticket->id;

                    //tmp update tickets page id - newly added field
                    // $ticket->page_id = $pageId;
                    // $ticket->save();

                }

                // dump($message->data);
                //loop through conversation messages
                foreach ( $message->data as $key => $messageDetails )
                {


                    if ( empty($messageDetails->message) )
                    {
                        // on empty message check attachments
                        // $attachment = json_decode( self::$fb->get(
                        //     '/'.$messageDetails->id.'/attachments',
                        //     $page_data->access_token
                        // )->getBody() );

                        if ( isset($messageDetails->attachments) )
                        {
                            // dump($messageDetails->attachments->data);
                            $mimeType = $messageDetails->attachments->data[0]->mime_type;
                            $mimeType = explode("/", $mimeType, 2);
                            $mimeType = $mimeType[0];

                            if ( $mimeType == 'video' )
                            {
                                $videoUrl            = $messageDetails->attachments->data[0]->video_data->url;
                                $messageAttachment = '<video width="320" height="240" preload="none" controls muted>
                                                            <source src="'.$videoUrl.'" type="video/mp4">
                                                        Your browser does not support the video tag.
                                                        </video>';
                            }
                            elseif ( $mimeType == 'image' )
                            {
                                $imgUrl            = $messageDetails->attachments->data[0]->image_data->preview_url;
                                $messageAttachment = '<img src="'.$imgUrl.'" width="auto" height="auto" >';
                            }

                            //create ticket message if does not exists
                            $message = Message::where('message_id', $messageDetails->id);

                            if ( !$message->count() )
                            {
                                
                                $createMessage = Message::create([

                                    // 'ticket_id'     => $conversation->id,
                                    'ticket_id'     => $ticketId,
                                    'message_id'    => $messageDetails->id,
                                    'message'       => base64_encode($messageAttachment),
                                    // 'from'          => $messageDetails->from->name,
                                    'from'          => str_replace( ['"', "'"], ['', ''], $messageDetails->from->name ),
                                    'internal_date' => self::formatDate($messageDetails->created_time),
                                    'created_at'    => \Carbon\Carbon::now(),
                                    'updated_at'    => \Carbon\Carbon::now(),

                                ]);

                                if ( $key == 0 )
                                {
                                    // update ticket snippet for new messages
                                    Ticket::where('thread_id', $ticketId)->update([
                                        'snippet' => substr($messageDetails->message, 0, 60).'..',
                                        'updated_at'    => \Carbon\Carbon::now(),
                                    ]);
                                }
                                
                            }
                            // else
                            // {
                            //     $updateMessage = $message->update([

                            //         'message' => base64_encode($messageAttachment),

                            //     ]);

                            // }

                        }
                        else if ( isset($messageDetails->sticker) )
                        {
                            
                            $messageSticker = '<img src="'.$messageDetails->sticker.'" width="auto" height="auto" >';
                            
                            //create ticket message if does not exists
                            $message = Message::where('message_id', $messageDetails->id);

                            if ( !$message->count() )
                            {
                                
                                $createMessage = Message::create([

                                    // 'ticket_id'     => $conversation->id,
                                    'ticket_id'     => $ticketId,
                                    'message_id'    => $messageDetails->id,
                                    'message'       => base64_encode($messageSticker),
                                    // 'from'          => $messageDetails->from->name,
                                    'from'          => str_replace( ['"', "'"], ['', ''], $messageDetails->from->name ),
                                    'internal_date' => self::formatDate($messageDetails->created_time),
                                    'created_at'    => \Carbon\Carbon::now(),
                                    'updated_at'    => \Carbon\Carbon::now(),

                                ]);

                                if ( $key == 0 )
                                {
                                    // update ticket snippet for new messages
                                    Ticket::where('thread_id', $ticketId)->update([
                                        'snippet' => substr($messageDetails->message, 0, 60).'..',
                                        'updated_at'    => \Carbon\Carbon::now(),
                                    ]);
                                }
                                
                            }
                            // else
                            // {
                            //     $updateMessage = $message->update([

                            //         'message' => base64_encode($messageSticker),

                            //     ]);

                            // }

                        }
                        
                    }
                    else
                    {
                        // dump($messageDetails);
                        //create ticket message if does not exists
                        $message = Message::where('message_id', $messageDetails->id);

                        if ( !$message->count() )
                        {
                            
                            $createMessage = Message::create([

                                // 'ticket_id'     => $conversation->id,
                                'ticket_id'     => $ticketId,
                                'message_id'    => $messageDetails->id,
                                'message'       => base64_encode($messageDetails->message),
                                'from'          => $messageDetails->from->name,
                                'internal_date' => self::formatDate($messageDetails->created_time),
                                'created_at'    => \Carbon\Carbon::now(),
                                'updated_at'    => \Carbon\Carbon::now(),

                            ]);

                            if ( $key == 0 )
                            {
                                //update ticket snippet for new messages
                                Ticket::where('thread_id', $ticketId)->update([
                                    'snippet' => substr($messageDetails->message, 0, 60).'..',
                                    'updated_at'    => \Carbon\Carbon::now(),
                                ]);
                            }
                            
                        }

                    }

                }

            }

        }
    }

    public static function getRecipientId($conversationId, $pageId, $accessToken)
    {

        $conversations = json_decode(self::$fb->get('/'.$pageId.'/conversations', $accessToken)->getBody());

        foreach ( $conversations->data as $conversation )
        {

            $message = json_decode(self::$fb->get('/'.$conversation->id.'/messages?fields=message,created_time,from,id,tags,to,sticker,attachments', $accessToken)->getBody());
            // dump($message);
            if ( $conversation->id == $conversationId )
            {
                // dump($message->data);
                $messageData = end($message->data);
                // dd($messageData);
                $recipientId = $messageData->from->id;

                return $recipientId;

            }

        }

    }

    // public static function sendFacebookMessage()
    public static function sendFacebookMessage($ticketId, $message)
    {
        /* sendReply using facebookpage access token, conversation starter/recipientId
         * 
        */

        $ticket = Ticket::find($ticketId);
        // dump($ticket->page_id);
        $facebookPage = FacebookPage::where('page_id', $ticket->page_id)->first();
        // dd($facebookPage);
        $recipientId = self::getRecipientId($ticket->thread_id, $facebookPage->page_id, $facebookPage->access_token);

        // dd($recipientId);
        // dump($facebookPage->access_token);
        // dump($message);
        
        $result = self::$fb->post('/me/messages',
                    [
                        'headers' => 'charset=UTF-8',
                        'messaging_type' => 'RESPONSE',
                        // 'recipient'      => ['id' => '3504449106297880'], //bed dev frankies
                        'recipient'      => ['id' => $recipientId], //rodney dev - rodneydcro3
                        'message'        => ['text' => $message]
                    ],
                    $facebookPage->access_token);

        // dump($ticketId);
        // dd(1);
        //sync conversation messages after sending message
        self::syncConversation($ticketId);
           
    }

    public static function syncConversation($ticketId)
    {

        $ticket       = Ticket::find($ticketId);
        $facebookPage = FacebookPage::where('page_id', $ticket->page_id)->first();

        //***sync this specific conversation
        $conversationId = $ticket->thread_id;
        $message = json_decode( self::$fb->get('/'.$conversationId.'/messages?fields=message,created_time,from,id,tags,to,sticker,attachments', $facebookPage->access_token)->getBody() );
        // dd($message->data);
        //loop through conversation messages
        foreach ( $message->data as $key => $messageDetails )
        {

            if ( empty($messageDetails->message) )
            {
                // on empty message check attachments
                // $attachment = json_decode( self::$fb->get(
                //     '/'.$messageDetails->id.'/attachments',
                //     $facebookPage->access_token
                //   )->getBody() );

                if ( isset($messageDetails->attachments) )
                {
                    
                    $imgUrl            = $messageDetails->attachments->data[0]->image_data->preview_url;
                    $messageAttachment = '<img src="'.$imgUrl.'" width="auto" height="auto" >';
                    
                    //create ticket message if does not exists
                    $message = Message::where('message_id', $messageDetails->id);

                    if ( !$message->count() )
                    {
                        
                        $createMessage = Message::create([

                            // 'ticket_id'     => $conversation->id,
                            'ticket_id'     => $ticket->id,
                            'message_id'    => $messageDetails->id,
                            'message'       => base64_encode($messageAttachment),
                            'from'          => $messageDetails->from->name,
                            'internal_date' => self::formatDate($messageDetails->created_time),
                            'created_at'    => \Carbon\Carbon::now(),
                            'updated_at'    => \Carbon\Carbon::now(),

                        ]);

                        if ( $key == 0 )
                        {
                            // update ticket snippet for new messages
                            Ticket::where('thread_id', $conversationId)->update([
                                'snippet' => substr($messageDetails->message, 0, 60).'..',
                                'updated_at'    => \Carbon\Carbon::now(),
                            ]);
                        }
                        
                    }
                    // else
                    // {
                    //     $updateMessage = $message->update([

                    //         'message' => base64_encode($messageAttachment),

                    //     ]);

                    // }

                }
                else if ( isset($messageDetails->sticker) )
                {
                    
                    $messageSticker = '<img src="'.$messageDetails->sticker.'" width="auto" height="auto" >';
                    
                    //create ticket message if does not exists
                    $message = Message::where('message_id', $messageDetails->id);

                    if ( !$message->count() )
                    {
                        
                        $createMessage = Message::create([

                            // 'ticket_id'     => $conversation->id,
                            'ticket_id'     => $ticket->id,
                            'message_id'    => $messageDetails->id,
                            'message'       => base64_encode($messageSticker),
                            'from'          => $messageDetails->from->name,
                            'internal_date' => self::formatDate($messageDetails->created_time),
                            'created_at'    => \Carbon\Carbon::now(),
                            'updated_at'    => \Carbon\Carbon::now(),

                        ]);

                        if ( $key == 0 )
                        {
                            // update ticket snippet for new messages
                            Ticket::where('thread_id', $conversationId)->update([
                                'snippet' => substr($messageDetails->message, 0, 60).'..',
                                'updated_at'    => \Carbon\Carbon::now(),
                            ]);
                        }
                        
                    }
                    else
                    {
                        $updateMessage = $message->update([

                            'message' => base64_encode($messageSticker),

                        ]);

                    }

                }
                
            }
            else
            {

                //create ticket message if does not exists
                $message = Message::where('message_id', $messageDetails->id);

                if ( !$message->count() )
                {
                    
                    $createMessage = Message::create([

                        // 'ticket_id'     => $conversation->id,
                        'ticket_id'     => $ticket->id,
                        'message_id'    => $messageDetails->id,
                        'message'       => base64_encode($messageDetails->message),
                        'from'          => $messageDetails->from->name,
                        'internal_date' => self::formatDate($messageDetails->created_time),
                        'created_at'    => \Carbon\Carbon::now(),
                        'updated_at'    => \Carbon\Carbon::now(),

                    ]);

                    if ( $key == 0 )
                    {
                        // update ticket snippet for new messages
                        Ticket::where('thread_id', $conversationId)->update([
                            'snippet' => substr($messageDetails->message, 0, 60).'..',
                            'updated_at'    => \Carbon\Carbon::now(),
                        ]);
                    }
                    
                }
                
            }

        }
        // dd();

        return true;

        //end sync

    }
    
    public static function getCallback()
    {
        return 'https://ots.blackedgedigital.com/channels/facebook/oauth';
    }

    public static function formatDate($internalDate)
    {

        //convert facebook api date to apps date(uk to australia)
        $date = new DateTime($internalDate, new DateTimeZone('Europe/London'));
        // dump($date->format('Y-m-d H:i:s') . "\n");

        $date->setTimezone(new DateTimeZone('Australia/Sydney'));
        
        return $date->format('Y-m-d H:i:s');

    }

    public static function getLoginUrl()
    {

        self::facebookInstance();

        $permissions = ['email', 'user_posts', 'pages_read_engagement', 'pages_read_user_content', 'pages_messaging', 'pages_manage_metadata'];  // optional
        $callback    = 'https://ots.blackedgedigital.com/channels/facebook/oauth';
        $helper      = self::$fb->getRedirectLoginHelper();
        $loginUrl    = $helper->getLoginUrl($callback, $permissions);

        return $loginUrl;

        // syncAccountPages

    }
    
}
