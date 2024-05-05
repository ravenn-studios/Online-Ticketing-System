<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\GmailApi;
use App\Ticket;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\TicketOrigin;
use App\Message;
use App\EmailSupportAddress;
use App\Setting;
use \DTS\eBaySDK\Constants;
use \DTS\eBaySDK\Trading\Types;
use \DTS\eBaySDK\Trading\Enums;
use \DTS\eBaySDK\Trading\Services;
use Cookie;
use Storage;
use Log;
use App\EbayKey;
use Intervention\Image\Facades\Image;

class EbayAPI extends Model
{
    
    static public $service;
    static public $pageNumber     = 1;
    static public $entriesPerPage = 40;

    static public $ticketThreadId;
    static public $ticketSubject;
    static public $ticketSender;
    static public $receiveDate;

    static public $messageTicketId;
    static public $messageID;
    static public $messageEncoded;

    public $userToken;

    protected $devID;
    protected $appID;
    protected $certID;
    protected $clientID;
    protected $serverUrl;
    protected $paypalEmailAddress;
    protected $authCode;
    protected $ruName;
    public $ebayKey;

    public $access_token;
    public $refresh_token;

    public function __construct($devID = null, $appID = null, $certID = null, $clientID = null, $ruName = null, $authCode = null)
    {

        $this->devID     = $devID;
        $this->appID     = $appID;
        $this->certID    = $certID;
        $this->clientID  = $clientID;

        $this->serverUrl = 'https://api.ebay.com/ws/api.dll';
        
        $this->authCode  = $authCode;
        $this->ruName    = $ruName;


        $this->ebayKey  = EbayKey::active()->first();
        $this->refresh_token = $this->ebayKey->refresh_token;

        self::$service = new Services\TradingService([
            'authToken'   => $this->ebayKey->access_token,
            // 'authToken'   => 'v^1.1#i^1#p^3#f^0#r^0#I^3#t^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',
            'credentials' => [
                'appId'  => $this->ebayKey->appId,
                'certId' => $this->ebayKey->certId,
                'devId'  => $this->ebayKey->devId,
            ],
            'siteId' => Constants\SiteIds::GB
        ]);

        /*dd( self::$service );

        if ( isset( self::$service['ErrorCode'] ) && self::$service['ErrorCode'] == 932 )
        {
            $this->refreshToken();

            self::$service->setConfig(['authToken' => $this->access_token]);
        }*/

      //   self::$service = new Services\TradingService([
		    // 'authToken'   => 'AgAAAA**AQAAAA**aAAAAA**56oQYQ**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYajCpWFoQ6dj6x9nY+seQ**+IQGAA**AAMAAA**FbwXURxri8sy8I6zysVh643E/JD+QDp5xyPj5VdZtiC6IMBa1dFZUnunTmwScAqaeEnG8hF4pUcwEayE3mgtfNy9TuiX4qnXlQf+E9ZjzpByZi5OK+weVHUXkGbD2P027yFD+UWsbMePaWbnyRD1pj8EGX9ZSbUBLWXqXP+svDVsU/6mu40ZiFIS3FTRAv40EdY7pssGZEBA7cRysa1kpHlePSymelsGXdbpE7OGYa+56LsckXE8MkaghbJXIPxeDXrLReRNcQAzb8BmhIH6GGQX0uOyZi0vnSwtSzt02TLDaQFjrrtDp9f0CXQYOkjjaELp8FhPMQL98PuYku05BXujDq0w+KAUgxhxQNJIPIPY/dTnrDhOFMeIRgicVN+2pH32oCYR8+oMaaNvfJknOGWlAnol4piNy7CfjjjvjclLWVBL0iosa5eBjjwPcC1ENxH7KfXwdN8aVARlUuMjfKr9cvAPXeAsKNVFMNjNXAvR6yb8d/yWEZW0H+MGBB9i2+tBATR7BL/pXDvn6gATWSjz2bi2We/fIgQBwoa0DM9zrZ6qR8lD3DGYaI+byMTvhdDO0YBwlYF55I3PRWEUmy5w6nt/TdYQL0F4VR/8/9F6R+Pg8iLaFgxKHGyOetE5Zg5OnG5njvhYBhKt8wVibC8tjRHL8rVJu1tCwZ0oDiqlpg+YKXJeiTZ9G8iGrEOlxhmTTEmuORNa/JcdnQ8WjwoIKvQHpIzcsQBC2Y8JViH8tPRGNRNzGlSAU955hB5d',
		    // 'credentials' => [
		    // 	'appId'  => 'Frankies-Intellig-PRD-d7cee19be-11488eef',
		    // 	'certId' => 'a1adf088-a284-4c53-a7f5-bd3a16649f7b',
		    // 	'devId'  => 'PRD-7cee19be4704-e461-4dc9-bc4d-29c0'],
		    // 'siteId' => Constants\SiteIds::GB
      //   ]);

    }

    public function authorizationToken() {
        $link     = 'https://api.ebay.com/identity/v1/oauth2/token';
        $codeAuth = base64_encode($this->clientID.':'.$this->certID);
        $ch       = curl_init($link);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.$codeAuth
        ));
        curl_setopt($ch, CURLHEADER_SEPARATE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=authorization_code&code='.$this->authCode.'&redirect_uri='.$this->ruName);
        $response = curl_exec($ch);
        $json = json_decode($response, true);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if($json != null) {
            $this->access_token = $json['access_token'];
            $this->refresh_token = $json['refresh_token'];
        }
    }

    public function refreshToken() {
        $link = 'https://api.ebay.com/identity/v1/oauth2/token';
        $codeAuth = base64_encode($this->clientID.':'.$this->certID);
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.$codeAuth
        ));
        echo $this->refresh_token;
        curl_setopt($ch, CURLHEADER_SEPARATE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=refresh_token&refresh_token='.$this->refresh_token.'&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly');
        $response = curl_exec($ch);
        $json = json_decode($response, true);
        $info = curl_getinfo($ch);
        curl_close($ch);
        // dd($json);
        if($json != null) {
            // dump(self::$service);
            $this->access_token          = $json['access_token'];
            $this->ebayKey->access_token = $json['access_token'];
            $this->ebayKey->save();
            // self::$service->setConfig(['authToken' => $json['access_token']]);

            self::$service = new Services\TradingService([
                // 'authToken'   => $ebayKey->access_token,
                'authToken'   => $this->ebayKey->access_token,
                'credentials' => [
                    'appId'  => $this->ebayKey->appId,
                    'certId' => $this->ebayKey->certId,
                    'devId'  => $this->ebayKey->devId,
                ],
                'siteId' => Constants\SiteIds::GB
            ]);

            // dump(self::$service);

        }
    }

    public static function send( $message, $ticket, $file = null )
    {
        $request                                 = new Types\AddMemberMessageRTQRequestType();
        $request->MemberMessage                  = new Types\MemberMessageType();
        $request->MemberMessage->ParentMessageID = $ticket->thread_id;
        $request->MemberMessage->RecipientID     = [$ticket->requester];
        $request->MemberMessage->Body            = $message;

        if( \Auth::id() == 1 && $file != null )
        {

            // if( $file != null )
            // {

                //BaseService.php, TradingBaseService.php, TradingService.php

                $uploadFileFullUrl = self::uploadFile($file, $ticket->thread_id);
                $imageName = explode('.', $file->name);

                $_file             = Storage::get('public/attachments/'.$file->name);
                $request->attachment([
                    'data'     => base64_encode($_file),
                    // 'data'     => $_file,
                    'mimeType' => $file->extension
                ]);

                $request->MemberMessage->attachment([
                    'data'     => base64_encode($_file),
                    // 'data'     => $_file,
                    'mimeType' => $file->extension
                ]);

            // }`

            $request->MemberMessage->MessageMedia->MediaName = $imageName[0];
            $request->MemberMessage->MessageMedia->MediaURL  = [$uploadFileFullUrl];
            // dump('----------------');
            logger(json_encode($request->hasAttachment()));
            // logger(json_encode($request->attachment()));
            // dump($request);
        }

        // dd($request);
        $response = self::$service->addMemberMessageRTQ($request);


        return $response;
    }

    public static function uploadFile($file, $messageId)
    {

        //**resize image before uploading to eBay
        $_file             = Storage::get('public/attachments/'.$file->name);
        $image             = Image::make($_file)->widen(1000)->encode($file->extension, 90);
        $storeResizedImage = Storage::put('public/attachments/' . $file->name, $image);
        $_file             = Storage::get('public/attachments/'.$file->name);

        // if( $storeResizedImage ) {}

        $imageUrl  = url('/') . Storage::url('public/attachments/' . $file->name);
        $imageName = explode('.', $file->name);

        // $storeResizedImage = Storage::disk('baseStorage')->put( 'app/public/attachments/' . $file->name, $img);

        // send message but catch message tmp being sent by auth 1 dev user.
        //send > auth > uploadFile + validation


        //**upload attachment to eBay
        $request                           = new Types\UploadSiteHostedPicturesRequestType();
        $request->ExtensionInDays          = 30;
        // $request->ExternalPictureURL       = ['https://ots.blackedgedigital.com/storage/attachments/c7e0ead67157.jpg'];
        $request->ExternalPictureURL       = [$imageUrl];
        // $request->PictureData              = base64_encode($_file);
        $request->PictureName              = $imageName[0];
        $request->PictureSet               = 'Standard';
        $request->MessageID                = $messageId;

        $request->attachment([
            'data'     => base64_encode($_file),
            // 'data'     => $_file,
            'mimeType' => $file->extension
        ]);


        // dd($request);
        $response = self::$service->uploadSiteHostedPictures($request);
        // dd($response);
        // dump($response);
        // dump($response->Ack);//Success, Failure
        /*dump($response);
        dd($response->SiteHostedPictureDetails->FullURL);*/

        return $response->SiteHostedPictureDetails->FullURL;

    }

    // public static function tmpSend( $message, $ticket, $attachedFileUrl )        
    // {
    //     $request                                         = new Types\AddMemberMessageRTQRequestType();
    //     $request->MemberMessage                          = new Types\MemberMessageType();
    //     $request->MemberMessage->MessageMedia            = new Types\MessageMediaType();
    //     $request->MemberMessage->ParentMessageID         = $ticket->thread_id;
    //     $request->MemberMessage->RecipientID             = [$ticket->requester];
    //     $request->MemberMessage->Body                    = $message;

    //     $request->MemberMessage->MessageMedia->MediaName = 'test-attachment';
    //     $request->MemberMessage->MessageMedia->MediaURL  = $attachedFileUrl
    //     $response                                        = self::$service->addMemberMessageRTQ($request);

    //     return $response;
    // }

    public static function syncEbayMessages()
    {
        
        $ebayApi        = new EbayAPI;
        $headers        = self::getHeaders();
        $messageIDs     = self::getMessageIds($headers);
        $createdTickets = Array();

        $chunkedIDs = array_chunk($messageIDs, 10);

        foreach ( $chunkedIDs as $chunkedID )
        {

            $messages = self::getMessages($chunkedID);
            // dump($messages);
            // $messages = self::getMessages(['3109364855019']);

            // if ( $chunkedID == '2186461150014' )
            // {
            //     dd($messages);
            // }

            foreach( $messages->Messages->Message as $message )
            {

                // for "eBay" as sender, store messageID as thread_id since it doesnt have ExternalMessageID.
                $_messageID = ( $message->ExternalMessageID !== null ) ? $message->ExternalMessageID : $message->MessageID;

                // if ( $message->Sender == 'theodorval-0' )
                // {
                //     dd($messages->Messages->Message);
                // }

                // if ( $message->Sender == 'rodcai-7')
                // {
                //     dump($message);
                //     dump(self::formatDateAndShiftTimezone($message->ReceiveDate->format('Y-m-d H:i:s')));
                // }

                self::$ticketThreadId  = $_messageID;
                self::$ticketSubject   = $message->Subject;
                self::$ticketSender    = $message->Sender;
                self::$receiveDate     = self::formatDateAndShiftTimezone($message->ReceiveDate->format('Y-m-d H:i:s')); // should be formatInternalDate function

                self::$messageID       = $_messageID;
                self::$messageEncoded  = self::messageEncode($message->Text);


                if ( self::threadExists($_messageID) === false )
                {

                    $_createdTickets = self::createTicket();

                    $createdTickets[] = $_createdTickets->fresh();

                }

                if ( self::messageExists($_messageID) === false )
                {

                    self::$messageTicketId = Ticket::where('thread_id', $_messageID)->first()->id;

                    self::createMessage();

                }
                // else
                // {

                //     self::$messageTicketId = Ticket::where('thread_id', $_messageID)->first()->id;

                //     self::updateMessage();

                // }
    
            }

            if ( !empty($createdTickets) && Setting::active()->where('name', Setting::AUTO_TICKET_DISTRIBUTION)->count() )
            {
                $ticket = new Ticket;
                // $users  = User::ebayAgents()->get()->toArray(); - 06-19-23
                // $users = User::whereIn('id', [9,28])->get()->toArray(); //Anne
                $users = User::whereIn('id', [28])->get()->toArray(); //Anne

                $ticket->assignTickets($random = true, $createdTickets, $users);
            }

            usleep(500000);

        }

    }

    public static function tmpDumpEbayMessages()
    {
        
        $ebayApi        = new EbayAPI;
        $headers        = self::tmpGetHeaders();
        dd($headers);
        $messageIDs     = self::getMessageIds($headers);
        $createdTickets = Array();

        $chunkedIDs = array_chunk($messageIDs, 10);

        foreach ( $chunkedIDs as $chunkedID )
        {

            $messages = self::getTmpMessages2($chunkedID);

            foreach( $messages->Messages->Message as $message )
            {
                dump($message);
            }

        }

    }

    public static function createTicket()
    {

        $autoTicketDistribution = Setting::active()->where('name', Setting::AUTO_TICKET_DISTRIBUTION)->count();
        $ticketStatus           = TicketStatus::STATUS_UNASSIGNED;

        if ( $autoTicketDistribution )
        {
            $ticketStatus = TicketStatus::STATUS_PENDING;
        }

        $createTicket = Ticket::create([
            // 'thread_id'         => $message->ExternalMessageID,calc
            'thread_id'         => self::$ticketThreadId,
            'history_id'        => 0,
            'channel_id'        => TicketOrigin::ORIGIN_EBAY, // temporarily for EBAY
            'origin_id'         => TicketOrigin::ORIGIN_EBAY,
            'subject'           => utf8_encode(self::$ticketSubject),
            'snippet'           => utf8_encode(self::$ticketSubject),
            // 'requester'         => self::$ticketSender,
            'requester'         => str_replace(array('\'','"'), '', self::$ticketSender),
            'thread_started_at' => self::$receiveDate,
            'status_id'         => $ticketStatus,
            // 'priority_id'       => TicketPriority::PRIORITY_NORMAL, // default for now
            // 'type_id'           => TicketType::TYPE_QUESTION, // default for now
            'created_at'        => \Carbon\Carbon::now(),
            'updated_at'        => \Carbon\Carbon::now(),
        ]);

        return $createTicket;

    }

    public static function createMessage()
    {

        $storeMessage = Message::create([
            // 'ticket_id'     => $createTicket->id,
            'ticket_id'     => self::$messageTicketId,
            'message_id'    => self::$messageID,
            // 'message'       => base64_encode($match[1]),
            'message'       => self::$messageEncoded,
            'from'          => self::$ticketSender,
            'internal_date' => self::$receiveDate,
            'created_at'    => \Carbon\Carbon::now(),
            'updated_at'    => \Carbon\Carbon::now(),
        ]);

        $ticket       = Ticket::find(self::$messageTicketId);
        $ticket->read = false;
        $ticket->save();

        return $storeMessage;

    }

    public static function updateMessage()
    {

        $updateMessage = Message::where('ticket_id', self::$messageTicketId)->update([
                            'message'       => self::$messageEncoded,
                            'updated_at'    => \Carbon\Carbon::now(),
                        ]);

        return $updateMessage;

    }

    public static function getService()
    {
        return self::$service;
    }

    public static function getHeaders()
    {

        $request                             = new Types\GetMyMessagesRequestType();
        $request->DetailLevel                = ['ReturnHeaders'];
        $request->Pagination                 = new Types\PaginationType();
        $request->Pagination->EntriesPerPage = self::$entriesPerPage;
        $request->Pagination->PageNumber     = self::$pageNumber;
        $response                            = self::$service->getMyMessages($request);
        
        // dump(self::$service);

        // logger($response);
        if ( isset( $response->Errors ) )
        {
            foreach( $response->Errors as $error)
            {
                if ( $error->ErrorCode == 932 )
                {
                    $ebayKey = EbayKey::active()->first();
                    (new self($ebayKey->devId, $ebayKey->appId, $ebayKey->certId, $ebayKey->appId, $ebayKey->ruName, null))->refreshToken();
                    // dump(self::$service);

                    // (new self)->refreshToken();

                    // self::$service->setConfig(['authToken' => $this->access_token]);

                    $response = self::$service->getMyMessages($request);
                    // dd($response);
                    // logger($response);

                }
            }
        }

        
        if ( $response->Ack == 'Failure' )
        {
            Log::info( 'EbayApi Error' );
            Log::info( $response->Errors[0]->toArray() );

            // return false;
            exit();
        }
        
        $messages = $response->Messages;

        return $messages; 

    }

    public static function tmpGetHeaders()
    {

        $request                             = new Types\GetMemberMessagesRequestType();
        $request->MailMessageType            = 'All';
        $request->Pagination                 = new Types\PaginationType();
        $request->Pagination->EntriesPerPage = 5;
        $request->Pagination->PageNumber     = 1;
        $response                            = self::$service->getMemberMessages($request);
        dd($response->Errors);
        // dump(self::$service);

        // logger($response);
        if ( isset( $response->Errors ) )
        {
            foreach( $response->Errors as $error)
            {
                if ( $error->ErrorCode == 932 )
                {
                    $ebayKey = EbayKey::active()->first();
                    (new self($ebayKey->devId, $ebayKey->appId, $ebayKey->certId, $ebayKey->appId, $ebayKey->ruName, null))->refreshToken();
                    // dump(self::$service);

                    // (new self)->refreshToken();

                    // self::$service->setConfig(['authToken' => $this->access_token]);

                    $response = self::$service->getMemberMessages($request);
                    // dd($response);
                    // logger($response);

                }
            }
        }

        
        if ( $response->Ack == 'Failure' )
        {
            Log::info( 'EbayApi Error' );
            Log::info( $response->Errors[0]->toArray() );

            // return false;
            exit();
        }
        
        $messages = $response->Messages;

        return $messages; 

    }

    public static function getMessageIds($messages)
    {
        $messageIDs = Array();
        // $messages = $response->Messages;

		foreach($messages->Message as $message) {
            // if ( $message->Sender == 'theodorval-0' )
            // {
                $messageIDs[] = $message->MessageID;
            // }
        }

        return $messageIDs;

    }

    public static function getMessages($messageIDs)
    {

        $request                        = new Types\GetMyMessagesRequestType();
        $request->DetailLevel           = ['ReturnMessages'];
        $request->MessageIDs            = new Types\MyMessagesMessageIDArrayType();
        $request->MessageIDs->MessageID = $messageIDs;
        $response                       = self::$service->getMyMessages($request);

        // foreach ( $response->Messages->Message as $key => $message ) {
        //     if ( $message->Sender == 'theodorval-0' )
        //     {
        //         $test = rtrim(strtr(base64_encode($message->Text), '+/', '-_'), '=');
        //         $test = base64_decode($test);
        //         dd($test);
        //     }
        // }

        return $response;

    }

    public static function getTmpMessages($messageIDs)
    {

        $request                                        = new Types\GetMyMessagesRequestType();
        $request->DetailLevel                           = ['ReturnMessages'];
        $request->ExternalMessageIDs                    = new Types\MyMessagesExternalMessageIDArrayType();
        $request->ExternalMessageIDs->ExternalMessageID = $messageIDs;
        $response                                       = self::$service->getMyMessages($request);

        // foreach ( $response->Messages->Message as $key => $message ) {
        //     if ( $message->Sender == 'theodorval-0' )
        //     {
        //         $test = rtrim(strtr(base64_encode($message->Text), '+/', '-_'), '=');
        //         $test = base64_decode($test);
        //         dd($test);
        //     }
        // }

        return $response;

    }

    public static function getTmpMessages2($messageIDs)
    {

        $request           = new Types\GetMemberMessagesRequestType();
        $request->SenderID = 'brandbeast';
        $response          = self::$service->getMemberMessages($request);

        // foreach ( $response->Messages->Message as $key => $message ) {
        //     if ( $message->Sender == 'theodorval-0' )
        //     {
        //         $test = rtrim(strtr(base64_encode($message->Text), '+/', '-_'), '=');
        //         $test = base64_decode($test);
        //         dd($test);
        //     }
        // }

        return $response;

    }

    public static function getMessageToDump()
    {
        $request                        = new Types\GetMyMessagesRequestType();
        $request->DetailLevel           = ['ReturnMessages'];
        $request->MessageIDs            = new Types\MyMessagesMessageIDArrayType();
        // $request->MessageIDs->MessageID = ['3109364855019'];
        $request->MessageIDs->MessageID = ['6aed11885ebaeeda'];
        $response                       = self::$service->getMyMessages($request);

        // foreach ( $response->Messages->Message as $key => $message ) {
        //     if ( $message->Sender == 'theodorval-0' )
        //     {
        //         $test = rtrim(strtr(base64_encode($message->Text), '+/', '-_'), '=');
        //         $test = base64_decode($test);
        //         dd($test);
        //     }
        // }

        dd($response);
    }

    public static function extractUserMessage($message)
    {
        //extract the specific html block where the user message is
        $message = preg_match('/<div id="UserInputtedText">(.*?)<\/div>/s', $message, $_message);

        return $_message[1];
    }

    public static function messageEncode($message)
    {

        $doc = new \DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($message);

        $selector = new \DOMXPath($doc);

        foreach($selector->query('//td') as $a )
        {

            foreach ($selector->query('a', $a) as $b)
            {
                //remove specific class like .center its conflict with argon dashboard css thats why some other cta buttons are not placed properly
                $a->removeAttribute('class');

                if ( $b->nodeValue == 'Reply' || $b->nodeValue == 'Reply with offer' )
                {
                    //remove the td(parent wrapper) with a child "a" tag text "Reply, etc."
                    $a->parentNode->removeChild($a);
                }

            }

        }

        $html = $doc->saveHTML();

        return base64_encode($html);

    }

    public static function messageDecode($message)
    {
        return base64_decode($message);
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

    public static function formatDateAndShiftTimezone($dateString)
    {
        
        $date = explode('.', $dateString);
        $date = explode('+', current($date));
        $date = trim( current($date) );

        $date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date, 'Iceland');
        $date->setTimezone('Australia/Sydney');
        
        return $date->format('Y-m-d H:i:s');

    }

    public static function formatThreadDate($date)
    {
        //date from api is from the apps timezone
        return date('Y-m-d h:i:s', strtotime($date));

    }

}
