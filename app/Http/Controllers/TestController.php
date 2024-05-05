<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Filter;
use Google_Service_Gmail_FilterCriteria;
use Google_Service_Gmail_FilterAction;
use App\AuthGmail;
use App\User;
use App\Role;
use App\GmailApi;
use App\Ticket;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketOrigin;
use App\TicketType;
use App\Signature;
use App\Message;
use App\EmailSupportAddress;
use App\EbayAPI;
use App\UserCustomPage;
use App\CustomPageCondition;
use App\Facebook;
use App\FacebookPage;
use App\Chat;
use App\ChatMessage;
use App\Customer;
use App\Setting;
use App\File;
use App\AssignedTicket;
use App\Tag;
use App\Category;
use App\TicketCategories;
use App\Reminder;
use App\UserSchedule;
use URL;
use \DTS\eBaySDK\Constants;
use \DTS\eBaySDK\Trading\Types;
use \DTS\eBaySDK\Trading\Enums;
use \DTS\eBaySDK\Trading\Services;
// use \DTS\eBaySDK\OAuth\Services;
// use \DTS\eBaySDK\OAuth\Types;
use Cookie;
use Storage;
use Illuminate\Support\Str;
use Session;
use DateTime;
use DateTimeZone;
use Cache;
use Log;
use Intervention\Image\Facades\Image;
use App\UserPerformanceLog;

use App\Mail\SendEmailWithNoReply;
use App\Mail\MailPerformanceReport;
use App\Mail\NotifyAccountCreation;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Emailer;

class TestController extends Controller
{
    
    public function oauth(Request $request)
    {
        // dd($request->getSession()->token());
        // dump(Session::get('SessionID'));
        
        $config = [
            'client_id'     => 'rodneyca-frankies-PRD-86c5a7b3e-a0521337',
            'client_secret' => 'PRD-6c5a7b3e9188-481a-48ba-ae01-00e8',
            // 'ru_name'    => 'rodney_caisip-rodneyca-franki-yaieky',
            'ru_name'       => 'rodney_caisip-rodneyca-franki-entsb',
            // 'login_url'  => 'https://auth.ebay.com/oauth2/authorize',
            'login_url'     => 'https://signin.ebay.com/ws/eBayISAPI.dll',
            'oauth_url'     => 'https://api.ebay.com/identity/v1/oauth2/token',
            'api_scopes'    => ['https://api.ebay.com/oauth/api_scope'],
        ];

        $service = new \DTS\eBaySDK\Trading\Services\TradingService([
            'credentials' => [
                'appId'  => $config['client_id'],
                'certId' => $config['client_secret'],
                'devId'  => 'a42c7c85-1bef-4dc4-8049-15f9509a067a'],
            // 'profile' => 'brandbeast',
		    'siteId' => Constants\SiteIds::GB
        ]);

        // dd($service);

        $__request = new \DTS\eBaySDK\Trading\Types\GetSessionIDRequestType();
        // $_request->SecretID  = $config['client_id'];
        // $_request->SessionID = $request->getSession()->token();
        $__request->RuName = $config['ru_name'];
        // $_request->SessionID = $request->getSession()->getId();
        // $_request->RequestUserId = 'brandbeast';
        
        // $_request = new \DTS\eBaySDK\Trading\Types\FetchTokenRequestType();
        // $_request->SecretID  = $config['client_id'];
        // $_request->SessionID = $request->getSession()->getId();
        // $_request->SessionID = $service->getSessionID($__request)->SessionID;
        // $_request->RequestUserId = 'brandbeast';
        // dd($_request);

        $_request = new \DTS\eBaySDK\Trading\Types\ConfirmIdentityRequestType();
        // $_request->SessionID = $service->getSessionID($__request)->SessionID;
        $_request->SessionID = Session::get('SessionID');
        
        // $response = $service->fetchToken($_request);
        $response = $service->confirmIdentity($_request);
        

        dd($response);

        $url = $config['login_url'];
        $url .= '?SignIn&runame='.$config['ru_name'];
        $url .= '&SessID='.$service->getSessionID($_request)->SessionID;

        //

        $service = new \DTS\eBaySDK\Trading\Services\TradingService([
		    // 'authorization'   => 'AgAAAA**AQAAAA**aAAAAA**lBayXw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYajCpWFoQ6dj6x9nY+seQ**F4MGAA**AAMAAA**bopzuZ7Kj/0PAH4p1T+p+Pva/nEpCJn8yYqin4zXJ9s0AsW4oTXUTCzU0IWufA7vYiwLiUxHxPuxe8RBitysImQc0SzAcDIbHI+24ke52YvsmgKITeqR7jMGMMv0ePsVYu8Q7pVNeERCu8mO1G2bAxOTeA9vmsr7uWSkaexOTrnHwAbQQ8RHNNqWccEPCeFI3z97F1c60It21ZvOwMyRMkxGPbcbEd73QzzSk1KD8JnI/glLK12tzqy3Z5IgnV4He/C3ATTenMji0qwISMyi0YgI1SIBXqIb0ZI9wRXZmjoFzZCUrydjyZY2NnWkuhdcoEOsHCWVJWWEAnJpso3wpiYdVq9BAum9R98J8vE7BN5/GMdkOP97ZcucupsbtDRCyMiCJn67KNyaO5IdWQ4/rFg1Ur79dzUvCo9yIx/Za4daYQPy8CE6fBBLKQgXbGgYu9n436YgJJ+2R/Jb6C4f27XCb9MfQoXKBiAzxf26YudqI8eJfgMHNDggP7OY/eleAp64gdTChOW1dFYCQI9+hWeYJRkok0X4REH4p+x+I2u5kGjDvazH5FvV59IRRuC3dbDSi6bewAsMCGtOafwJGegfzn9DzvzSNWvNzeBhkbyrKgpHMJnQ7qEsMXzSdTcb7em7PXXP83wROo1NsDfixFKUGzE6o/W+jIIH+7gdmjOoAFx4BruTLQwwvXeJ8bSCIejW8BuyTFdwgN7KVLyl0Vxei42eJ97TqfkGVMga1o8bph7+R2F1uqSaEsdWGVfk',
		    'authorization'   => $response->access_token,
		    'credentials' => [
		    	'appId'  => 'Frankies-Intellig-PRD-8e6527e8b-e809e2fb',
		    	'certId' => 'b2d75e6f-fed5-49da-8d31-ae37661d91fa',
		    	'devId'  => 'PRD-e6527e8b8e38-f353-4341-b037-3371'],
		    'siteId' => Constants\SiteIds::GB
        ]);

        $request = new \DTS\eBaySDK\Trading\Types\GetMyMessagesRequestType();

		$request->DetailLevel = ['ReturnHeaders'];
		$request->Pagination  = new \DTS\eBaySDK\Trading\Types\PaginationType();
		$request->Pagination->EntriesPerPage = 10;
  		$request->Pagination->PageNumber     = 1;
        $response = $service->getMyMessages($request);
        $messages = $response->Messages;

        dd($request);



        //should be in db
        $config = [
            'client_id' => 'rodneyca-frankies-PRD-86c5a7b3e-a0521337',
            'client_secret' => 'PRD-6c5a7b3e9188-481a-48ba-ae01-00e8',
            'ru_name' => 'rodney_caisip-rodneyca-franki-yaieky',
            'login_url' => 'https://auth.ebay.com/oauth2/authorize',
            'oauth_url' => 'https://api.ebay.com/identity/v1/oauth2/token',
            'api_scopes' => ['https://api.ebay.com/oauth/api_scope'],
        ];

        $code = $request->code;
        dump($code);
        $authorization = 'Basic '.base64_encode($config['client_id'].':'.$config['client_secret']);
        // print_r($config);
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $config['oauth_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials&code=".$code."&redirect_uri=".$config['ru_name'],
            CURLOPT_HTTPHEADER => [
                "Authorization: ".$authorization,
                "Content-Type: application/x-www-form-urlencoded",
                "cache-control: no-cache",
            ],
        ]);

        $response = json_decode( curl_exec($curl) );
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        dd($response);

        $service = new \DTS\eBaySDK\Trading\Services\TradingService([
		    // 'authorization'   => 'AgAAAA**AQAAAA**aAAAAA**lBayXw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYajCpWFoQ6dj6x9nY+seQ**F4MGAA**AAMAAA**bopzuZ7Kj/0PAH4p1T+p+Pva/nEpCJn8yYqin4zXJ9s0AsW4oTXUTCzU0IWufA7vYiwLiUxHxPuxe8RBitysImQc0SzAcDIbHI+24ke52YvsmgKITeqR7jMGMMv0ePsVYu8Q7pVNeERCu8mO1G2bAxOTeA9vmsr7uWSkaexOTrnHwAbQQ8RHNNqWccEPCeFI3z97F1c60It21ZvOwMyRMkxGPbcbEd73QzzSk1KD8JnI/glLK12tzqy3Z5IgnV4He/C3ATTenMji0qwISMyi0YgI1SIBXqIb0ZI9wRXZmjoFzZCUrydjyZY2NnWkuhdcoEOsHCWVJWWEAnJpso3wpiYdVq9BAum9R98J8vE7BN5/GMdkOP97ZcucupsbtDRCyMiCJn67KNyaO5IdWQ4/rFg1Ur79dzUvCo9yIx/Za4daYQPy8CE6fBBLKQgXbGgYu9n436YgJJ+2R/Jb6C4f27XCb9MfQoXKBiAzxf26YudqI8eJfgMHNDggP7OY/eleAp64gdTChOW1dFYCQI9+hWeYJRkok0X4REH4p+x+I2u5kGjDvazH5FvV59IRRuC3dbDSi6bewAsMCGtOafwJGegfzn9DzvzSNWvNzeBhkbyrKgpHMJnQ7qEsMXzSdTcb7em7PXXP83wROo1NsDfixFKUGzE6o/W+jIIH+7gdmjOoAFx4BruTLQwwvXeJ8bSCIejW8BuyTFdwgN7KVLyl0Vxei42eJ97TqfkGVMga1o8bph7+R2F1uqSaEsdWGVfk',
		    'authorization'   => $response->access_token,
		    'credentials' => [
		    	'appId'  => 'Frankies-Intellig-PRD-8e6527e8b-e809e2fb',
		    	'certId' => 'b2d75e6f-fed5-49da-8d31-ae37661d91fa',
		    	'devId'  => 'PRD-e6527e8b8e38-f353-4341-b037-3371'],
		    'siteId' => Constants\SiteIds::GB
        ]);

        $request = new \DTS\eBaySDK\Trading\Types\GetMyMessagesRequestType();

		$request->DetailLevel = ['ReturnHeaders'];
		$request->Pagination  = new \DTS\eBaySDK\Trading\Types\PaginationType();
		$request->Pagination->EntriesPerPage = 10;
  		$request->Pagination->PageNumber     = 1;
        $response = $service->getMyMessages($request);
        $messages = $response->Messages;

        dd($messages);
    }

    public function assignTickets($random = 0, $tickets, $users)
    {

        if ( $random )
        {

            $user = $users->first();

            foreach ( $tickets as $ticket )
            {
                // $this->assignTicket($ticket, $user);
                dump($user->id.': '.$user->name);
                $user = $user->next();

                if(!$user)
                {
                    $user = $users->first();
                }
                
            }

        }
        else
        {

            foreach ( $tickets as $ticket )
            {
                // $this->assignTicket($ticket, $users);
            }

        }

    }

    public function createDoc($content = '')
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $section = $phpWord->addSection();


        // $description = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

        $section->addText( base64_decode($content) );

        $randString = random_bytes(6);
        $randString = bin2hex($randString);
        $file = $randString.'.docx';
        header("Content-Description: File Transfer");
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: base64');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');


        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        
        // $objWriter->save("php://output");
        $objWriter->save( storage_path('app/attachments/'.$file) );
    }

    public static function messageEncode($messageData, $attachments = null)
    {
        // dump($messageData);
        // dump($attachments);
        $message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $messageData));
        $message = str_replace('’', '\'', $message);

        // dd(mb_detect_encoding($message));
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
        // foreach($s->query('div') as $t )
        // {
        //     $t->parentNode->removeChild($t);
        //     if ( empty($t->nodeValue) )
        //     {
        //         $t->parentNode->removeChild($t);
        //     }
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
        // echo $html;
        // dd($html);
        
        $html = str_replace( array('&Acirc;', '&acirc;', '&#128;', '&#153;', '&#65533;'), '', $html);
        // dump($html);
        // echo $html;

        
        return base64_encode($message);

    }

    public function resizeImage($image, $requiredSize)
    {

        $width = $image->width();
        $height = $image->height();
    
        // Check if image resize is required or not
        if ($requiredSize >= $width && $requiredSize >= $height) return $image;
    
        $newWidth;
        $newHeight;
    
        $aspectRatio = $width/$height;
        if ($aspectRatio >= 1.0) {
            $newWidth = $requiredSize;
            $newHeight = $requiredSize / $aspectRatio;
        } else {
            $newWidth = $requiredSize * $aspectRatio;
            $newHeight = $requiredSize;
        }
    
        $image->resize($newWidth, $newHeight);

        return $image;

    }

    public function getNumberOfTickets()
    {

        $ticketsCtr = 0;
        // Number of tickets within 6AM - 2PM PHT (office hours)
        $period = \Carbon\CarbonPeriod::create('2022-01-03', '2022-02-17');
        $arrDates = [];

        // Iterate over the period
        foreach ($period as $date) {
            //loop through dates
            $ticketsCtr += $tickets = Ticket::excludeEbay()
                                        ->whereBetween('created_at', [ $date->format('Y-m-d') . ' 08:00:00', $date->format('Y-m-d') . ' 16:00:00'])
                                        ->count();
            // dump( $date->format('Y-m-d') . ' => ' .$tickets);
            dump($tickets);

            array_push($arrDates, $date->format('Y-m-d'));
        }   

        $arrDates = array_chunk($arrDates, 2);

        // dump($arrDates);
        dump('---');
        // get tickets count outside office hours
        foreach ($arrDates as $arrDate) {

            if ( count($arrDate) == 2 )
            {
                // loop through dates
                $ticketsCtr += $tickets = Ticket::excludeEbay()
                                  ->whereBetween('created_at', [ $arrDate[0] . ' 16:00:01', $arrDate[1] . ' 07:59:59'])
                                  ->count();
                // dump($arrDate[0] . ' - ' . $arrDate[1] . ' => ' . $tickets);
                dump($tickets);
            }
            else
            {
                $endDate = \Carbon\Carbon::parse($arrDate[0])->addDay()->format('Y-m-d');
                dd($endDate);
                // loop through dates
                $ticketsCtr += $tickets = Ticket::excludeEbay()
                                  ->whereBetween('created_at', [ $arrDate[0] . ' 16:00:01', $endDate . ' 07:59:59'])
                                  ->count();    
                // dump($arrDate[0] . ' - ' . $endDate . ' => ' . $tickets);
                dump($tickets);
            }
        }

        dump('Ticket Counter: ' . $ticketsCtr);
    }

    public function countAgentsTickets()
    {
        // $users = User::whereIn('id', [8,9])->get();
        $users = User::whereIn('id', [9])->get();

        foreach ($users as $user)
        {
            $tickets = $user->tickets()->excludeEbay()->whereBetween('created_at', ['2022-01-03', '2022-02-17'])->get();
            // $tickets = $user->tickets()->excludeEbay()->whereBetween('created_at', ['2022-02-01', '2022-02-17'])->get();
            dd( $user->name . ' => ' . count($tickets) );
            // dd( $tickets->toArray() );
            

            $ticketCtr            = 0; // ticket ctr with agent reply
            $totalAgentReplyCount = 0;

            if ( count($tickets) > 500 )
            {

                $chunkedTickets = array_chunk($tickets->toArray(), 100);
                // dd($chunkedTickets);

                foreach ($chunkedTickets as $chunkedTicket)
                {

                    foreach ($chunkedTicket as $ticket)
                    {

                        $agentReplyCount = Message::where('ticket_id', $ticket['id'])->where('from', 'support@frankiesautoelectrics.com.au')->count();
                        // dump($ticket->id . ' => ' .$agentReplyCount);

                        if ($agentReplyCount)
                        {
                            $ticketCtr++;
                            $totalAgentReplyCount += $agentReplyCount;
                        }

                    }

                    usleep(3000000);
                }

            }
            else
            {
                foreach ($tickets as $ticket)
                {
                    $agentReplyCount = $ticket->messages->whereIn('from', 'support@frankiesautoelectrics.com.au')->count();
                    // dump($ticket->id . ' => ' .$agentReplyCount);

                    if ($agentReplyCount)
                    {
                        $ticketCtr++;
                        $totalAgentReplyCount += $agentReplyCount;
                    }
                    // usleep(400000);
                }
            }

            dump('Ticket Counts: ' . $ticketCtr);
            dump('Total Agent Reply Count: ' . $totalAgentReplyCount);
            dump('Avg Agent Reply Count: ' . number_format($totalAgentReplyCount / $ticketCtr, 2) );

        }

        // dd( $user->tickets()->excludeEbay()->whereBetween('created_at', ['2022-01-03', '2022-02-17'])->count() );
    }

    public function getNotificationIntervalCondition($reminderIntervalRecord, $interval)
    {

        $condition = '';

        if ( $interval->day )
        {
            $condition = $reminderIntervalRecord->updated_at->addDays($interval->day);
        }

        if ( $interval->hour )
        {
            $condition = $reminderIntervalRecord->updated_at->addHours($interval->hour);
        }

        if ( $interval->minute )
        {
            $condition = $reminderIntervalRecord->updated_at->addMinutes($interval->minute);
        }


        return $condition;

    }

    public function regenerate()
    {
        dd(99);
        // session()->regenerate();
    }

    public function get_users_limit_not_reached()
    {

        $users = User::faeAgentsExcludeAnne()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();

        foreach($users as $key => $user)
        {

            if ( $this->count_user_tickets($user->id) >= $user->ticketLimit->first()->limit && $user->ticketLimit->first()->limit != 0 )
            {
                unset($users[$key]);
            }

        }

        return $users->toArray();

    }

    public function is_user_ticket_limit_reached($userId)
    {
        $userActiveTicketCount = $this->count_user_tickets($userId);
        $userTicketLimit       = User::find($userId)->ticketLimit->first()->limit;

        return ($userActiveTicketCount >= $userTicketLimit) ? true: false;
    }

    public function count_user_tickets($userId)
    {
        return User::find($userId)->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->count();
    }

    public static function decodeMessage($message)
    {

        $_message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message)); 
        $_message = quoted_printable_decode($_message);
        /*if (strpos($_message, '<div class="gmail_quote">'))
        {
            $_message = substr($_message, 0, strpos($_message, '<div class="gmail_quote">'));
        }*/

        return $_message;

    }

    public function getRecentMessages($limit = 20)
    {
        // $messages = Message::orderByDesc('created_at')->limit($limit)->get(['ticket_id','message','message_id']);
        $messages = Message::where('from','Brandbeast')->orderByDesc('created_at')->limit($limit)->get(['ticket_id','message','message_id']);

        // return $messages;

        foreach($messages as $key => $message)
        {
            $decodedMessage = $this->decodeMessage($message->message);
            $decodedMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $decodedMessage );
            $decodedMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $decodedMessage );
            $decodedMessage = str_replace("progress-bar", "", $decodedMessage);
            $tmpMessage        = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');

            //temporary fix for plain text, for some reason on some messages the gmail only returns text/plain and not text/html
            //if message has no html, automatically add <p> on every white/breaklines
            if ( $tmpMessage == strip_tags($tmpMessage)  )
            {
                $tmpMessage = preg_replace("/[\r\n]/","<p></br>",$tmpMessage);
            }

            $key += 1;

            echo '<hr>';
            echo '--'.$key.'-----------------------'.$message->ticket_id.' - '.$message->message_id.'---------------------------<br>';
            echo $tmpMessage;
            // echo $tmpMessage . '<br>-----------------------------------------------------<br>';
        }

    }

    public function ticketsNotAssigned()
    {
        $assignedTickets    = AssignedTicket::pluck('ticket_id')->all();
        $ticketsNotAssigned = Ticket::whereNotIn('id', $assignedTickets)->select('id','created_at')->get()->toArray();
        
        $str = '';
        foreach($ticketsNotAssigned as $data)
        {
            $str .= $data['id'].',';
        }
        dd($str);
        dd(99);
    }

    public function testAssignTickets($random = 0, Array $tickets, Array $userIds)
    {
        // logger($users);

        if ( !isset($users[0]) ) // if not array of key values then create one for the current(), and next() function to work
        {
            $users = User::select('id','name')->whereIn('id', $userIds)->withCount(['tickets' => function($q){
                $q->where('status_id', TicketStatus::STATUS_PENDING);
            }])->orderBy('tickets_count', 'asc')->limit(1)->get()->toArray();

            $data[0]     = $users;
            $users       = $data;
        }

        if ( $random )
        {

            $users = User::select('id','name')->whereIn('id', $userIds)->withCount(['tickets' => function($q){
                $q->where('status_id', TicketStatus::STATUS_PENDING);
            }])->orderBy('tickets_count', 'asc')->limit(1)->get()->toArray();

            // $allUsers = $users;
            // $user     = current($users);

            foreach ( $tickets as $ticket )
            {
                $users = User::select('id','name')->whereIn('id', $userIds)->withCount(['tickets' => function($q){
                    $q->where('status_id', TicketStatus::STATUS_PENDING);
                }])->orderBy('tickets_count', 'asc')->limit(1)->get()->toArray();

                $allUsers = $users;
                $user     = current($users);
                dump($user);

                // $this->assignTicket($ticket, $user);
                dump('randomly assign to: ' . $user['name']);
                // $user = next($users);

                // if(!$user)
                // {
                //     $users = $allUsers;
                //     $user  = current($users);
                // }
                
            }

        }
        else
        {

            foreach ( $tickets as $ticket )
            {
                // $this->assignTicket($ticket, $user);
                dump('not random assign: ' . $user['name']);
            }

        }

    }

    public function test_query(Request $request)
    {

        $tmpAgents = User::withCount(['tickets'])->teamThea()->get();
        dd($tmpAgents);

        $customerName = 'Rodney DC';
        $orderNumber = '1242421';
        $emailContent = '<table border="0" cellpadding="0" cellspacing="0" class="body" style="background-color:#eff4fa;width:100%">';
        $emailContent .= '<tbody>';
        $emailContent .= '<tr>';
        $emailContent .= '<td>&nbsp;</td>';
        $emailContent .= '<td class="container" style="display:block;margin:0 auto!important;max-width:580px;padding:10px;width:580px">';
        $emailContent .= '<div class="content" style="box-sizing:border-box;display:block;margin:0 auto;max-width:580px;padding:10px">';
        $emailContent .= '<table class="main" style="background:#fff;border-radius:3px;width:100%">';
        $emailContent .= '<tbody>';
        $emailContent .= '<tr>';
        $emailContent .= '<td class="wrapper" style="box-sizing:border-box;padding:20px">';
        $emailContent .= '<table border="0" cellpadding="0" cellspacing="0">';
        $emailContent .= '<tbody>';
        $emailContent .= '<tr>';
        $emailContent .= '<td>';
        $emailContent .= '<p style="margin-bottom: 25px;">Hi '.$customerName.', good day!</p>';
        $emailContent .= '<p style="margin-bottom: 25px;">We hope you are doing well!</p>';
        $emailContent .= '<p>Thank you for placing your order with us! We received your purchase with Order <b>#'.$orderNumber.'</b> and it has been lined up for dispatch.</p>';
        $emailContent .= '<p>We are chasing with our warehouse to check for any updates and we will revert back to you.</p>';
        $emailContent .= '<p>We sincerely apologize for any inconvenience in the delay and hope you will be waiting for your item.</p>';
        $emailContent .= '<p>Thank you for your patience, kind understanding, and continued overwhelming support by ordering with us.</p>';
        $emailContent .= '<p>Feel free to let us know if there’s anything else we can help you with.</p>';
        $emailContent .= '<p>Keep safe and have a lovely day!</p>';
        $emailContent .= '<br>';
        $emailContent .= '<p style="margin-bottom: 0px;">Many Thanks,</p>';
        $emailContent .= '<p style="margin-top: 0px;">Frankies Auto Electrics and Car Audio</p>';
        $emailContent .= '</td>';
        $emailContent .= '</tr>';
        $emailContent .= '</tbody>';
        $emailContent .= '</table>';
        $emailContent .= '</td>';
        $emailContent .= '</tr>';
        $emailContent .= '</tbody>';
        $emailContent .= '</table>';
        $emailContent .= '</div>';
        $emailContent .= '</td>';
        $emailContent .= '<td>&nbsp;</td>';
        $emailContent .= '</tr>';
        $emailContent .= '</tbody>';
        $emailContent .= '</table>';

        echo $emailContent;
        dd();

        $exec_loads = sys_getloadavg();
        $exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
        $cpu = round($exec_loads[1]/($exec_cores + 1)*100, 0) . '%';
        dump('CPU: '.$cpu);

        $exec_free = explode("\n", trim(shell_exec('free')));
        $get_mem = preg_split("/[\s]+/", $exec_free[1]);
        $mem = round($get_mem[2]/$get_mem[1]*100, 0) . '%';
        dump('RAM(%): '.$mem);

        $exec_free = explode("\n", trim(shell_exec('free')));
        $get_mem = preg_split("/[\s]+/", $exec_free[1]);
        $mem = number_format(round($get_mem[2]/1024/1024, 2), 2) . '/' . number_format(round($get_mem[1]/1024/1024, 2), 2);
        dd('RAM(GB): '.$mem);


        dd();

        $user = User::withTrashed()->where('id', 27)->first();
        $tickets = $user->tickets()->where('status_id', TicketStatus::STATUS_PENDING)->orderBy('updated_at', 'DESC')->get();;
        
        foreach ($tickets as $key => $ticket) {
            // dump($ticket->id);

            $randomId = rand(25,26);

            // $reAssignTicket = AssignedTicket::where(['ticket_id' => $ticket->id])->first()->update(['user_id' => $randomId]);

            // logger('Ticket#' . $ticket->id . ' has been re-assigned to user_id:' . $randomId . ' by the Admin');

        }

        dd(99);


        // default to Karina - handles AF tickets
        $users = User::whereIn('id', [18])->withCount(['tickets' => function($q){
                        $q->where('status_id', TicketStatus::STATUS_PENDING)
                            ->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)
                            ->orWhere('type_id', TicketType::TYPE_AWAITING_SHIPMENT);
                    }])
                    ->orderBy('tickets_count', 'asc')->get();

        if ( !$users->count() )
        {
            $users = User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
        }
        dd($users);
        dd(123);

        $t = GmailApi::threadDataDump('18a8ebfd9e3ece79');
        dd($t);

        $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
        $userIds       = array_column($usersSchedule,'user_id');
        dump($userIds);
        dd(array_rand($userIds));

        $t = GmailApi::threadDataDump('18a405db3e744554');
        dd($t);

        $timeStart = microtime(true);
        $tickets = Ticket::whereNotIn('origin_id', [TicketOrigin::ORIGIN_FACEBOOK,TicketOrigin::ORIGIN_EBAY])->with(['messages','categories','assignedTo','origin','status','type','priority'])->whereIn('status_id', [TicketStatus::STATUS_CLOSED])->whereBetween('updated_at', 

                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                        )->orderBy('updated_at', 'DESC')->paginate(20);

        dump(microtime(true) - $timeStart);

        dd();
        $users  = User::ebayAgents()->get()->toArray();
        dump($users);

        $users = User::where('id', 9)->get()->toArray();
        dd($users);

        dd();
        // dd( \Carbon\Carbon::now()->startOfDay()->toISOString() );
        // $t = GmailApi::threadDataDump('187dae04149b4977');
        // dd($t);

        $ebay         = new EbayAPI;

        $ebay->tmpDumpEbayMessages();
        dd(9);

        $messageIds = array("2785687227016");
        $messageIds = array_chunk($messageIds,2);

        $messageIds = ['2746790895011'];

        $messages = $ebay->getTmpMessages($messageIds);
        foreach( $messages->Messages->Message as $message )
        {
            echo $message->Text;
        }


        dd(9);

        $message = 'PGJyPuKAi0hpIEFuZHJldyzCoDxicj7CoDxicj5UaGFua3MgZm9yIGxldHRpbmcgdXMga25vd-KAiyB0aGF0IHlvdXIgcGFyY2VsIGFscmVhZHkgYXJyaXZlZC7CoDxicj7CoDxicj5JZiB5b3UgaGF2ZSBhbnkgcXVlc3Rpb25zIG9yIGNvbmNlcm5zLCBwbGVhc2UgZG8gbm90IGhlc2l0YXRlIHRvIGNvbnRhY3QgdXMuPGJyPsKgPGJyPsKgPGJyPldhcm0gcmVnYXJkcyw8YnI-wqA8YnI-QnJhbmRiZWFzdDxicj4';

        $result = $html     = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message));
        $hostName = parse_url(url('/'))['host'];

        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        foreach ($doc->getElementsByTagName('a') as $link) {

            $href = $link->getAttribute('href');

            if ( !Str::contains($href, $hostName) )
            {
                $link->setAttribute('target', '_blank');
            }

        }

        $result =  $doc->saveHTML();

        $decodedMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $result );
        // $decodedMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?\>/si', ' ', $decodedMessage );
        $decodedMessage = str_replace("progress-bar", "", $decodedMessage);
        $a = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');

        if ( $a == strip_tags($a)  )
        {
            $a = preg_replace("/[\r\n]/","<p></br>",$a);
        }

        echo $a;
        dd($a);


        $_tmpTicket        = new Ticket;
        $annesTicketsCount = $_tmpTicket->count_user_tickets_today(9); // user id - anne
        dd($annesTicketsCount);
        if ( $annesTicketsCount >= 3 )
        {

            $key = array_search(9, $userIds);
            if( $key )
            {
                unset($userIds[$key]);
            }

        }

        $t = GmailApi::threadDataDump('18783ee6513fe205');
        dd($t);

        $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
        $userIds       = array_column($usersSchedule,'user_id');
        dump($usersSchedule);
        dump($userIds);
        $key = array_search(18, $userIds);
        if( $key )
        {
            unset($userIds[$key]);
        }
        dd($userIds);


        $ticket = new Ticket;
        $annesTickets = $ticket->count_user_all_tickets(9);
        dd($annesTickets);
        //account creation emailer
        /*$users = User::whereIn('id', [2,4,22,23,24])->get();
        // $users = User::whereIn('id', [23])->get();


        foreach($users as $user)
        {

            $tmpPassword = 'fae2023';
            $result = Mail::to($user->email)
                            ->bcc('theodore@frankiesautoelectrics.com.au')
                            ->send(new NotifyAccountCreation(
                                    $user->name,
                                    $user->email,
                                    $tmpPassword
                                )
                            );
            dump($result);
        }*/
        dd(9);


        //end account creation emailer...




        // $t = GmailApi::threadDataDump('1860f7e167497571');
        // dd($t);

        /*$str1 = 'PCFET0NUWVBFIGh0bWwgUFVCTElDICItLy9XM0MvL0RURCBIVE1MIDQuMCBUcmFuc2l0aW9uYWwvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvVFIvUkVDLWh0bWw0MC9sb29zZS5kdGQiPgo8aHRtbD48Ym9keT48ZGl2PkhpIE11aGFtbWFkLDwvZGl2Pgo8ZGl2PiZuYnNwOzwvZGl2Pgo8ZGl2Pkdvb2QgbW9ybmluZyE8L2Rpdj4KPGRpdj4mbmJzcDs8L2Rpdj4KPGRpdj4KPGRpdj5XZSB3YW50IHRvIGNvbmZpcm0gdGhhdCB3ZSByZWNlaXZlZCB0aGUgMXggRE5BIEFYQTE5OSB5b3UgcmV0dXJuZWQuPGJyPiZuYnNwOzxicj5QbGVhc2UgbGV0IHVzIGtub3cgaWYgeW91IHdpc2ggdG8gc3dhcCBpdCB3aXRoIGFub3RoZXIgaXRlbSBvciBnZXQgYSByZWZ1bmQgd2l0aCBhIDIwJSByZXN0b2NraW5nIGZlZS48L2Rpdj4KPGRpdj48YnI+WW91IG1heSBhbHNvIGdldCB0aGUgZXF1aXZhbGVudCBhbW91bnQgYXMgc3RvcmUgY3JlZGl0cyB3aGljaCB5b3UgbWF5IHVzZSBvbiB5b3VyIG5leHQgcHVyY2hhc2UuPGJyPiZuYnNwOzxicj5XZSBob3BlIHRvIGhlYXIgZnJvbSB5b3Ugc29vbi48YnI+Jm5ic3A7PGJyPk1hbnkgVGhhbmtzLDxicj5GcmFua2llcyBBdXRvIEVsZWN0cmljcyBhbmQgQ2FyIEF1ZGlvPC9kaXY+CjxkaXY+Jm5ic3A7PC9kaXY+CjxkaXY+Jm5ic3A7PC9kaXY+CjwvZGl2PjwvYm9keT48L2h0bWw+Cg==';
        $str2 = 'PCFET0NUWVBFIGh0bWwgUFVCTElDICItLy9XM0MvL0RURCBIVE1MIDQuMCBUcmFuc2l0aW9uYWwvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvVFIvUkVDLWh0bWw0MC9sb29zZS5kdGQiPgo8aHRtbD48Ym9keT48ZGl2Pgo8ZGl2PkhpIE11aGFtbWFkLDxicj4mbmJzcDs8YnI+R29vZCBtb3JuaW5nITxicj4mbmJzcDs8YnI+V2Ugd2FudCB0byBjb25maXJtIHRoYXQgd2UgcmVjZWl2ZWQgdGhlIDF4IEROQSBBWEExOTkgeW91IHJldHVybmVkLjxicj4mbmJzcDs8YnI+UGxlYXNlIGxldCB1cyBrbm93IGlmIHlvdSB3aXNoIHRvIHN3YXAgaXQgd2l0aCBhbm90aGVyIGl0ZW0gb3IgZ2V0IGEgcmVmdW5kIHdpdGggYSAyMCUgcmVzdG9ja2luZyBmZWUuPC9kaXY+CjxkaXY+Jm5ic3A7PC9kaXY+CjxkaXY+WW91IG1heSBhbHNvIGdldCB0aGUgZXF1aXZhbGVudCBhbW91bnQgYXMgc3RvcmUgY3JlZGl0cyB3aGljaCB5b3UgbWF5IHVzZSBvbiB5b3VyIG5leHQgcHVyY2hhc2UuPGJyPiZuYnNwOzxicj5XZSBob3BlIHRvIGhlYXIgZnJvbSB5b3Ugc29vbi48YnI+Jm5ic3A7PGJyPk1hbnkgVGhhbmtzLDxicj5GcmFua2llcyBBdXRvIEVsZWN0cmljcyBhbmQgQ2FyIEF1ZGlvPC9kaXY+CjwvZGl2PjwvYm9keT48L2h0bWw+Cg==';

        $str1 = strip_tags(base64_decode($str1));
        $str2 = strip_tags(base64_decode($str2));
        $str1 = preg_replace("/\s|' '|&nbsp;/",'',$str1);
        $str2 = preg_replace("/\s|' '|&nbsp;/",'',$str2);

        $str1 = base64_decode($str1);
        $str2 = base64_decode($str2);

        echo base64_encode($str1);
        echo '<br>';
        echo base64_encode($str2);
        echo '<br>';

        if ($str1 == $str2)
        {
            dump('true');
        }
        else
        {
            dump('false');
        }

        dd();*/

        // $messages = Message::select('message')->where('ticket_id', 55287)->get();

        // dd($messages);


        $start = \Carbon\Carbon::parse('2023-03-15 00:00:00')->toDateTimeString();
        $end   = \Carbon\Carbon::parse('2023-03-15 23:59:59')->toDateTimeString();

        //where unique column
        $awaitingFulfillmentAndShipmentTicketTypes = [TicketType::TYPE_AWAITING_FULFILLMENT, TicketType::TYPE_AWAITING_SHIPMENT];
        // $messages = Message::whereHas('ticket', function($a){
        //                         $a->whereNotIn('type_id', $awaitingFulfillmentAndShipmentTicketTypes);
        //                     })
        $messages = Message::with('ticket')
                            ->whereHas('activityLogs', function($b){
                                $b->where('subject_type', 'App\Message')
                                  ->where('causer_id', 18);
                            })
                            // do not include assignedTo as its possible that an agent will reply on other agents tickets
                            // ->whereHas('assignedTo', function($q){
                            //     $q->where('user_id', 18);
                            // })
                            // ->where('ticket_id', 54849)
                            ->where('from', 'support@frankiesautoelectrics.com.au')
                            ->whereBetween('created_at', [$start, $end])
                            ->orderBy('ticket_id')
                            ->get();

        // dd($messages);
        // dump('Messages Count: '.$messages->count());



        // ! do not include assignedTo as its possible that an agent will reply on other agents tickets
        // ! find a way to not count duplicate message in a ticket
        // add condition if its AF and message is not the first, then count...
        $ctr            = 0;
        $ticketMessages = [];
        foreach($messages as $message)
        {
            $countIt = true;
            $ticket = $message->ticket;

            $key = empty($ticket->subject) ? $ticket->id : $ticket->subject;

            $tmpMsg = $message->message;
            $tmpMsg = strip_tags(base64_decode($tmpMsg));
            $tmpMsg = preg_replace("/\s|' '|&nbsp;/",'',$tmpMsg);
            $message->message = base64_encode($tmpMsg);

            if ( empty($ticketMessages) )
            {
                $ticketMessages[$ticket->id][] = $message->message;
            }
            else
            {
                if ( isset($ticketMessages[$ticket->id]) )
                {
                    if ( in_array($message->message, $ticketMessages[$ticket->id]) )
                    {
                        //exclude or use not in array
                        $countIt = false;
                    }
                    else
                    {
                        $ticketMessages[$ticket->id][] = $message->message;
                    }
                }
                else
                {
                    $ticketMessages = [];
                    $ticketMessages[$ticket->id][] = $message->message;
                }
            }


            if ( $countIt )
            {

                if( in_array($ticket->type_id, $awaitingFulfillmentAndShipmentTicketTypes) )
                {
                    $firstMessageId = $ticket->messages->first()->id;

                    if ($message->id != $firstMessageId)
                    {
                        if ( !isset($data[$key]) )
                        {
                            $data[$key] = 1; $ctr++;
                        }
                        else
                        {
                            $data[$key] += 1; $ctr++;
                        }
                    }
                }
                else
                {
                    if ( !isset($data[$key]) )
                    {
                        $data[$key] = 1; $ctr++;
                    }
                    else
                    {
                        $data[$key] += 1; $ctr++;
                    }
                }

            }


            

            if ( empty($ticket->order_number) )
            {
                echo '-'.$ticket->id.'-<br>';
            }
            else
            {
                echo $ticket->order_number.'<br>';
            }
            // echo 'Ticket#: '.$ticket->id.' | Order #: '.$ticket->order_number.'<br>';
            // echo 'Ticket#'.$ticket->id.' '.$ticket->subject.' - '.$ticket->status_id.'<br>';
            // echo 'Id: '.$ticket->id.' Order #: '.$ticket->order_number.' Subject: '.$ticket->subject.' Type Id: '.$ticket->type_id.'<br>';
        }
        dump('Messages Count: '.$ctr);
        dd($data);

        $t = GmailApi::threadDataDump('186c3ab8a40311f6');
        // $t = GmailApi::threadDataDump('186a540b52d88aa4');
        dd($t);

        dd(123);
        $firstDayofPreviousMonth = \Carbon\Carbon::now()->startOfMonth()->subMonthsNoOverflow()->toDateString();
        $lastDayofPreviousMonth  = \Carbon\Carbon::now()->subMonthsNoOverflow()->endOfMonth()->toDateString();

        $user = User::where('id', 9)->with(['tickets' => function($q) use($firstDayofPreviousMonth, $lastDayofPreviousMonth){
            $q->whereBetween('created_at', [$firstDayofPreviousMonth, $lastDayofPreviousMonth]);
        }])->first();

        // $tickets = Ticket::whereHas(['user' => function($q){
        //     $q->where('id', 9);
        // }])->whereBetween('created_at', [$firstDayofPreviousMonth, $lastDayofPreviousMonth])->get()->count();

        dd($user);
        dd();

        $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
        $userIds       = array_column($usersSchedule,'user_id');
        $users = User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
        dump($usersSchedule);
        dump($users);
        dd($userIds);

        dd(\Carbon\Carbon::now()->format('M d, Y H:i:s'));

        $timeStart = microtime(true);
        $usersWithPendingTickets = User::where('id', Auth::id())->whereHas('tickets', function($q){
                                            $q->where('status_id', Ticket::STATUS_PENDING);
                                        })->first();
        // $usersWithPendingTickets = User::where('id', Auth::id())->with(['tickets' => function($q){
        //                                     $q->where('status_id', Ticket::STATUS_PENDING);
        //                                     $q->with('messages');
        //                                 }])->first();


        dump(microtime(true) - $timeStart);
        dump($usersWithPendingTickets->tickets->count());
        dump($usersWithPendingTickets);
        dd(implode(',', array_column($usersWithPendingTickets->tickets->toArray(), 'id')));


        $reminders = Reminder::systemReminders()->get();
        dd($reminders);

        $t = GmailApi::threadDataDump('186728fb2e1ae079');
        dd($t);


        $users = User::whereIn('id', [18])->withCount(['tickets' => function($q){
                                    $q->where('status_id', TicketStatus::STATUS_PENDING)
                                        ->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)
                                        ->orWhere('type_id', TicketType::TYPE_AWAITING_SHIPMENT);
                                }])
                                ->orderBy('tickets_count', 'asc')->get();

        dd($users);


        $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
        dump($usersSchedule);
        $userIds       = array_column($usersSchedule,'user_id');
        dump($userIds);
        dump(empty($userIds));

        dd(User::managers()->withCount('tickets')->orderBy('tickets_count', 'asc')->get());

        $users = User::whereIn('id', $userIds)->withCount(['tickets' => function($q){
                        $q->where('status_id', TicketStatus::STATUS_PENDING)
                            ->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)
                            ->orWhere('type_id', TicketType::TYPE_AWAITING_SHIPMENT);
                    }])
                    ->orderBy('tickets_count', 'asc')->get();
        dd($users);

        $t = GmailApi::threadDataDump('18658f047ee09b5c');
        dd($t);

        $ids = [53998,53997,53996,53995,53994,53993,53992,53991,53990,53989,53988,53987,53986,53985,53984,53983,53982,53981,53980,53979,53978,53977,53976,53975,53974,53973,53972,53971,53970,53969,53968,53967,53966,53965,53964,53963,53962,53961,53960,53959,53958,53957,53956,53955,53954,53953,53952,53951,53950,53949,53948,53947,53946,53945,53944,53943,53942,53941,53940,53939,53938,53937,53936,53935,53934,53933,53932,53931,53930,53929,53928,53927,53926,53925,53924,53923,53922,53921,53920,53919,53918,53917,53916,53915,53914,53913,53912,53911,53910,53909,53908,53907,53906,53905,53904,53903,53902,53901,53900,53899,53898,53897,53896,53895,53894,53893,53892,53891,53890,53889,53888,53887,53886,53885,53884,53883,53882,53881,53880,53879,53878,53877,53876,53875,53874,53873,53872,53871,53870,53869,53868,53867,53866,53865,53864,53863,53862,53861,53860];

        // $users = User::whereIn('id', $userIds)->withCount(['tickets' => function($q){
        //                         $q->where('status_id', TicketStatus::STATUS_PENDING);
        //                     }])
        //                     ->orderBy('tickets_count', 'asc')->get();

        $tickets = Ticket::whereIn('id', $ids)->withCount('messages')->get();

        // dd($tickets);

        foreach($tickets as $ticket)
        {
            dump($ticket->id .' : '. $ticket->messages_count);
        }


        dd(9);

        $tickets = Ticket::excludeFacebook()
                    ->where('read', false)
                    ->whereHas('messages', function($q){
                        $q->latest()->whereNotIn('from', ['support@frankiesautoelectrics.com.au', 'sales@frankiesautoelectrics.com.au','eBay','csfeedback@go.ebay.com','kucia6666'])
                          ->orderBy('created_at', 'DESC');
                    })
                    ->where('created_at', '>', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                    ->orderBy('updated_at', 'DESC')->get();

        dump($tickets->count());
        foreach($tickets as $ticket)
        {
            // if ( $ticket->messages->count() )
            // {
                dump($ticket->messages->first()->from);
            // }
        }

        dd(9);

        $t = GmailApi::tmpBuildTicketData('185fba9a56d97cd8');
        dd($t);

        $t = new Emailer;
        dd($t);


        $ticketType = TicketType::active()->where('name','Awaiting Fulfillment')->first();
        dd($ticketType->id);

        dd(9);
        $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
        $userIds       = array_column($usersSchedule,'user_id');
        $users = User::select('id','name')->whereIn('id', $userIds)->withCount(['tickets' => function($q){
            $q->where('status_id', TicketStatus::STATUS_PENDING);
        }])->orderBy('tickets_count', 'asc')->get()->toArray();
        dump($users);

        // $totalTicketsCount = array_sum(array_column($users,'tickets_count'));

         /*testing for weighted distribution
         * weight for each user tickets ( use reversed weight distribution to prioritized those with less tickets)
         *  - if  there is more than 1 with equal weighted avg. then just use random pick of those equal user tickets weight
         * set loop conditions for each weight
         * set if conditions true then assign ticket
         * set if conditions true then assign ticket*/
        
        // $users = array_map(function($d) use($totalTicketsCount){

        //     $d['weightDistribution']         = round($d['tickets_count'] / $totalTicketsCount, 2);
        //     $d['reversedWeightDistribution'] = ( 1 - round($d['tickets_count'] / $totalTicketsCount, 2) );

        //     return $d;

        // }, $users);

        // dd($users);
        $ticketIds = [49232,49231,49230,49229,49228,49227,49226,49225,49224,49223,49222,49221,49220,49219,49218,49217,49216];

        $tickets = Ticket::whereIn('id', $ticketIds)->get()->toArray();
        $this->testAssignTickets(true, $tickets, $userIds);

        dd(1);


        $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
        $userIds       = array_column($usersSchedule,'user_id');
        // Log::info('create tickets and assign with one of these ids: ' . implode(",",$userIds));
        $users         = User::whereIn('id', $userIds)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
        $this->testAssignTickets( true, [1,3,4,5,9,8,12], $users->toArray() );
        dd(12345);
        $t = GmailApi::threadDataDump('184e5554855fcff7');
        dd($t);

        $messages = $this->getRecentMessages(100);

        dd($messages);
        
        $ebay         = new EbayAPI;
        dd(EbayAPI::uploadFile());

        $image = url('/') . Storage::url('public/attachments/c7e0ead67157.jpg');
        dd($image);


        $t = GmailApi::threadDataDump('184a1ac3de266671');
        dd($t);

        $url = URL::current();
        dump($url);
        if (strpos($url, 'tickets/spam'))
        {
            dd('inn');
        }
        else
        {
            dd('not found');
        }

        $ticket = Ticket::withTrashed()->with(['messages', 'categories', 'tags'])->where('id', 41319)->first();
        dd($ticket);
        $ticket->thread_started_at = \Carbon\Carbon::parse($ticket->thread_started_at)->diffForHumans();


        $t = GmailApi::threadDataDump('18487ef80592691d');
        dd($t);

        /*dd(GmailApi::getEmailSpamFilter());

        dd($this->getRecentMessages(45));

        $t = GmailApi::threadDataDump('1835ded2ae61e1fd');
        dd($t);*/
        
        $ebay         = new EbayAPI;
        dd(EbayAPI::uploadFile());

        

        $t = GmailApi::threadDataDump('1835ded2ae61e1fd');
        dd($t);



        // @if ( !empty($_message) && ( strpos($_message->from, $emailSupportAddress) !== false || strpos($_message->from, $user->email) !== false || $_message->from == 'Brandbeast' || $_message->from == $userNameEmail ) )

        for($i = 0; $i <= 100; $i++)
        {

            $t1 = microtime(true);
            $ticket = Ticket::find(38740);
            // $messages = $ticket->getLastMessage();
            $ticket = Ticket::with(['messages' => function($q){ $q->orderBy('id','DESC')->limit(1); }])->where('id',38740)->get('id');
            // dump($ticket);
            // dump($messages);
            $t2 = microtime(true);

            dump($t2 - $t1);

        }

        dd(000);


        $t = GmailApi::threadDataDump('182e3e7db2bf71d7');
        dd($t);

        $messages = $this->getRecentMessages(50);

        dd($messages);

        dd(99);

        $ticketsNotAssigned = $this->ticketsNotAssigned();
        dd(00);


        $message = $this->messageEncode('PGh0bWw-DQo8aGVhZD4NCjxtZXRhIGh0dHAtZXF1aXY9IkNvbnRlbnQtVHlwZSIgY29udGVudD0idGV4dC9odG1sOyBjaGFyc2V0PVdpbmRvd3MtMTI1MiI-DQo8L2hlYWQ-DQo8Ym9keT4NCjxkaXY-PC9kaXY-DQo8YnI-DQo8aHIgc3R5bGU9ImRpc3BsYXk6aW5saW5lLWJsb2NrO3dpZHRoOjk4JSIgdGFiaW5kZXg9Ii0xIj4NCjxkaXYgaWQ9ImRpdlJwbHlGd2RNc2ciIGRpcj0ibHRyIj48Zm9udCBmYWNlPSJDYWxpYnJpLCBzYW5zLXNlcmlmIiBzdHlsZT0iZm9udC1zaXplOjExcHQiIGNvbG9yPSIjMDAwMDAwIj48Yj5Gcm9tOjwvYj4gQXJhbWV4IFdvbGxvbmdvbmcgKEFyYW1leCAoZm9ybWVybHkgRmFzdHdheSBDb3VyaWVycykpICZsdDtzdXBwb3J0QGZhc3R3YXlzdXBwb3J0LnplbmRlc2suY29tJmd0Ozxicj4NCjxiPlNlbnQ6PC9iPiBUdWVzZGF5LCAxNiBBdWd1c3QgMjAyMiA0OjQ2IFBNPGJyPg0KPGI-VG86PC9iPiBBdGQwMyAmbHQ7YXRkMDNAaG90bWFpbC5jb20mZ3Q7PGJyPg0KPGI-U3ViamVjdDo8L2I-IEFyYW1leCAtIEN1c3RvbWVyIGVucXVpcnkgKE1QMDAxNDIwMDgyMyk8L2ZvbnQ-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPC9kaXY-DQo8c3R5bGUgdHlwZT0idGV4dC9jc3MiIHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWYiPg0KPCEtLQ0KKg0KCXttYXJnaW46MDsNCglwYWRkaW5nOjA7DQoJZm9udC1zaXplOjEwMCU7DQoJZm9udC1mYW1pbHk6IkFyaWFsIiwiSGVsdmV0aWNhIixzYW5zLXNlcmlmOw0KCWxpbmUtaGVpZ2h0OjEuNjV9DQppbWcNCgl7bWF4LXdpZHRoOjEwMCU7DQoJZGlzcGxheTpibG9ja30NCmRpdiwgLnhfYm9keS13cmFwDQoJe3dpZHRoOjEwMCUhaW1wb3J0YW50Ow0KCWhlaWdodDoxMDAlOw0KCWJhY2tncm91bmQ6I2Y4ZjhmOH0NCmENCgl7Y29sb3I6I2RjMjkxZTsNCgl0ZXh0LWRlY29yYXRpb246bm9uZX0NCmE6aG92ZXINCgl7dGV4dC1kZWNvcmF0aW9uOnVuZGVybGluZX0NCi54X3RleHQtY2VudGVyDQoJe3RleHQtYWxpZ246Y2VudGVyfQ0KLnhfdGV4dC1yaWdodA0KCXt0ZXh0LWFsaWduOnJpZ2h0fQ0KLnhfdGV4dC1sZWZ0DQoJe3RleHQtYWxpZ246bGVmdH0NCi54X2J1dHRvbg0KCXtkaXNwbGF5OmlubGluZS1ibG9jazsNCgljb2xvcjp3aGl0ZTsNCgliYWNrZ3JvdW5kOiMwMDMxODQ7DQoJYm9yZGVyOnNvbGlkICMwMDMxODQ7DQoJYm9yZGVyLXdpZHRoOjEwcHggMjBweCA4cHg7DQoJZm9udC13ZWlnaHQ6Ym9sZDsNCglib3JkZXItcmFkaXVzOjRweH0NCmgxLCBoMiwgaDMsIGg0LCBoNSwgaDYNCgl7bWFyZ2luLWJvdHRvbToyMHB4Ow0KCWxpbmUtaGVpZ2h0OjEuMjV9DQpoMQ0KCXtmb250LXNpemU6MzJweDsNCgljb2xvcjojNGQ0ZDRkfQ0KaDINCgl7Zm9udC1zaXplOjI4cHg7DQoJY29sb3I6IzRkNGQ0ZH0NCmgzDQoJe2ZvbnQtc2l6ZToyNHB4Ow0KCWNvbG9yOiM0ZDRkNGR9DQpoNA0KCXtmb250LXNpemU6MjBweDsNCgljb2xvcjojNGQ0ZDRkfQ0KaDUNCgl7Zm9udC1zaXplOjE2cHg7DQoJY29sb3I6IzRkNGQ0ZDsNCglmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZn0NCnAsIHVsLCBvbA0KCXtmb250LXNpemU6MTZweDsNCglmb250LXdlaWdodDpub3JtYWw7DQoJbWFyZ2luLWJvdHRvbToyMHB4Ow0KCWNvbG9yOiM0ZDRkNGR9DQoueF9jb250YWluZXINCgl7ZGlzcGxheTpibG9jayFpbXBvcnRhbnQ7DQoJY2xlYXI6Ym90aCFpbXBvcnRhbnQ7DQoJbWFyZ2luOjAgYXV0byFpbXBvcnRhbnQ7DQoJbWF4LXdpZHRoOjU4MHB4IWltcG9ydGFudH0NCi54X2NvbnRhaW5lciB0YWJsZQ0KCXt3aWR0aDoxMDAlIWltcG9ydGFudDsNCglib3JkZXItY29sbGFwc2U6Y29sbGFwc2V9DQoueF9jb250YWluZXIgLnhfbWFzdGhlYWQNCgl7YmFja2dyb3VuZDojZGMyOTFlOw0KCWNvbG9yOndoaXRlfQ0KLnhfY29udGFpbmVyIC54X21hc3RoZWFkIGgxDQoJe21hcmdpbjowIGF1dG8haW1wb3J0YW50Ow0KCW1heC13aWR0aDo5MCV9DQoueF9jb250YWluZXIgLnhfY29udGVudA0KCXtiYWNrZ3JvdW5kOndoaXRlOw0KCXBhZGRpbmc6MzBweCAzNXB4fQ0KLnhfY29udGFpbmVyIC54X2NvbnRlbnQuZm9vdGVyDQoJe2JhY2tncm91bmQ6bm9uZTsNCglwYWRkaW5nOjMwcHggMjVweH0NCi54X2NvbnRhaW5lciAueF9jb250ZW50LmZvb3RlciBwDQoJe21hcmdpbi1ib3R0b206MDsNCgljb2xvcjojODg4Ow0KCWZvbnQtc2l6ZToxMnB4Ow0KCXRleHQtYWxpZ246Y2VudGVyfQ0KLnhfY29udGFpbmVyIC54X2NvbnRlbnQuZm9vdGVyIGENCgl7Y29sb3I6Izg4ODsNCgl0ZXh0LWRlY29yYXRpb246bm9uZTsNCglmb250LXdlaWdodDpib2xkfQ0KLnhfY29udGFpbmVyIC54X2NvbnRlbnQuZm9vdGVyIGE6aG92ZXINCgl7dGV4dC1kZWNvcmF0aW9uOnVuZGVybGluZX0NCi0tPg0KPC9zdHlsZT4NCjxkaXYgc3R5bGU9Im1hcmdpbjowOyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgaGVpZ2h0OjEwMCU7IGJhY2tncm91bmQ6I2Y4ZjhmODsgd2lkdGg6MTAwJSFpbXBvcnRhbnQiPg0KPHRhYmxlIGNsYXNzPSJ4X2JvZHktd3JhcCIgc3R5bGU9Im1hcmdpbjowOyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgaGVpZ2h0OjEwMCU7IGJhY2tncm91bmQ6I2Y4ZjhmODsgd2lkdGg6MTAwJSFpbXBvcnRhbnQiPg0KPHRib2R5Pg0KPHRyIHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjUiPg0KPHRkIGNsYXNzPSJ4X2NvbnRhaW5lciIgc3R5bGU9Im1hcmdpbjowIGF1dG8haW1wb3J0YW50OyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgZGlzcGxheTpibG9jayFpbXBvcnRhbnQ7IGNsZWFyOmJvdGghaW1wb3J0YW50OyBtYXgtd2lkdGg6NTgwcHghaW1wb3J0YW50Ij4NCjxkaXYgc3R5bGU9ImNvbG9yOiNiNWI1YjU7IG1hcmdpbjowOyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NSI-DQojIy0gUGxlYXNlIHR5cGUgeW91ciByZXBseSBhYm92ZSB0aGlzIGxpbmUgLSMjPC9kaXY-DQo8dGFibGUgc3R5bGU9Im1hcmdpbjowOyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgYm9yZGVyLWNvbGxhcHNlOmNvbGxhcHNlOyB3aWR0aDoxMDAlIWltcG9ydGFudCI-DQo8dGJvZHk-DQo8dHIgc3R5bGU9Im1hcmdpbjowOyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NSI-DQo8dGQgY2xhc3M9InhfbWFzdGhlYWQiIHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjU7IGJhY2tncm91bmQ6I2RjMjkxZTsgY29sb3I6d2hpdGUiPg0KPHRhYmxlIHdpZHRoPSIxMDAlIiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjU7IGJvcmRlci1jb2xsYXBzZTpjb2xsYXBzZTsgd2lkdGg6MTAwJSFpbXBvcnRhbnQiPg0KPHRib2R5IHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjUiPg0KPHRyIHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjUiPg0KPHRkIHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjUiPg0KPGltZyB3aWR0aD0iMzc4IiBoZWlnaHQ9IjcwIiBzdHlsZT0iZGlzcGxheTpibG9jazsgcGFkZGluZzowcHg7IGhlaWdodDo3MHB4OyB3aWR0aDozNzhweDsgbWFyZ2luOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgbWF4LXdpZHRoOjEwMCUiIHNyYz0iaHR0cHM6Ly93d3cuYXJhbWV4LmNvbS5hdS9tZWRpYS8yMzAzL2xvZ29oZWFkZXJhcmFtZXgucG5nIj4NCjwvdGQ-DQo8L3RyPg0KPC90Ym9keT4NCjwvdGFibGU-DQo8L3RkPg0KPC90cj4NCjx0ciBzdHlsZT0ibWFyZ2luOjA7IHBhZGRpbmc6MDsgZm9udC1zaXplOjEwMCU7IGZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmOyBsaW5lLWhlaWdodDoxLjY1Ij4NCjx0ZCBjbGFzcz0ieF9jb250ZW50IiBzdHlsZT0ibWFyZ2luOjA7IHBhZGRpbmc6MzBweCAzNXB4OyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjU7IGJhY2tncm91bmQ6d2hpdGUiPg0KPHAgZGlyPSJsdHIiIHN0eWxlPSJtYXJnaW46MDsgcGFkZGluZzowOyBmb250LXNpemU6MTZweDsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjU7IGZvbnQtd2VpZ2h0Om5vcm1hbDsgbWFyZ2luLWJvdHRvbToyMHB4OyBjb2xvcjojNGQ0ZDRkIj4NCjwvcD4NCjxwIGRpcj0ibHRyIj5IaSBhZGFtIGRhZmZpbiw8YnI-DQpPdXIgdGVhbSBhcmUgY3VycmVudGx5IHdvcmtpbmcgb24geW91ciB0aWNrZXQgKDxhIHRhcmdldD0iX2JsYW5rIiBocmVmPSJodHRwczovL2hlbHAuYXJhbWV4LmNvbS5hdS9oYy9yZXF1ZXN0cy82MzkyOTA4Ij4jNjM5MjkwODwvYT4pIGFuZCBoYXZlIHByb3ZpZGVkIGFuIHVwZGF0ZS48YnI-DQpJZiB5b3Ugd2lzaCB0byBhZGQgYW55IGFkZGl0aW9uYWwgY29tbWVudHMsIG9yIHdhbnQgY2xhcmlmaWNhdGlvbiBvbiB0aGUgdXBkYXRlLCBzaW1wbHkgcmVwbHkgdG8gdGhpcyBlbWFpbC48YnI-DQpUcmFja2luZyBudW1iZXI6IE1QMDAxNDIwMDgyMzwvcD4NCjxwIGRpcj0ibHRyIj48L3A-DQo8ZGl2IHN0eWxlPSJtYXJnaW4tdG9wOjI1cHgiPg0KPHRhYmxlIHdpZHRoPSIxMDAlIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCIgcm9sZT0icHJlc2VudGF0aW9uIj4NCjx0Ym9keT4NCjx0cj4NCjx0ZCB3aWR0aD0iMTAwJSIgc3R5bGU9InBhZGRpbmc6MTVweCAwOyBib3JkZXItdG9wOjFweCBkb3R0ZWQgI2M1YzVjNSI-DQo8dGFibGUgd2lkdGg9IjEwMCUiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgYm9yZGVyPSIwIiByb2xlPSJwcmVzZW50YXRpb24iIHN0eWxlPSJ0YWJsZS1sYXlvdXQ6Zml4ZWQiPg0KPHRib2R5Pg0KPHRyPg0KPHRkIHdpZHRoPSIxMDAlIiB2YWxpZ249InRvcCIgc3R5bGU9InBhZGRpbmc6MDsgbWFyZ2luOjAiPg0KPHAgZGlyPSJsdHIiIHN0eWxlPSJmb250LWZhbWlseTonTHVjaWRhIEdyYW5kZScsJ0x1Y2lkYSBTYW5zIFVuaWNvZGUnLCdMdWNpZGEgU2FucycsVmVyZGFuYSxUYWhvbWEsc2Fucy1zZXJpZjsgZm9udC1zaXplOjE1cHg7IGxpbmUtaGVpZ2h0OjE4cHg7IG1hcmdpbi1ib3R0b206MDsgbWFyZ2luLXRvcDowOyBwYWRkaW5nOjA7IGNvbG9yOiMxYjFkMWUiPg0KPHN0cm9uZz5BcmFtZXggV29sbG9uZ29uZzwvc3Ryb25nPiAoQXJhbWV4KSA8L3A-DQo8cCBkaXI9Imx0ciIgc3R5bGU9ImZvbnQtZmFtaWx5OidMdWNpZGEgR3JhbmRlJywnTHVjaWRhIFNhbnMgVW5pY29kZScsJ0x1Y2lkYSBTYW5zJyxWZXJkYW5hLFRhaG9tYSxzYW5zLXNlcmlmOyBmb250LXNpemU6MTNweDsgbGluZS1oZWlnaHQ6MjVweDsgbWFyZ2luLWJvdHRvbToxNXB4OyBtYXJnaW4tdG9wOjA7IHBhZGRpbmc6MDsgY29sb3I6I2JiYmJiYiI-DQpBdWcgMTYsIDIwMjIsIDE4OjQ2IEdNVCsxMiA8L3A-DQo8ZGl2IGNsYXNzPSJ4X3pkLWNvbW1lbnQiIGRpcj0iYXV0byIgc3R5bGU9ImNvbG9yOiMyYjJlMmY7IGxpbmUtaGVpZ2h0OjIycHg7IG1hcmdpbjoxNXB4IDAiPg0KSGkgdGhlcmUsPGJyPg0KJm5ic3A7PGJyPg0KV2UgYXJlIGNvbnRhY3RpbmcgeW91IGluIHJlbGF0aW9uIHRvIHRoZSBhYm92ZSBsYWJlbCBudW1iZXIgZ29pbmcgdG8gQWRhbSBvZiBPeGVuZm9yZCwgNDIxMC48YnI-DQombmJzcDs8YnI-DQpXZeKAmXJlIHNvcnJ5IHRvIGluZm9ybSB5b3UgdGhhdCB5b3VyIHBhY2thZ2UgaGFzIGJlZW4gbG9zdCBpbiB0cmFuc2l0Ljxicj4NCiZuYnNwOzxicj4NCldlIGFwb2xvZ2lzZSBmb3IgdGhlIGluY29udmVuaWVuY2UgYW5kIGhhdmUgYWR2aXNlZCBvdXIgY2xpZW50ICh0aGUgc2VuZGVyL3B1cmNoYXNlcikgdG8gc2VlayBjb21wZW5zYXRpb24uIElmIHRoZXkgaGF2ZSBub3QgY29udGFjdGVkIHlvdSB3aXRoaW4gdGhlIG5leHQgMjQgaG91cnMsIHBsZWFzZSBjb250YWN0IHRoZW0gZGlyZWN0bHkgZm9yIGZ1cnRoZXIgYXNzaXN0YW5jZS48YnI-DQombmJzcDs8YnI-DQpBZ2FpbiB3ZSBhcG9sb2dpc2UgdGhhdCB3ZSBoYXZlIG5vdCBiZWVuIGFibGUgdG8gZGVsaXZlciB5b3VyIGl0ZW0gYW5kIGFwcHJlY2lhdGUgeW91ciB1bmRlcnN0YW5kaW5nLjxicj4NCiZuYnNwOzxicj4NCjxkaXYgY2xhc3M9Inhfc2lnbmF0dXJlIj4NCjxwIGRpcj0ibHRyIiBzdHlsZT0iY29sb3I6IzJiMmUyZjsgbGluZS1oZWlnaHQ6MjJweDsgbWFyZ2luOjE1cHggMCI-QXJhbWV4IFdvbGxvbmdvbmc8YnI-DQpLaW5kIFJlZ2FyZHMsPGJyPg0KQXJhbWV4IChXb2xsb25nb25nKTwvcD4NCjwvZGl2Pg0KPC9kaXY-DQo8cCBkaXI9Imx0ciI-PC9wPg0KPC90ZD4NCjwvdHI-DQo8L3Rib2R5Pg0KPC90YWJsZT4NCjwvdGQ-DQo8L3RyPg0KPC90Ym9keT4NCjwvdGFibGU-DQo8cCBkaXI9Imx0ciI-PC9wPg0KPHRhYmxlIHdpZHRoPSIxMDAlIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCIgcm9sZT0icHJlc2VudGF0aW9uIj4NCjx0Ym9keT4NCjx0cj4NCjx0ZCB3aWR0aD0iMTAwJSIgc3R5bGU9InBhZGRpbmc6MTVweCAwOyBib3JkZXItdG9wOjFweCBkb3R0ZWQgI2M1YzVjNSI-DQo8dGFibGUgd2lkdGg9IjEwMCUiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgYm9yZGVyPSIwIiByb2xlPSJwcmVzZW50YXRpb24iIHN0eWxlPSJ0YWJsZS1sYXlvdXQ6Zml4ZWQiPg0KPHRib2R5Pg0KPHRyPg0KPHRkIHdpZHRoPSIxMDAlIiB2YWxpZ249InRvcCIgc3R5bGU9InBhZGRpbmc6MDsgbWFyZ2luOjAiPg0KPHAgZGlyPSJsdHIiIHN0eWxlPSJmb250LWZhbWlseTonTHVjaWRhIEdyYW5kZScsJ0x1Y2lkYSBTYW5zIFVuaWNvZGUnLCdMdWNpZGEgU2FucycsVmVyZGFuYSxUYWhvbWEsc2Fucy1zZXJpZjsgZm9udC1zaXplOjE1cHg7IGxpbmUtaGVpZ2h0OjE4cHg7IG1hcmdpbi1ib3R0b206MDsgbWFyZ2luLXRvcDowOyBwYWRkaW5nOjA7IGNvbG9yOiMxYjFkMWUiPg0KPHN0cm9uZz5BcmFtZXggV29sbG9uZ29uZzwvc3Ryb25nPiAoQXJhbWV4KSA8L3A-DQo8cCBkaXI9Imx0ciIgc3R5bGU9ImZvbnQtZmFtaWx5OidMdWNpZGEgR3JhbmRlJywnTHVjaWRhIFNhbnMgVW5pY29kZScsJ0x1Y2lkYSBTYW5zJyxWZXJkYW5hLFRhaG9tYSxzYW5zLXNlcmlmOyBmb250LXNpemU6MTNweDsgbGluZS1oZWlnaHQ6MjVweDsgbWFyZ2luLWJvdHRvbToxNXB4OyBtYXJnaW4tdG9wOjA7IHBhZGRpbmc6MDsgY29sb3I6I2JiYmJiYiI-DQpBdWcgMTUsIDIwMjIsIDE2OjA4IEdNVCsxMiA8L3A-DQo8ZGl2IGNsYXNzPSJ4X3pkLWNvbW1lbnQiIGRpcj0iYXV0byIgc3R5bGU9ImNvbG9yOiMyYjJlMmY7IGxpbmUtaGVpZ2h0OjIycHg7IG1hcmdpbjoxNXB4IDAiPg0KSGkgVGhlcmUsPGJyPg0KPGJyPg0KVGhhbmsgeW91IGZvciB5b3VyIGVucXVpcnkuJm5ic3A7PGJyPg0KPGJyPg0KV2UgaGF2ZSBjb250YWN0ZWQgdGhlIHJlbGV2YW50IHBhcnRpZXMgYW5kIHlvdXIgZW5xdWlyeSBpcyBjdXJyZW50bHkgYmVpbmcgYWN0aW9uZWQuPGJyPg0KPGJyPg0KV2Ugd2lsbCBlbmRlYXZvciB0byBmaW5kIGEgcmVzb2x1dGlvbiBpbiB0aGUgc2hvcnRlc3QgcG9zc2libGUgdGltZSBmcmFtZSBhbmQgd2lsbCBiZSBpbiBjb250YWN0IG9uY2UgSSBoYXZlIG1vcmUgaW5mb3JtYXRpb24gZm9yIHlvdS48YnI-DQombmJzcDs8YnI-DQpFYWNoIGVucXVpcnkgY2FuIHRha2UgYSBkaWZmZXJlbnQgYW1vdW50IG9mIHRpbWUgdG8gYWN0aW9uIHNhdGlzZmFjdG9yaWx5LCBkZXBlbmRpbmcgb24gdGhlIG5hdHVyZS48YnI-DQombmJzcDs8YnI-DQpTaG91bGQgeW91ciByZXF1ZXN0IG5vdCBuZWVkIGFueSBmb2xsb3ctdXAsIHdlIHdpbGwgYWN0aW9uIHdpdGhvdXQgcmVzcG9uc2Ugb3IgY29udGFjdCB5b3Ugd2hlbiB3ZSBoYXZlIGFuIHVwZGF0ZS48YnI-DQombmJzcDs8YnI-DQpQbGVhc2Ugbm90ZSDigJMgYW55IGl0ZW0gY29sbGVjdGVkIGZyb20gb3VyIGNsaWVudHMgd2lsbCBiZSBkZWxpdmVyZWQgYXMgYWRkcmVzc2VkLiZuYnNwOyBBbnkgcmVxdWVzdHMgZm9yIHJlZGlyZWN0aW9uIG9yIHJldHVybnMgd2lsbCBiZSBhdHRlbXB0ZWQsIGhvd2V2ZXIsIGNhbiBub3QgYmUgZ3VhcmFudGVlZC48YnI-DQombmJzcDs8YnI-DQpUaGFuayB5b3UmbmJzcDs8YnI-DQpDdXN0b21lciBTZXJ2aWNlIFRlYW08YnI-DQo8ZGl2IGNsYXNzPSJ4X3NpZ25hdHVyZSI-DQo8cCBkaXI9Imx0ciIgc3R5bGU9ImNvbG9yOiMyYjJlMmY7IGxpbmUtaGVpZ2h0OjIycHg7IG1hcmdpbjoxNXB4IDAiPkFyYW1leCBXb2xsb25nb25nPGJyPg0KS2luZCBSZWdhcmRzLDxicj4NCkFyYW1leCAoV29sbG9uZ29uZyk8L3A-DQo8L2Rpdj4NCjwvZGl2Pg0KPHAgZGlyPSJsdHIiPjwvcD4NCjwvdGQ-DQo8L3RyPg0KPC90Ym9keT4NCjwvdGFibGU-DQo8L3RkPg0KPC90cj4NCjwvdGJvZHk-DQo8L3RhYmxlPg0KPHAgZGlyPSJsdHIiPjwvcD4NCjx0YWJsZSB3aWR0aD0iMTAwJSIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBib3JkZXI9IjAiIHJvbGU9InByZXNlbnRhdGlvbiI-DQo8dGJvZHk-DQo8dHI-DQo8dGQgd2lkdGg9IjEwMCUiIHN0eWxlPSJwYWRkaW5nOjE1cHggMDsgYm9yZGVyLXRvcDoxcHggZG90dGVkICNjNWM1YzUiPg0KPHRhYmxlIHdpZHRoPSIxMDAlIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCIgcm9sZT0icHJlc2VudGF0aW9uIiBzdHlsZT0idGFibGUtbGF5b3V0OmZpeGVkIj4NCjx0Ym9keT4NCjx0cj4NCjx0ZCB3aWR0aD0iMTAwJSIgdmFsaWduPSJ0b3AiIHN0eWxlPSJwYWRkaW5nOjA7IG1hcmdpbjowIj4NCjxwIGRpcj0ibHRyIiBzdHlsZT0iZm9udC1mYW1pbHk6J0x1Y2lkYSBHcmFuZGUnLCdMdWNpZGEgU2FucyBVbmljb2RlJywnTHVjaWRhIFNhbnMnLFZlcmRhbmEsVGFob21hLHNhbnMtc2VyaWY7IGZvbnQtc2l6ZToxNXB4OyBsaW5lLWhlaWdodDoxOHB4OyBtYXJnaW4tYm90dG9tOjA7IG1hcmdpbi10b3A6MDsgcGFkZGluZzowOyBjb2xvcjojMWIxZDFlIj4NCjxzdHJvbmc-QXRkMDM8L3N0cm9uZz4gPC9wPg0KPHAgZGlyPSJsdHIiIHN0eWxlPSJmb250LWZhbWlseTonTHVjaWRhIEdyYW5kZScsJ0x1Y2lkYSBTYW5zIFVuaWNvZGUnLCdMdWNpZGEgU2FucycsVmVyZGFuYSxUYWhvbWEsc2Fucy1zZXJpZjsgZm9udC1zaXplOjEzcHg7IGxpbmUtaGVpZ2h0OjI1cHg7IG1hcmdpbi1ib3R0b206MTVweDsgbWFyZ2luLXRvcDowOyBwYWRkaW5nOjA7IGNvbG9yOiNiYmJiYmIiPg0KQXVnIDEwLCAyMDIyLCAwNToxOSBHTVQrMTIgPC9wPg0KPGRpdiBjbGFzcz0ieF96ZC1jb21tZW50IiBkaXI9ImF1dG8iIHN0eWxlPSJjb2xvcjojMmIyZTJmOyBsaW5lLWhlaWdodDoyMnB4OyBtYXJnaW46MTVweCAwIj4NCjxwIGRpcj0ibHRyIiBzdHlsZT0iY29sb3I6IzJiMmUyZjsgbGluZS1oZWlnaHQ6MjJweDsgbWFyZ2luOjE1cHggMCI-SGksIG15IHBhcmNlbCB3YXMgcG9zdGVkIDIgd2Vla3MgYWdvIGFuZCBpIGp1c3Qgd2FudGVkIGFuIHVwZGF0ZSBvbiBob3cgbXVjaCBsb25nZXIgaXQgd2lsbCB0YWtlPzwvcD4NCjwvZGl2Pg0KPC90ZD4NCjwvdHI-DQo8L3Rib2R5Pg0KPC90YWJsZT4NCjwvdGQ-DQo8L3RyPg0KPC90Ym9keT4NCjwvdGFibGU-DQo8L2Rpdj4NCjxwIGRpcj0ibHRyIj48L3A-DQo8L3RkPg0KPC90cj4NCjwvdGJvZHk-DQo8L3RhYmxlPg0KPC90ZD4NCjwvdHI-DQo8dHIgc3R5bGU9Im1hcmdpbjowOyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgYmFja2dyb3VuZC1jb2xvcjoiPg0KPHRkIGNsYXNzPSJ4X2NvbnRhaW5lciIgc3R5bGU9Im1hcmdpbjowIGF1dG8haW1wb3J0YW50OyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMDAlOyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgZGlzcGxheTpibG9jayFpbXBvcnRhbnQ7IGNsZWFyOmJvdGghaW1wb3J0YW50OyBtYXgtd2lkdGg6NTgwcHghaW1wb3J0YW50Ij4NCjx0YWJsZSBzdHlsZT0ibWFyZ2luOjA7IHBhZGRpbmc6MDsgZm9udC1zaXplOjEwMCU7IGZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmOyBsaW5lLWhlaWdodDoxLjY1OyBib3JkZXItY29sbGFwc2U6Y29sbGFwc2U7IHdpZHRoOjEwMCUhaW1wb3J0YW50Ij4NCjx0Ym9keT4NCjx0ciBzdHlsZT0ibWFyZ2luOjA7IHBhZGRpbmc6MDsgZm9udC1zaXplOjEwMCU7IGZvbnQtZmFtaWx5OkFyaWFsLEhlbHZldGljYSxzYW5zLXNlcmlmOyBsaW5lLWhlaWdodDoxLjY1Ij4NCjx0ZCBjbGFzcz0ieF9jb250ZW50IHhfZm9vdGVyIiBzdHlsZT0ibWFyZ2luOjA7IHBhZGRpbmc6MzBweCAyNXB4ICAyMHB4ICAyNXB4OyBmb250LXNpemU6MTAwJTsgZm9udC1mYW1pbHk6QXJpYWwsSGVsdmV0aWNhLHNhbnMtc2VyaWY7IGxpbmUtaGVpZ2h0OjEuNjU7IGJhY2tncm91bmQtY29sb3I6I0Y4RjhGOCI-DQo8cCBkaXI9Imx0ciIgc3R5bGU9Im1hcmdpbjowOyBwYWRkaW5nOjA7IGZvbnQtc2l6ZToxMnB4OyBmb250LWZhbWlseTpBcmlhbCxIZWx2ZXRpY2Esc2Fucy1zZXJpZjsgbGluZS1oZWlnaHQ6MS42NTsgZm9udC13ZWlnaHQ6bm9ybWFsOyBjb2xvcjojODg4OyB0ZXh0LWFsaWduOmNlbnRlciI-DQpUaGlzIGVtYWlsIHdhcyBzZW50IGJ5IEF1c3RyYWxpYW4gQ291cmllcnMgUHR5IEx0ZCwgTGV2ZWwgOSwgNDkxIEtlbnQgU3RyZWV0IFN5ZG5leSBOU1cgMjAwMCAvIEZhc3R3YXkgQ291cmllcnMgKE5aKSBMdGQsIFNoZWQgNSwgTGV2ZWwgMSwgTGV2ZXIgU3RyZWV0LCBBaHVyaXJpLCBOYXBpZXIgNDExMi4gSWYgeW91IGhhdmUgYW55IHF1ZXN0aW9ucyBvciBjb25jZXJucywgcGxlYXNlIHZpc2l0DQo8YSBocmVmPSJ3d3cuYXJhbWV4LmNvbS5hdSI-YXJhbWV4LmNvbS5hdTwvYT4gLyA8YSBocmVmPSJ3d3cuYXJhbWV4LmNvLm56Ij5hcmFtZXguY28ubno8L2E-IGZvciBmdXJ0aGVyIGluZm9ybWF0aW9uLg0KPC9wPg0KPC90ZD4NCjwvdHI-DQo8L3Rib2R5Pg0KPC90YWJsZT4NCjwvdGQ-DQo8L3RyPg0KPC90Ym9keT4NCjwvdGFibGU-DQo8c3BhbiBhcmlhLWhpZGRlbj0idHJ1ZSIgc3R5bGU9ImNvbG9yOiNGRkZGRkYiPls2NTk5UTMtUUdWRFZdPC9zcGFuPiA8L2Rpdj4NCjwvYm9keT4NCjwvaHRtbD4NCg==', null);
        // dd($message);
        $decodedMessage = $this->decodeMessage($message);
        $decodedMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $decodedMessage );
        $decodedMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $decodedMessage );
        $decodedMessage = str_replace("progress-bar", "", $decodedMessage);
        $message        = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');

        //temporary fix for plain text, for some reason on some messages the gmail only returns text/plain and not text/html
        //if message has no html, automatically add <p> on every white/breaklines
        if ( $message == strip_tags($message)  )
        {
            $message = preg_replace("/[\r\n]/","<p></br>",$message);
        }
        // dd($message);
        echo $message;

        dd(1234);

     //    $thread = GmailApi::threadDataDump('181d9e6ccf1038ac');
     //    dd($thread);
    	// $thread = GmailApi::threadDataDump('181c8f2188fd08f5');
    	// dd($thread);
     //    dd(9);

        $thread = GmailApi::threadDataDump('181d9e6ccf1038ac');

        dump($thread);

        $data    = Array();
        $i       = 0;
        $k       = 0;
        $j = 0;

        $client  = GmailApi::getClient( 1 );
        $service = new Google_Service_Gmail($client);
        $user    = 'me';

        $messages = $thread->messages;
        $_message = $textHtml = $textPlain = '';
        foreach ($messages as $key => $message) {

            if( $message->id == '181d9e6ccf1038ac' )
            {
                dump($message->id);
                $attachments = $messageAttachments = [];
                $_message    = '';

                if (isset($message->payload->parts)) {

                    foreach ( $message->payload->parts as $part )
                    {

                        if ( $part->getBody()->getAttachmentId() != null ) // has attachment
                        {
                            $messageAttachments = GmailApi::getAllAttachments( $message->id, $message->payload->parts, $service, true );
                            dump(1);
                        }
                        else
                        {
                            dump(2);
                            if ( isset($part->parts) )
                            {
                                dump(3);
                                $getParts = GmailApi::getParts($message->payload);
                                $_data = array();
                                array_walk_recursive($getParts, function ($value, $key) use (&$_data){
                                    if ($value)
                                        $_data[] = $value;
                                }, $_data);
                                $_message = $_data[ count($_data) - 1 ];
                                dump($_message);
                                $messageAttachments = GmailApi::getAllAttachments( $message->id, $part->parts, $service, true ); // apply this on 04/11/22
                                dump($messageAttachments);

                                dump('@@@@');
                                dd( $this->messageEncode($_message, $messageAttachments) );
                                dump('@@@@');
                            }
                            else
                            {
                                dump(4);
                                //get message that has no attachment - payload parts changes if the message has attachment.

                                //store each text/plain and text/html contents in a variable
                                // make a condition if text/html is empy then use text/plain if not empty..

                                if ( empty( $_message ) )
                                {
                                    dump('4.0');
                                    if ( $part->getMimeType() == 'text/html' && $part->getBody()->data != null )
                                    {
                                        dump('4.1');
                                        // $textHtml = $part->getBody()->data;
                                        $_message = $part->getBody()->data;
                                        // $mimeType = $part->getMimeType();
                                    }
                                    
                                    if ( $part->getMimeType() == 'text/plain' && $part->getBody()->data != null && empty( $textPlain ) )
                                    {
                                        dump('4.2');
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
                    dump(5);
                    // $_message = $message->payload->body->data;
                    $_message = $message->payload->body->data;
                }
                    

                //if decoded message html is empty, get text plain.
                $_messageData = trim( base64_decode($_message) );
                if ( empty($_messageData) )
                {
                    dump(6);
                    $_textPlainData = trim( base64_decode($textPlain) );
                    if ( !empty($_textPlainData) )
                    {
                        $_message = $textPlain;
                    }
                }

                $data[$i]['messages'][$j]['message_id']    = $message->id;
                $_date                                     = date('Y-m-d H:i:s', strtotime(GmailApi::getDate($message->payload->headers)));
                $data[$i]['messages'][$j]['internal_date'] = $_date;

                $data[$i]['messages'][$j]['from']        = str_replace( ['"', "'"], ['', ''], GmailApi::getFrom($message->payload->headers) );
                $data[$i]['messages'][$j]['to']          = str_replace( ['"', "'"], ['', ''], GmailApi::getFrom($message->payload->headers, 'To') );
                $data[$i]['messages'][$j]['message']     = $_message;
                $data[$i]['messages'][$j]['attachments'] = $messageAttachments;

                $j++;

            }

        }

        dd(99);

        /*$threadIds = ['18176c1eba493822','181766e88caa71d9','18175fd7fa1d13be','18175e9a82c3fe53','18175a3eca3a3172','18175a1e44968450','181759ff33a90222','1817570369ad5926','18175701ebf80f68','181756bbf7808731','181756b97c5183b4','181755c521a41536','1817512955a73407','18174d7419641ca0','18174a9424b4893d','1817497466a60de8','18174777b7110937','1817449858cf85b2','1817418a9d700d83','181741963dff50f0','1817407ef01a1e05','18173d7672d401b1','18173d172c3e8c1e','18173c4a7aaf436c','18173b89c07c5095','18173785a35c20ca','181729469e227489','1817b86850e5666c','1817b82b0f71e18a','1817b23dde560352','1817af9e7530ac55','1817adee2210ba01','1817adf61e723d78','1817ac364e9a209c','18179bf3a076f233','1817962f4ff6a40a','18179152403a7b18','18178ffa77d7ce6f','18178e725024e168','1818151e3fa95ffc','1817ff3e472a65a9','1817f9b8f8ff4b96','1817f846b96f3a06','1817f6e85b46428d','1817f5c89e6a7fe6','1817f4d89962fd5b','1817f4592a58f704','1817f48c0ad1ae25','1817f413ed97944a','1817f3c06ac2f3e0','1817f3fadedd676d','1817f3ebb2aeabc1','1817f2e5dea27dba','1817f099e5eef3d8','1817efcddf448856','1817ef0f81578d08','1817ee9f04ba0222','1817edc8e513f476','1817edd1b5d5b328','1817ecee1fee6562','1817ebc60c55eaae','1817ebcccd35e16a','1817eb3e41f39aa8','1817eae157acdd88','1817ea98a159164e','1817ea58b6fe121a','1817ea58bc72dc03','1817e782fe2c2255','1817e74a6c515133','1817e74c3ebd2fe3','1817e69029735d5c','1817e69e2a1447fb','1817e54131d1a49e','1817e30c346121db','1817e2932eaead1e','1817e053bf0e5b66','1817dfdd5e3f6b19','1817dfab12fc06ca','1817dad939a09358','1817cadff91b476b','181866a5101bc06a','181866ab9829c7d8','181859f173fd4fe6','181858847c3e48b3','181857256ce7e4e2','18184fe2e372409f','18185019358a3f1e','18184f67600b0d51','18184eb345397fd4','18184eb460066418','18184e70a41fa464','18184c3e95429b4e','181849f0e5150916','181849852d3af7b9','181848a38ecb48cc','181848072b23f3c3','18184769bf68cf22','181846d09bb06e2d','181846a746f89692','18184521152eda46','1818435df32731d2','1818433886e4c72b','18184276cab23810','18183ee46f3971f3','181839d5a7720ded','181838f7f282d54a','18183783981ab61c','181836b81cce8114','1818356285a3f051','1818357fdca60d37','1818351920408d6c','181833145de62333','18183291a73edffd','1818324179fae6dd','181832569139a44d','18182fccc30df92d','18182e3d7a397910'];

        foreach($threadIds as $threadId)
        {
            GmailApi::updateTicketsMessages($threadId);
        }

        dd(9);*/

        GmailApi::updateTicketsMessages('18184276cab23810');
        dd(9);
        dd( GmailApi::threadDataDump('1802562395bd0525') );
        // $assignedTicket = AssignedTicket::where('ticket_id', 33165)->where('user_id', '!=', 12)->count();
        // dd($assignedTicket);

        dump(strtolower( \Carbon\Carbon::now()->format('D') ));
        $usersSchedule = UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray();
        $userIds       = array_column($usersSchedule,'user_id');
        $users         = User::whereIn('id', $userIds)->withCount('tickets')->orderBy('tickets_count', 'asc')->get()->toArray();

        dump($usersSchedule);
        dump($userIds);
        // dd($users);

        $tickets = [2242,2245];
        $random = true;

        if ( !isset($users[0]) ) // if not array of key values then create one for the current(), and next() function to work
        {
            $data[0]     = $users;
            $users       = $data;   
        }

        if ( $random )
        {

            $allUsers = $users;
            $user     = current($users);

            foreach ( $tickets as $ticket )
            {
                
                dump($user['id']);
                // $this->assignTicket($ticket, $user);
                $user = next($users);

                if(!$user)
                {
                    $users = $allUsers;
                    $user  = current($users);
                }
                
            }

        }

        // dd($users->toArray());

        dd();

        $email = EmailSupportAddress::all('email')->toArray();
        dump($email);

        $tmpEmail = [0 => ['email' => 'email1'], 1 => ['email' => 'email2']];
        dd(array_column($email, 'email'));

        $message = Message::where('ticket_id', 32249)->where('from', 'support@frankiesautoelectrics.com.au')->count();
        dd($message);
        /*$ticketIds = explode(',', '32775,32774,32771,32770,32768');
        

        foreach($ticketIds as $ticketId)
        {
            $d = AssignedTicket::create([
                'user_id' => 21,
                'ticket_id' => $ticketId,
            ]);

            dump($d);
        }*/
        dd('end');

        dd( GmailApi::tmpBuildTicketData('1810d16fd0c79686') );

        dd( GmailApi::threadDataDump('1810d16fd0c79686') );
        dd( UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray() );

        dd( $this->messageEncode('PGh0bWwgeG1sbnM6dj0idXJuOnNjaGVtYXMtbWljcm9zb2Z0LWNvbTp2bWwiIHhtbG5zOm89InVybjpzY2hlbWFzLW1pY3Jvc29mdC1jb206b2ZmaWNlOm9mZmljZSIgeG1sbnM6dz0idXJuOnNjaGVtYXMtbWljcm9zb2Z0LWNvbTpvZmZpY2U6d29yZCIgeG1sbnM6bT0iaHR0cDovL3NjaGVtYXMubWljcm9zb2Z0LmNvbS9vZmZpY2UvMjAwNC8xMi9vbW1sIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvVFIvUkVDLWh0bWw0MCI-PGhlYWQ-PG1ldGEgaHR0cC1lcXVpdj1Db250ZW50LVR5cGUgY29udGVudD0idGV4dC9odG1sOyBjaGFyc2V0PXV0Zi04Ij48bWV0YSBuYW1lPVByb2dJZCBjb250ZW50PVdvcmQuRG9jdW1lbnQ-PG1ldGEgbmFtZT1HZW5lcmF0b3IgY29udGVudD0iTWljcm9zb2Z0IFdvcmQgMTUiPjxtZXRhIG5hbWU9T3JpZ2luYXRvciBjb250ZW50PSJNaWNyb3NvZnQgV29yZCAxNSI-PGxpbmsgcmVsPUZpbGUtTGlzdCBocmVmPSJjaWQ6ZmlsZWxpc3QueG1sQDAxRDg2NTE2LkFENUNEREUwIj48bGluayByZWw9RWRpdC1UaW1lLURhdGEgaHJlZj0iY2lkOmVkaXRkYXRhLm1zbyI-PCEtLVtpZiAhbXNvXT48c3R5bGU-dlw6KiB7YmVoYXZpb3I6dXJsKCNkZWZhdWx0I1ZNTCk7fQ0Kb1w6KiB7YmVoYXZpb3I6dXJsKCNkZWZhdWx0I1ZNTCk7fQ0Kd1w6KiB7YmVoYXZpb3I6dXJsKCNkZWZhdWx0I1ZNTCk7fQ0KLnNoYXBlIHtiZWhhdmlvcjp1cmwoI2RlZmF1bHQjVk1MKTt9DQo8L3N0eWxlPjwhW2VuZGlmXS0tPjwhLS1baWYgZ3RlIG1zbyA5XT48eG1sPg0KPG86T2ZmaWNlRG9jdW1lbnRTZXR0aW5ncz4NCjxvOkFsbG93UE5HLz4NCjwvbzpPZmZpY2VEb2N1bWVudFNldHRpbmdzPg0KPC94bWw-PCFbZW5kaWZdLS0-PCEtLVtpZiBndGUgbXNvIDldPjx4bWw-DQo8dzpXb3JkRG9jdW1lbnQ-DQo8dzpTcGVsbGluZ1N0YXRlPkNsZWFuPC93OlNwZWxsaW5nU3RhdGU-DQo8dzpEb2N1bWVudEtpbmQ-RG9jdW1lbnRFbWFpbDwvdzpEb2N1bWVudEtpbmQ-DQo8dzpUcmFja01vdmVzLz4NCjx3OlRyYWNrRm9ybWF0dGluZy8-DQo8dzpFbnZlbG9wZVZpcy8-DQo8dzpWYWxpZGF0ZUFnYWluc3RTY2hlbWFzLz4NCjx3OlNhdmVJZlhNTEludmFsaWQ-ZmFsc2U8L3c6U2F2ZUlmWE1MSW52YWxpZD4NCjx3Oklnbm9yZU1peGVkQ29udGVudD5mYWxzZTwvdzpJZ25vcmVNaXhlZENvbnRlbnQ-DQo8dzpBbHdheXNTaG93UGxhY2Vob2xkZXJUZXh0PmZhbHNlPC93OkFsd2F5c1Nob3dQbGFjZWhvbGRlclRleHQ-DQo8dzpEb05vdFByb21vdGVRRi8-DQo8dzpMaWRUaGVtZU90aGVyPkVOLUFVPC93OkxpZFRoZW1lT3RoZXI-DQo8dzpMaWRUaGVtZUFzaWFuPlgtTk9ORTwvdzpMaWRUaGVtZUFzaWFuPg0KPHc6TGlkVGhlbWVDb21wbGV4U2NyaXB0PlgtTk9ORTwvdzpMaWRUaGVtZUNvbXBsZXhTY3JpcHQ-DQo8dzpDb21wYXRpYmlsaXR5Pg0KPHc6RG9Ob3RFeHBhbmRTaGlmdFJldHVybi8-DQo8dzpCcmVha1dyYXBwZWRUYWJsZXMvPg0KPHc6U25hcFRvR3JpZEluQ2VsbC8-DQo8dzpXcmFwVGV4dFdpdGhQdW5jdC8-DQo8dzpVc2VBc2lhbkJyZWFrUnVsZXMvPg0KPHc6RG9udEdyb3dBdXRvZml0Lz4NCjx3OlNwbGl0UGdCcmVha0FuZFBhcmFNYXJrLz4NCjx3OkVuYWJsZU9wZW5UeXBlS2VybmluZy8-DQo8dzpEb250RmxpcE1pcnJvckluZGVudHMvPg0KPHc6T3ZlcnJpZGVUYWJsZVN0eWxlSHBzLz4NCjwvdzpDb21wYXRpYmlsaXR5Pg0KPG06bWF0aFByPg0KPG06bWF0aEZvbnQgbTp2YWw9IkNhbWJyaWEgTWF0aCIvPg0KPG06YnJrQmluIG06dmFsPSJiZWZvcmUiLz4NCjxtOmJya0JpblN1YiBtOnZhbD0iJiM0NTstIi8-DQo8bTpzbWFsbEZyYWMgbTp2YWw9Im9mZiIvPg0KPG06ZGlzcERlZi8-DQo8bTpsTWFyZ2luIG06dmFsPSIwIi8-DQo8bTpyTWFyZ2luIG06dmFsPSIwIi8-DQo8bTpkZWZKYyBtOnZhbD0iY2VudGVyR3JvdXAiLz4NCjxtOndyYXBJbmRlbnQgbTp2YWw9IjE0NDAiLz4NCjxtOmludExpbSBtOnZhbD0ic3ViU3VwIi8-DQo8bTpuYXJ5TGltIG06dmFsPSJ1bmRPdnIiLz4NCjwvbTptYXRoUHI-PC93OldvcmREb2N1bWVudD4NCjwveG1sPjwhW2VuZGlmXS0tPjwhLS1baWYgZ3RlIG1zbyA5XT48eG1sPg0KPHc6TGF0ZW50U3R5bGVzIERlZkxvY2tlZFN0YXRlPSJmYWxzZSIgRGVmVW5oaWRlV2hlblVzZWQ9ImZhbHNlIiBEZWZTZW1pSGlkZGVuPSJmYWxzZSIgRGVmUUZvcm1hdD0iZmFsc2UiIERlZlByaW9yaXR5PSI5OSIgTGF0ZW50U3R5bGVDb3VudD0iMzc2Ij4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMCIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iTm9ybWFsIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjkiIFFGb3JtYXQ9InRydWUiIE5hbWU9ImhlYWRpbmcgMSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI5IiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJoZWFkaW5nIDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iOSIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iaGVhZGluZyAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjkiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJoZWFkaW5nIDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iOSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIFFGb3JtYXQ9InRydWUiIE5hbWU9ImhlYWRpbmcgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI5IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iaGVhZGluZyA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjkiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJoZWFkaW5nIDciLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iOSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIFFGb3JtYXQ9InRydWUiIE5hbWU9ImhlYWRpbmcgOCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI5IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iaGVhZGluZyA5Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9ImluZGV4IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iaW5kZXggMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJpbmRleCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9ImluZGV4IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iaW5kZXggNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJpbmRleCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9ImluZGV4IDciLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iaW5kZXggOCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJpbmRleCA5Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjM5IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0idG9jIDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMzkiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJ0b2MgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSIzOSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9InRvYyAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjM5IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0idG9jIDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMzkiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJ0b2MgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSIzOSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9InRvYyA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjM5IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0idG9jIDciLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMzkiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJ0b2MgOCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSIzOSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9InRvYyA5Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ik5vcm1hbCBJbmRlbnQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iZm9vdG5vdGUgdGV4dCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJhbm5vdGF0aW9uIHRleHQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iaGVhZGVyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9ImZvb3RlciIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJpbmRleCBoZWFkaW5nIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjM1IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iY2FwdGlvbiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJ0YWJsZSBvZiBmaWd1cmVzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9ImVudmVsb3BlIGFkZHJlc3MiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iZW52ZWxvcGUgcmV0dXJuIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9ImZvb3Rub3RlIHJlZmVyZW5jZSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJhbm5vdGF0aW9uIHJlZmVyZW5jZSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJsaW5lIG51bWJlciIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJwYWdlIG51bWJlciIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJlbmRub3RlIHJlZmVyZW5jZSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJlbmRub3RlIHRleHQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0idGFibGUgb2YgYXV0aG9yaXRpZXMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0ibWFjcm8iLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0idG9hIGhlYWRpbmciLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iTGlzdCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IEJ1bGxldCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IE51bWJlciIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iTGlzdCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ikxpc3QgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iTGlzdCBCdWxsZXQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IEJ1bGxldCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ikxpc3QgQnVsbGV0IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iTGlzdCBCdWxsZXQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IE51bWJlciAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ikxpc3QgTnVtYmVyIDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iTGlzdCBOdW1iZXIgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IE51bWJlciA1Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjEwIiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJUaXRsZSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJDbG9zaW5nIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlNpZ25hdHVyZSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSIxIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iRGVmYXVsdCBQYXJhZ3JhcGggRm9udCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJCb2R5IFRleHQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iQm9keSBUZXh0IEluZGVudCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IENvbnRpbnVlIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ikxpc3QgQ29udGludWUgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJMaXN0IENvbnRpbnVlIDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iTGlzdCBDb250aW51ZSA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ikxpc3QgQ29udGludWUgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJNZXNzYWdlIEhlYWRlciIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSIxMSIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iU3VidGl0bGUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iU2FsdXRhdGlvbiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJEYXRlIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkJvZHkgVGV4dCBGaXJzdCBJbmRlbnQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iQm9keSBUZXh0IEZpcnN0IEluZGVudCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ik5vdGUgSGVhZGluZyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJCb2R5IFRleHQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJCb2R5IFRleHQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJCb2R5IFRleHQgSW5kZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iQm9keSBUZXh0IEluZGVudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkJsb2NrIFRleHQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iSHlwZXJsaW5rIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkZvbGxvd2VkSHlwZXJsaW5rIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjIyIiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJTdHJvbmciLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMjAiIFFGb3JtYXQ9InRydWUiIE5hbWU9IkVtcGhhc2lzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkRvY3VtZW50IE1hcCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJQbGFpbiBUZXh0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkUtbWFpbCBTaWduYXR1cmUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iSFRNTCBUb3Agb2YgRm9ybSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJIVE1MIEJvdHRvbSBvZiBGb3JtIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ik5vcm1hbCAoV2ViKSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJIVE1MIEFjcm9ueW0iLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iSFRNTCBBZGRyZXNzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkhUTUwgQ2l0ZSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJIVE1MIENvZGUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iSFRNTCBEZWZpbml0aW9uIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkhUTUwgS2V5Ym9hcmQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iSFRNTCBQcmVmb3JtYXR0ZWQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iSFRNTCBTYW1wbGUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iSFRNTCBUeXBld3JpdGVyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IkhUTUwgVmFyaWFibGUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iTm9ybWFsIFRhYmxlIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9ImFubm90YXRpb24gc3ViamVjdCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJObyBMaXN0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ik91dGxpbmUgTGlzdCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ik91dGxpbmUgTGlzdCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9Ik91dGxpbmUgTGlzdCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIFNpbXBsZSAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIFNpbXBsZSAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIFNpbXBsZSAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIENsYXNzaWMgMSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBDbGFzc2ljIDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgQ2xhc3NpYyAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIENsYXNzaWMgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBDb2xvcmZ1bCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIENvbG9yZnVsIDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgQ29sb3JmdWwgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBDb2x1bW5zIDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgQ29sdW1ucyAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIENvbHVtbnMgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBDb2x1bW5zIDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgQ29sdW1ucyA1Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIEdyaWQgMSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBHcmlkIDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgR3JpZCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIEdyaWQgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBHcmlkIDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgR3JpZCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIEdyaWQgNyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBHcmlkIDgiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgTGlzdCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIExpc3QgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBMaXN0IDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgTGlzdCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIExpc3QgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBMaXN0IDYiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgTGlzdCA3Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIExpc3QgOCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSAzRCBlZmZlY3RzIDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgM0QgZWZmZWN0cyAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIDNEIGVmZmVjdHMgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBDb250ZW1wb3JhcnkiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgRWxlZ2FudCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJUYWJsZSBQcm9mZXNzaW9uYWwiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgU3VidGxlIDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgU3VidGxlIDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgV2ViIDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgV2ViIDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iVGFibGUgV2ViIDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iQmFsbG9vbiBUZXh0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjM5IiBOYW1lPSJUYWJsZSBHcmlkIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlRhYmxlIFRoZW1lIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgTmFtZT0iUGxhY2Vob2xkZXIgVGV4dCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSIxIiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJObyBTcGFjaW5nIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYwIiBOYW1lPSJMaWdodCBTaGFkaW5nIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYxIiBOYW1lPSJMaWdodCBMaXN0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYyIiBOYW1lPSJMaWdodCBHcmlkIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYzIiBOYW1lPSJNZWRpdW0gU2hhZGluZyAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY0IiBOYW1lPSJNZWRpdW0gU2hhZGluZyAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY1IiBOYW1lPSJNZWRpdW0gTGlzdCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY2IiBOYW1lPSJNZWRpdW0gTGlzdCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY3IiBOYW1lPSJNZWRpdW0gR3JpZCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY4IiBOYW1lPSJNZWRpdW0gR3JpZCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY5IiBOYW1lPSJNZWRpdW0gR3JpZCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjcwIiBOYW1lPSJEYXJrIExpc3QiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzEiIE5hbWU9IkNvbG9yZnVsIFNoYWRpbmciLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzIiIE5hbWU9IkNvbG9yZnVsIExpc3QiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzMiIE5hbWU9IkNvbG9yZnVsIEdyaWQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjAiIE5hbWU9IkxpZ2h0IFNoYWRpbmcgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjEiIE5hbWU9IkxpZ2h0IExpc3QgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjIiIE5hbWU9IkxpZ2h0IEdyaWQgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjMiIE5hbWU9Ik1lZGl1bSBTaGFkaW5nIDEgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjQiIE5hbWU9Ik1lZGl1bSBTaGFkaW5nIDIgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjUiIE5hbWU9Ik1lZGl1bSBMaXN0IDEgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBTZW1pSGlkZGVuPSJ0cnVlIiBOYW1lPSJSZXZpc2lvbiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSIzNCIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iTGlzdCBQYXJhZ3JhcGgiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMjkiIFFGb3JtYXQ9InRydWUiIE5hbWU9IlF1b3RlIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjMwIiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJJbnRlbnNlIFF1b3RlIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY2IiBOYW1lPSJNZWRpdW0gTGlzdCAyIEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY3IiBOYW1lPSJNZWRpdW0gR3JpZCAxIEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY4IiBOYW1lPSJNZWRpdW0gR3JpZCAyIEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY5IiBOYW1lPSJNZWRpdW0gR3JpZCAzIEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjcwIiBOYW1lPSJEYXJrIExpc3QgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzEiIE5hbWU9IkNvbG9yZnVsIFNoYWRpbmcgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzIiIE5hbWU9IkNvbG9yZnVsIExpc3QgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzMiIE5hbWU9IkNvbG9yZnVsIEdyaWQgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjAiIE5hbWU9IkxpZ2h0IFNoYWRpbmcgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjEiIE5hbWU9IkxpZ2h0IExpc3QgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjIiIE5hbWU9IkxpZ2h0IEdyaWQgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjMiIE5hbWU9Ik1lZGl1bSBTaGFkaW5nIDEgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjQiIE5hbWU9Ik1lZGl1bSBTaGFkaW5nIDIgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjUiIE5hbWU9Ik1lZGl1bSBMaXN0IDEgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjYiIE5hbWU9Ik1lZGl1bSBMaXN0IDIgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjciIE5hbWU9Ik1lZGl1bSBHcmlkIDEgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjgiIE5hbWU9Ik1lZGl1bSBHcmlkIDIgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjkiIE5hbWU9Ik1lZGl1bSBHcmlkIDMgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzAiIE5hbWU9IkRhcmsgTGlzdCBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MSIgTmFtZT0iQ29sb3JmdWwgU2hhZGluZyBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MiIgTmFtZT0iQ29sb3JmdWwgTGlzdCBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MyIgTmFtZT0iQ29sb3JmdWwgR3JpZCBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MCIgTmFtZT0iTGlnaHQgU2hhZGluZyBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MSIgTmFtZT0iTGlnaHQgTGlzdCBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MiIgTmFtZT0iTGlnaHQgR3JpZCBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MyIgTmFtZT0iTWVkaXVtIFNoYWRpbmcgMSBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NCIgTmFtZT0iTWVkaXVtIFNoYWRpbmcgMiBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NSIgTmFtZT0iTWVkaXVtIExpc3QgMSBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NiIgTmFtZT0iTWVkaXVtIExpc3QgMiBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NyIgTmFtZT0iTWVkaXVtIEdyaWQgMSBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2OCIgTmFtZT0iTWVkaXVtIEdyaWQgMiBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2OSIgTmFtZT0iTWVkaXVtIEdyaWQgMyBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MCIgTmFtZT0iRGFyayBMaXN0IEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjcxIiBOYW1lPSJDb2xvcmZ1bCBTaGFkaW5nIEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjcyIiBOYW1lPSJDb2xvcmZ1bCBMaXN0IEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjczIiBOYW1lPSJDb2xvcmZ1bCBHcmlkIEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYwIiBOYW1lPSJMaWdodCBTaGFkaW5nIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYxIiBOYW1lPSJMaWdodCBMaXN0IEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYyIiBOYW1lPSJMaWdodCBHcmlkIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjYzIiBOYW1lPSJNZWRpdW0gU2hhZGluZyAxIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY0IiBOYW1lPSJNZWRpdW0gU2hhZGluZyAyIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY1IiBOYW1lPSJNZWRpdW0gTGlzdCAxIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY2IiBOYW1lPSJNZWRpdW0gTGlzdCAyIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY3IiBOYW1lPSJNZWRpdW0gR3JpZCAxIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY4IiBOYW1lPSJNZWRpdW0gR3JpZCAyIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjY5IiBOYW1lPSJNZWRpdW0gR3JpZCAzIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjcwIiBOYW1lPSJEYXJrIExpc3QgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzEiIE5hbWU9IkNvbG9yZnVsIFNoYWRpbmcgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzIiIE5hbWU9IkNvbG9yZnVsIExpc3QgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzMiIE5hbWU9IkNvbG9yZnVsIEdyaWQgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjAiIE5hbWU9IkxpZ2h0IFNoYWRpbmcgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjEiIE5hbWU9IkxpZ2h0IExpc3QgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjIiIE5hbWU9IkxpZ2h0IEdyaWQgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjMiIE5hbWU9Ik1lZGl1bSBTaGFkaW5nIDEgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjQiIE5hbWU9Ik1lZGl1bSBTaGFkaW5nIDIgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjUiIE5hbWU9Ik1lZGl1bSBMaXN0IDEgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjYiIE5hbWU9Ik1lZGl1bSBMaXN0IDIgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjciIE5hbWU9Ik1lZGl1bSBHcmlkIDEgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjgiIE5hbWU9Ik1lZGl1bSBHcmlkIDIgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNjkiIE5hbWU9Ik1lZGl1bSBHcmlkIDMgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNzAiIE5hbWU9IkRhcmsgTGlzdCBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MSIgTmFtZT0iQ29sb3JmdWwgU2hhZGluZyBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MiIgTmFtZT0iQ29sb3JmdWwgTGlzdCBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MyIgTmFtZT0iQ29sb3JmdWwgR3JpZCBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MCIgTmFtZT0iTGlnaHQgU2hhZGluZyBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MSIgTmFtZT0iTGlnaHQgTGlzdCBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MiIgTmFtZT0iTGlnaHQgR3JpZCBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2MyIgTmFtZT0iTWVkaXVtIFNoYWRpbmcgMSBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NCIgTmFtZT0iTWVkaXVtIFNoYWRpbmcgMiBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NSIgTmFtZT0iTWVkaXVtIExpc3QgMSBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NiIgTmFtZT0iTWVkaXVtIExpc3QgMiBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2NyIgTmFtZT0iTWVkaXVtIEdyaWQgMSBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2OCIgTmFtZT0iTWVkaXVtIEdyaWQgMiBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI2OSIgTmFtZT0iTWVkaXVtIEdyaWQgMyBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI3MCIgTmFtZT0iRGFyayBMaXN0IEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjcxIiBOYW1lPSJDb2xvcmZ1bCBTaGFkaW5nIEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjcyIiBOYW1lPSJDb2xvcmZ1bCBMaXN0IEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjczIiBOYW1lPSJDb2xvcmZ1bCBHcmlkIEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjE5IiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJTdWJ0bGUgRW1waGFzaXMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMjEiIFFGb3JtYXQ9InRydWUiIE5hbWU9IkludGVuc2UgRW1waGFzaXMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMzEiIFFGb3JtYXQ9InRydWUiIE5hbWU9IlN1YnRsZSBSZWZlcmVuY2UiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iMzIiIFFGb3JtYXQ9InRydWUiIE5hbWU9IkludGVuc2UgUmVmZXJlbmNlIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjMzIiBRRm9ybWF0PSJ0cnVlIiBOYW1lPSJCb29rIFRpdGxlIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjM3IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgTmFtZT0iQmlibGlvZ3JhcGh5Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjM5IiBTZW1pSGlkZGVuPSJ0cnVlIiBVbmhpZGVXaGVuVXNlZD0idHJ1ZSIgUUZvcm1hdD0idHJ1ZSIgTmFtZT0iVE9DIEhlYWRpbmciLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDEiIE5hbWU9IlBsYWluIFRhYmxlIDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDIiIE5hbWU9IlBsYWluIFRhYmxlIDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDMiIE5hbWU9IlBsYWluIFRhYmxlIDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDQiIE5hbWU9IlBsYWluIFRhYmxlIDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDUiIE5hbWU9IlBsYWluIFRhYmxlIDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDAiIE5hbWU9IkdyaWQgVGFibGUgTGlnaHQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDYiIE5hbWU9IkdyaWQgVGFibGUgMSBMaWdodCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NyIgTmFtZT0iR3JpZCBUYWJsZSAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ4IiBOYW1lPSJHcmlkIFRhYmxlIDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDkiIE5hbWU9IkdyaWQgVGFibGUgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MCIgTmFtZT0iR3JpZCBUYWJsZSA1IERhcmsiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTEiIE5hbWU9IkdyaWQgVGFibGUgNiBDb2xvcmZ1bCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MiIgTmFtZT0iR3JpZCBUYWJsZSA3IENvbG9yZnVsIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ2IiBOYW1lPSJHcmlkIFRhYmxlIDEgTGlnaHQgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDciIE5hbWU9IkdyaWQgVGFibGUgMiBBY2NlbnQgMSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OCIgTmFtZT0iR3JpZCBUYWJsZSAzIEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ5IiBOYW1lPSJHcmlkIFRhYmxlIDQgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTAiIE5hbWU9IkdyaWQgVGFibGUgNSBEYXJrIEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUxIiBOYW1lPSJHcmlkIFRhYmxlIDYgQ29sb3JmdWwgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTIiIE5hbWU9IkdyaWQgVGFibGUgNyBDb2xvcmZ1bCBBY2NlbnQgMSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NiIgTmFtZT0iR3JpZCBUYWJsZSAxIExpZ2h0IEFjY2VudCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ3IiBOYW1lPSJHcmlkIFRhYmxlIDIgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDgiIE5hbWU9IkdyaWQgVGFibGUgMyBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OSIgTmFtZT0iR3JpZCBUYWJsZSA0IEFjY2VudCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUwIiBOYW1lPSJHcmlkIFRhYmxlIDUgRGFyayBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MSIgTmFtZT0iR3JpZCBUYWJsZSA2IENvbG9yZnVsIEFjY2VudCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUyIiBOYW1lPSJHcmlkIFRhYmxlIDcgQ29sb3JmdWwgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDYiIE5hbWU9IkdyaWQgVGFibGUgMSBMaWdodCBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NyIgTmFtZT0iR3JpZCBUYWJsZSAyIEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ4IiBOYW1lPSJHcmlkIFRhYmxlIDMgQWNjZW50IDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDkiIE5hbWU9IkdyaWQgVGFibGUgNCBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MCIgTmFtZT0iR3JpZCBUYWJsZSA1IERhcmsgQWNjZW50IDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTEiIE5hbWU9IkdyaWQgVGFibGUgNiBDb2xvcmZ1bCBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MiIgTmFtZT0iR3JpZCBUYWJsZSA3IENvbG9yZnVsIEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ2IiBOYW1lPSJHcmlkIFRhYmxlIDEgTGlnaHQgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDciIE5hbWU9IkdyaWQgVGFibGUgMiBBY2NlbnQgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OCIgTmFtZT0iR3JpZCBUYWJsZSAzIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ5IiBOYW1lPSJHcmlkIFRhYmxlIDQgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTAiIE5hbWU9IkdyaWQgVGFibGUgNSBEYXJrIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUxIiBOYW1lPSJHcmlkIFRhYmxlIDYgQ29sb3JmdWwgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTIiIE5hbWU9IkdyaWQgVGFibGUgNyBDb2xvcmZ1bCBBY2NlbnQgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NiIgTmFtZT0iR3JpZCBUYWJsZSAxIExpZ2h0IEFjY2VudCA1Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ3IiBOYW1lPSJHcmlkIFRhYmxlIDIgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDgiIE5hbWU9IkdyaWQgVGFibGUgMyBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OSIgTmFtZT0iR3JpZCBUYWJsZSA0IEFjY2VudCA1Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUwIiBOYW1lPSJHcmlkIFRhYmxlIDUgRGFyayBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MSIgTmFtZT0iR3JpZCBUYWJsZSA2IENvbG9yZnVsIEFjY2VudCA1Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUyIiBOYW1lPSJHcmlkIFRhYmxlIDcgQ29sb3JmdWwgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDYiIE5hbWU9IkdyaWQgVGFibGUgMSBMaWdodCBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NyIgTmFtZT0iR3JpZCBUYWJsZSAyIEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ4IiBOYW1lPSJHcmlkIFRhYmxlIDMgQWNjZW50IDYiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDkiIE5hbWU9IkdyaWQgVGFibGUgNCBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MCIgTmFtZT0iR3JpZCBUYWJsZSA1IERhcmsgQWNjZW50IDYiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTEiIE5hbWU9IkdyaWQgVGFibGUgNiBDb2xvcmZ1bCBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MiIgTmFtZT0iR3JpZCBUYWJsZSA3IENvbG9yZnVsIEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ2IiBOYW1lPSJMaXN0IFRhYmxlIDEgTGlnaHQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDciIE5hbWU9Ikxpc3QgVGFibGUgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OCIgTmFtZT0iTGlzdCBUYWJsZSAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ5IiBOYW1lPSJMaXN0IFRhYmxlIDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTAiIE5hbWU9Ikxpc3QgVGFibGUgNSBEYXJrIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUxIiBOYW1lPSJMaXN0IFRhYmxlIDYgQ29sb3JmdWwiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTIiIE5hbWU9Ikxpc3QgVGFibGUgNyBDb2xvcmZ1bCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NiIgTmFtZT0iTGlzdCBUYWJsZSAxIExpZ2h0IEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ3IiBOYW1lPSJMaXN0IFRhYmxlIDIgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDgiIE5hbWU9Ikxpc3QgVGFibGUgMyBBY2NlbnQgMSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OSIgTmFtZT0iTGlzdCBUYWJsZSA0IEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUwIiBOYW1lPSJMaXN0IFRhYmxlIDUgRGFyayBBY2NlbnQgMSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MSIgTmFtZT0iTGlzdCBUYWJsZSA2IENvbG9yZnVsIEFjY2VudCAxIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUyIiBOYW1lPSJMaXN0IFRhYmxlIDcgQ29sb3JmdWwgQWNjZW50IDEiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDYiIE5hbWU9Ikxpc3QgVGFibGUgMSBMaWdodCBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NyIgTmFtZT0iTGlzdCBUYWJsZSAyIEFjY2VudCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ4IiBOYW1lPSJMaXN0IFRhYmxlIDMgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDkiIE5hbWU9Ikxpc3QgVGFibGUgNCBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MCIgTmFtZT0iTGlzdCBUYWJsZSA1IERhcmsgQWNjZW50IDIiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTEiIE5hbWU9Ikxpc3QgVGFibGUgNiBDb2xvcmZ1bCBBY2NlbnQgMiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MiIgTmFtZT0iTGlzdCBUYWJsZSA3IENvbG9yZnVsIEFjY2VudCAyIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ2IiBOYW1lPSJMaXN0IFRhYmxlIDEgTGlnaHQgQWNjZW50IDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDciIE5hbWU9Ikxpc3QgVGFibGUgMiBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OCIgTmFtZT0iTGlzdCBUYWJsZSAzIEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ5IiBOYW1lPSJMaXN0IFRhYmxlIDQgQWNjZW50IDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTAiIE5hbWU9Ikxpc3QgVGFibGUgNSBEYXJrIEFjY2VudCAzIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUxIiBOYW1lPSJMaXN0IFRhYmxlIDYgQ29sb3JmdWwgQWNjZW50IDMiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTIiIE5hbWU9Ikxpc3QgVGFibGUgNyBDb2xvcmZ1bCBBY2NlbnQgMyIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NiIgTmFtZT0iTGlzdCBUYWJsZSAxIExpZ2h0IEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ3IiBOYW1lPSJMaXN0IFRhYmxlIDIgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDgiIE5hbWU9Ikxpc3QgVGFibGUgMyBBY2NlbnQgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OSIgTmFtZT0iTGlzdCBUYWJsZSA0IEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUwIiBOYW1lPSJMaXN0IFRhYmxlIDUgRGFyayBBY2NlbnQgNCIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MSIgTmFtZT0iTGlzdCBUYWJsZSA2IENvbG9yZnVsIEFjY2VudCA0Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUyIiBOYW1lPSJMaXN0IFRhYmxlIDcgQ29sb3JmdWwgQWNjZW50IDQiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDYiIE5hbWU9Ikxpc3QgVGFibGUgMSBMaWdodCBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0NyIgTmFtZT0iTGlzdCBUYWJsZSAyIEFjY2VudCA1Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ4IiBOYW1lPSJMaXN0IFRhYmxlIDMgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDkiIE5hbWU9Ikxpc3QgVGFibGUgNCBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MCIgTmFtZT0iTGlzdCBUYWJsZSA1IERhcmsgQWNjZW50IDUiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTEiIE5hbWU9Ikxpc3QgVGFibGUgNiBDb2xvcmZ1bCBBY2NlbnQgNSIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI1MiIgTmFtZT0iTGlzdCBUYWJsZSA3IENvbG9yZnVsIEFjY2VudCA1Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ2IiBOYW1lPSJMaXN0IFRhYmxlIDEgTGlnaHQgQWNjZW50IDYiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNDciIE5hbWU9Ikxpc3QgVGFibGUgMiBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFByaW9yaXR5PSI0OCIgTmFtZT0iTGlzdCBUYWJsZSAzIEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjQ5IiBOYW1lPSJMaXN0IFRhYmxlIDQgQWNjZW50IDYiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTAiIE5hbWU9Ikxpc3QgVGFibGUgNSBEYXJrIEFjY2VudCA2Ii8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgUHJpb3JpdHk9IjUxIiBOYW1lPSJMaXN0IFRhYmxlIDYgQ29sb3JmdWwgQWNjZW50IDYiLz4NCjx3OkxzZEV4Y2VwdGlvbiBMb2NrZWQ9ImZhbHNlIiBQcmlvcml0eT0iNTIiIE5hbWU9Ikxpc3QgVGFibGUgNyBDb2xvcmZ1bCBBY2NlbnQgNiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJNZW50aW9uIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlNtYXJ0IEh5cGVybGluayIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJIYXNodGFnIi8-DQo8dzpMc2RFeGNlcHRpb24gTG9ja2VkPSJmYWxzZSIgU2VtaUhpZGRlbj0idHJ1ZSIgVW5oaWRlV2hlblVzZWQ9InRydWUiIE5hbWU9IlVucmVzb2x2ZWQgTWVudGlvbiIvPg0KPHc6THNkRXhjZXB0aW9uIExvY2tlZD0iZmFsc2UiIFNlbWlIaWRkZW49InRydWUiIFVuaGlkZVdoZW5Vc2VkPSJ0cnVlIiBOYW1lPSJTbWFydCBMaW5rIi8-DQo8L3c6TGF0ZW50U3R5bGVzPg0KPC94bWw-PCFbZW5kaWZdLS0-PHN0eWxlPjwhLS0NCi8qIEZvbnQgRGVmaW5pdGlvbnMgKi8NCkBmb250LWZhY2UNCgl7Zm9udC1mYW1pbHk6V2luZ2RpbmdzOw0KCXBhbm9zZS0xOjUgMCAwIDAgMCAwIDAgMCAwIDA7DQoJbXNvLWZvbnQtY2hhcnNldDoyOw0KCW1zby1nZW5lcmljLWZvbnQtZmFtaWx5OmF1dG87DQoJbXNvLWZvbnQtcGl0Y2g6dmFyaWFibGU7DQoJbXNvLWZvbnQtc2lnbmF0dXJlOjAgMjY4NDM1NDU2IDAgMCAtMjE0NzQ4MzY0OCAwO30NCkBmb250LWZhY2UNCgl7Zm9udC1mYW1pbHk6IkNhbWJyaWEgTWF0aCI7DQoJcGFub3NlLTE6MiA0IDUgMyA1IDQgNiAzIDIgNDsNCgltc28tZm9udC1jaGFyc2V0OjA7DQoJbXNvLWdlbmVyaWMtZm9udC1mYW1pbHk6cm9tYW47DQoJbXNvLWZvbnQtcGl0Y2g6dmFyaWFibGU7DQoJbXNvLWZvbnQtc2lnbmF0dXJlOi01MzY4NjkxMjEgMTEwNzMwNTcyNyAzMzU1NDQzMiAwIDQxNSAwO30NCkBmb250LWZhY2UNCgl7Zm9udC1mYW1pbHk6Q2FsaWJyaTsNCglwYW5vc2UtMToyIDE1IDUgMiAyIDIgNCAzIDIgNDsNCgltc28tZm9udC1jaGFyc2V0OjA7DQoJbXNvLWdlbmVyaWMtZm9udC1mYW1pbHk6c3dpc3M7DQoJbXNvLWZvbnQtcGl0Y2g6dmFyaWFibGU7DQoJbXNvLWZvbnQtc2lnbmF0dXJlOi00Njk3NTAwMTcgLTEwNzM3MzI0ODUgOSAwIDUxMSAwO30NCi8qIFN0eWxlIERlZmluaXRpb25zICovDQpwLk1zb05vcm1hbCwgbGkuTXNvTm9ybWFsLCBkaXYuTXNvTm9ybWFsDQoJe21zby1zdHlsZS11bmhpZGU6bm87DQoJbXNvLXN0eWxlLXFmb3JtYXQ6eWVzOw0KCW1zby1zdHlsZS1wYXJlbnQ6IiI7DQoJbWFyZ2luOjBjbTsNCgltc28tcGFnaW5hdGlvbjp3aWRvdy1vcnBoYW47DQoJZm9udC1zaXplOjExLjBwdDsNCglmb250LWZhbWlseToiQ2FsaWJyaSIsc2Fucy1zZXJpZjsNCgltc28tZmFyZWFzdC1mb250LWZhbWlseTpDYWxpYnJpO30NCmgyDQoJe21zby1zdHlsZS1wcmlvcml0eTo5Ow0KCW1zby1zdHlsZS11bmhpZGU6bm87DQoJbXNvLXN0eWxlLXFmb3JtYXQ6eWVzOw0KCW1zby1zdHlsZS1saW5rOiJIZWFkaW5nIDIgQ2hhciI7DQoJbXNvLW1hcmdpbi10b3AtYWx0OmF1dG87DQoJbWFyZ2luLXJpZ2h0OjBjbTsNCgltc28tbWFyZ2luLWJvdHRvbS1hbHQ6YXV0bzsNCgltYXJnaW4tbGVmdDowY207DQoJbXNvLXBhZ2luYXRpb246d2lkb3ctb3JwaGFuOw0KCW1zby1vdXRsaW5lLWxldmVsOjI7DQoJZm9udC1zaXplOjE4LjBwdDsNCglmb250LWZhbWlseToiQ2FsaWJyaSIsc2Fucy1zZXJpZjsNCgltc28tZmFyZWFzdC1mb250LWZhbWlseTpDYWxpYnJpOw0KCWZvbnQtd2VpZ2h0OmJvbGQ7fQ0KaDMNCgl7bXNvLXN0eWxlLXByaW9yaXR5Ojk7DQoJbXNvLXN0eWxlLXVuaGlkZTpubzsNCgltc28tc3R5bGUtcWZvcm1hdDp5ZXM7DQoJbXNvLXN0eWxlLWxpbms6IkhlYWRpbmcgMyBDaGFyIjsNCgltc28tbWFyZ2luLXRvcC1hbHQ6YXV0bzsNCgltYXJnaW4tcmlnaHQ6MGNtOw0KCW1zby1tYXJnaW4tYm90dG9tLWFsdDphdXRvOw0KCW1hcmdpbi1sZWZ0OjBjbTsNCgltc28tcGFnaW5hdGlvbjp3aWRvdy1vcnBoYW47DQoJbXNvLW91dGxpbmUtbGV2ZWw6MzsNCglmb250LXNpemU6MTMuNXB0Ow0KCWZvbnQtZmFtaWx5OiJDYWxpYnJpIixzYW5zLXNlcmlmOw0KCW1zby1mYXJlYXN0LWZvbnQtZmFtaWx5OkNhbGlicmk7DQoJZm9udC13ZWlnaHQ6Ym9sZDt9DQphOmxpbmssIHNwYW4uTXNvSHlwZXJsaW5rDQoJe21zby1zdHlsZS1ub3Nob3c6eWVzOw0KCW1zby1zdHlsZS1wcmlvcml0eTo5OTsNCgljb2xvcjpibHVlOw0KCXRleHQtZGVjb3JhdGlvbjp1bmRlcmxpbmU7DQoJdGV4dC11bmRlcmxpbmU6c2luZ2xlO30NCmE6dmlzaXRlZCwgc3Bhbi5Nc29IeXBlcmxpbmtGb2xsb3dlZA0KCXttc28tc3R5bGUtbm9zaG93OnllczsNCgltc28tc3R5bGUtcHJpb3JpdHk6OTk7DQoJY29sb3I6cHVycGxlOw0KCXRleHQtZGVjb3JhdGlvbjp1bmRlcmxpbmU7DQoJdGV4dC11bmRlcmxpbmU6c2luZ2xlO30NCnAubXNvbm9ybWFsMCwgbGkubXNvbm9ybWFsMCwgZGl2Lm1zb25vcm1hbDANCgl7bXNvLXN0eWxlLW5hbWU6bXNvbm9ybWFsOw0KCW1zby1zdHlsZS11bmhpZGU6bm87DQoJbXNvLW1hcmdpbi10b3AtYWx0OmF1dG87DQoJbWFyZ2luLXJpZ2h0OjBjbTsNCgltc28tbWFyZ2luLWJvdHRvbS1hbHQ6YXV0bzsNCgltYXJnaW4tbGVmdDowY207DQoJbXNvLXBhZ2luYXRpb246d2lkb3ctb3JwaGFuOw0KCWZvbnQtc2l6ZToxMS4wcHQ7DQoJZm9udC1mYW1pbHk6IkNhbGlicmkiLHNhbnMtc2VyaWY7DQoJbXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6Q2FsaWJyaTt9DQpzcGFuLkhlYWRpbmcyQ2hhcg0KCXttc28tc3R5bGUtbmFtZToiSGVhZGluZyAyIENoYXIiOw0KCW1zby1zdHlsZS1ub3Nob3c6eWVzOw0KCW1zby1zdHlsZS1wcmlvcml0eTo5Ow0KCW1zby1zdHlsZS11bmhpZGU6bm87DQoJbXNvLXN0eWxlLWxvY2tlZDp5ZXM7DQoJbXNvLXN0eWxlLWxpbms6IkhlYWRpbmcgMiI7DQoJbXNvLWFuc2ktZm9udC1zaXplOjEzLjBwdDsNCgltc28tYmlkaS1mb250LXNpemU6MTMuMHB0Ow0KCWZvbnQtZmFtaWx5OiJDYWxpYnJpIExpZ2h0IixzYW5zLXNlcmlmOw0KCW1zby1hc2NpaS1mb250LWZhbWlseToiQ2FsaWJyaSBMaWdodCI7DQoJbXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7DQoJbXNvLWhhbnNpLWZvbnQtZmFtaWx5OiJDYWxpYnJpIExpZ2h0IjsNCgltc28tYmlkaS1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIjsNCgljb2xvcjojMkY1NDk2O30NCnNwYW4uSGVhZGluZzNDaGFyDQoJe21zby1zdHlsZS1uYW1lOiJIZWFkaW5nIDMgQ2hhciI7DQoJbXNvLXN0eWxlLW5vc2hvdzp5ZXM7DQoJbXNvLXN0eWxlLXByaW9yaXR5Ojk7DQoJbXNvLXN0eWxlLXVuaGlkZTpubzsNCgltc28tc3R5bGUtbG9ja2VkOnllczsNCgltc28tc3R5bGUtbGluazoiSGVhZGluZyAzIjsNCgltc28tYW5zaS1mb250LXNpemU6MTIuMHB0Ow0KCW1zby1iaWRpLWZvbnQtc2l6ZToxMi4wcHQ7DQoJZm9udC1mYW1pbHk6IkNhbGlicmkgTGlnaHQiLHNhbnMtc2VyaWY7DQoJbXNvLWFzY2lpLWZvbnQtZmFtaWx5OiJDYWxpYnJpIExpZ2h0IjsNCgltc28tZmFyZWFzdC1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIjsNCgltc28taGFuc2ktZm9udC1mYW1pbHk6IkNhbGlicmkgTGlnaHQiOw0KCW1zby1iaWRpLWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iOw0KCWNvbG9yOiMxRjM3NjM7fQ0Kc3Bhbi5FbWFpbFN0eWxlMjINCgl7bXNvLXN0eWxlLXR5cGU6cGVyc29uYWwtcmVwbHk7DQoJbXNvLXN0eWxlLW5vc2hvdzp5ZXM7DQoJbXNvLXN0eWxlLXVuaGlkZTpubzsNCgltc28tYW5zaS1mb250LXNpemU6MTEuMHB0Ow0KCW1zby1iaWRpLWZvbnQtc2l6ZToxMS4wcHQ7DQoJZm9udC1mYW1pbHk6IkNhbGlicmkiLHNhbnMtc2VyaWY7DQoJbXNvLWFzY2lpLWZvbnQtZmFtaWx5OkNhbGlicmk7DQoJbXNvLWhhbnNpLWZvbnQtZmFtaWx5OkNhbGlicmk7DQoJbXNvLWJpZGktZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7DQoJY29sb3I6IzFGNDk3RDsNCglmb250LXdlaWdodDpub3JtYWw7DQoJZm9udC1zdHlsZTpub3JtYWw7DQoJdGV4dC11bmRlcmxpbmU6IzQ0NzJDNDt9DQouTXNvQ2hwRGVmYXVsdA0KCXttc28tc3R5bGUtdHlwZTpleHBvcnQtb25seTsNCgltc28tZGVmYXVsdC1wcm9wczp5ZXM7DQoJZm9udC1zaXplOjEwLjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCW1zby1iaWRpLWZvbnQtc2l6ZToxMC4wcHQ7fQ0KQHBhZ2UgV29yZFNlY3Rpb24xDQoJe3NpemU6NjEyLjBwdCA3OTIuMHB0Ow0KCW1hcmdpbjo3Mi4wcHQgNzIuMHB0IDcyLjBwdCA3Mi4wcHQ7DQoJbXNvLWhlYWRlci1tYXJnaW46MzYuMHB0Ow0KCW1zby1mb290ZXItbWFyZ2luOjM2LjBwdDsNCgltc28tcGFwZXItc291cmNlOjA7fQ0KZGl2LldvcmRTZWN0aW9uMQ0KCXtwYWdlOldvcmRTZWN0aW9uMTt9DQovKiBMaXN0IERlZmluaXRpb25zICovDQpAbGlzdCBsMA0KCXttc28tbGlzdC1pZDo4Mzk2Mzk2NDsNCgltc28tbGlzdC10ZW1wbGF0ZS1pZHM6MTYxMjMyNzMzMDt9DQpAbGlzdCBsMDpsZXZlbDENCgl7bXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CtzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6MzYuMHB0Ow0KCW1zby1sZXZlbC1udW1iZXItcG9zaXRpb246bGVmdDsNCgl0ZXh0LWluZGVudDotMTguMHB0Ow0KCW1zby1hbnNpLWZvbnQtc2l6ZToxMC4wcHQ7DQoJZm9udC1mYW1pbHk6U3ltYm9sO30NCkBsaXN0IGwwOmxldmVsMg0KCXttc28tbGV2ZWwtc3RhcnQtYXQ6MDsNCgltc28tbGV2ZWwtbnVtYmVyLWZvcm1hdDpidWxsZXQ7DQoJbXNvLWxldmVsLXRleHQ6bzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6NzIuMHB0Ow0KCW1zby1sZXZlbC1udW1iZXItcG9zaXRpb246bGVmdDsNCgl0ZXh0LWluZGVudDotMTguMHB0Ow0KCW1zby1hbnNpLWZvbnQtc2l6ZToxMC4wcHQ7DQoJZm9udC1mYW1pbHk6IkNvdXJpZXIgTmV3IjsNCgltc28tYmlkaS1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIjt9DQpAbGlzdCBsMDpsZXZlbDMNCgl7bXNvLWxldmVsLXN0YXJ0LWF0OjA7DQoJbXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CpzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6MTA4LjBwdDsNCgltc28tbGV2ZWwtbnVtYmVyLXBvc2l0aW9uOmxlZnQ7DQoJdGV4dC1pbmRlbnQ6LTE4LjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCWZvbnQtZmFtaWx5OldpbmdkaW5nczt9DQpAbGlzdCBsMDpsZXZlbDQNCgl7bXNvLWxldmVsLXN0YXJ0LWF0OjA7DQoJbXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CpzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6MTQ0LjBwdDsNCgltc28tbGV2ZWwtbnVtYmVyLXBvc2l0aW9uOmxlZnQ7DQoJdGV4dC1pbmRlbnQ6LTE4LjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCWZvbnQtZmFtaWx5OldpbmdkaW5nczt9DQpAbGlzdCBsMDpsZXZlbDUNCgl7bXNvLWxldmVsLXN0YXJ0LWF0OjA7DQoJbXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CpzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6MTgwLjBwdDsNCgltc28tbGV2ZWwtbnVtYmVyLXBvc2l0aW9uOmxlZnQ7DQoJdGV4dC1pbmRlbnQ6LTE4LjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCWZvbnQtZmFtaWx5OldpbmdkaW5nczt9DQpAbGlzdCBsMDpsZXZlbDYNCgl7bXNvLWxldmVsLXN0YXJ0LWF0OjA7DQoJbXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CpzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6MjE2LjBwdDsNCgltc28tbGV2ZWwtbnVtYmVyLXBvc2l0aW9uOmxlZnQ7DQoJdGV4dC1pbmRlbnQ6LTE4LjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCWZvbnQtZmFtaWx5OldpbmdkaW5nczt9DQpAbGlzdCBsMDpsZXZlbDcNCgl7bXNvLWxldmVsLXN0YXJ0LWF0OjA7DQoJbXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CpzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6MjUyLjBwdDsNCgltc28tbGV2ZWwtbnVtYmVyLXBvc2l0aW9uOmxlZnQ7DQoJdGV4dC1pbmRlbnQ6LTE4LjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCWZvbnQtZmFtaWx5OldpbmdkaW5nczt9DQpAbGlzdCBsMDpsZXZlbDgNCgl7bXNvLWxldmVsLXN0YXJ0LWF0OjA7DQoJbXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CpzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6Mjg4LjBwdDsNCgltc28tbGV2ZWwtbnVtYmVyLXBvc2l0aW9uOmxlZnQ7DQoJdGV4dC1pbmRlbnQ6LTE4LjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCWZvbnQtZmFtaWx5OldpbmdkaW5nczt9DQpAbGlzdCBsMDpsZXZlbDkNCgl7bXNvLWxldmVsLXN0YXJ0LWF0OjA7DQoJbXNvLWxldmVsLW51bWJlci1mb3JtYXQ6YnVsbGV0Ow0KCW1zby1sZXZlbC10ZXh0Ou-CpzsNCgltc28tbGV2ZWwtdGFiLXN0b3A6MzI0LjBwdDsNCgltc28tbGV2ZWwtbnVtYmVyLXBvc2l0aW9uOmxlZnQ7DQoJdGV4dC1pbmRlbnQ6LTE4LjBwdDsNCgltc28tYW5zaS1mb250LXNpemU6MTAuMHB0Ow0KCWZvbnQtZmFtaWx5OldpbmdkaW5nczt9DQpvbA0KCXttYXJnaW4tYm90dG9tOjBjbTt9DQp1bA0KCXttYXJnaW4tYm90dG9tOjBjbTt9DQotLT48L3N0eWxlPjwhLS1baWYgZ3RlIG1zbyAxMF0-PHN0eWxlPi8qIFN0eWxlIERlZmluaXRpb25zICovDQp0YWJsZS5Nc29Ob3JtYWxUYWJsZQ0KCXttc28tc3R5bGUtbmFtZToiVGFibGUgTm9ybWFsIjsNCgltc28tdHN0eWxlLXJvd2JhbmQtc2l6ZTowOw0KCW1zby10c3R5bGUtY29sYmFuZC1zaXplOjA7DQoJbXNvLXN0eWxlLW5vc2hvdzp5ZXM7DQoJbXNvLXN0eWxlLXByaW9yaXR5Ojk5Ow0KCW1zby1zdHlsZS1wYXJlbnQ6IiI7DQoJbXNvLXBhZGRpbmctYWx0OjBjbSA1LjRwdCAwY20gNS40cHQ7DQoJbXNvLXBhcmEtbWFyZ2luOjBjbTsNCgltc28tcGFnaW5hdGlvbjp3aWRvdy1vcnBoYW47DQoJZm9udC1zaXplOjEwLjBwdDsNCglmb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIixzZXJpZjt9DQo8L3N0eWxlPjwhW2VuZGlmXS0tPjwhLS1baWYgZ3RlIG1zbyA5XT48eG1sPg0KPG86c2hhcGVkZWZhdWx0cyB2OmV4dD0iZWRpdCIgc3BpZG1heD0iMTAyNiIgLz4NCjwveG1sPjwhW2VuZGlmXS0tPjwhLS1baWYgZ3RlIG1zbyA5XT48eG1sPg0KPG86c2hhcGVsYXlvdXQgdjpleHQ9ImVkaXQiPg0KPG86aWRtYXAgdjpleHQ9ImVkaXQiIGRhdGE9IjEiIC8-DQo8L286c2hhcGVsYXlvdXQ-PC94bWw-PCFbZW5kaWZdLS0-PC9oZWFkPjxib2R5IGxhbmc9RU4tQVUgbGluaz1ibHVlIHZsaW5rPXB1cnBsZSBzdHlsZT0ndGFiLWludGVydmFsOjM2LjBwdDt3b3JkLXdyYXA6YnJlYWstd29yZCc-PGRpdiBjbGFzcz1Xb3JkU2VjdGlvbjE-PHAgY2xhc3M9TXNvTm9ybWFsPjxzcGFuIHN0eWxlPSdtc28tYXNjaWktZm9udC1mYW1pbHk6Q2FsaWJyaTttc28taGFuc2ktZm9udC1mYW1pbHk6Q2FsaWJyaTttc28tYmlkaS1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIjtjb2xvcjojMUY0OTdEO21zby1mYXJlYXN0LWxhbmd1YWdlOkVOLVVTJz5JIGp1c3QgcmVjZWl2ZWQgdGhlIGdvb2RzLiBJIHRob3VnaHQgSSB3YXMgZ2V0dGluZyBhIHJvbGxlciBmb3IgdGhlIGZsb29yIGluc3VsYXRpb24_PG86cD48L286cD48L3NwYW4-PC9wPjxwIGNsYXNzPU1zb05vcm1hbD48c3BhbiBzdHlsZT0nbXNvLWFzY2lpLWZvbnQtZmFtaWx5OkNhbGlicmk7bXNvLWhhbnNpLWZvbnQtZmFtaWx5OkNhbGlicmk7bXNvLWJpZGktZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7Y29sb3I6IzFGNDk3RDttc28tZmFyZWFzdC1sYW5ndWFnZTpFTi1VUyc-PG86cD4mbmJzcDs8L286cD48L3NwYW4-PC9wPjxwIGNsYXNzPU1zb05vcm1hbD48c3BhbiBzdHlsZT0nbXNvLWFzY2lpLWZvbnQtZmFtaWx5OkNhbGlicmk7bXNvLWhhbnNpLWZvbnQtZmFtaWx5OkNhbGlicmk7bXNvLWJpZGktZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7Y29sb3I6IzFGNDk3RDttc28tZmFyZWFzdC1sYW5ndWFnZTpFTi1VUyc-UGV0ZXIgQmF0bGV5PG86cD48L286cD48L3NwYW4-PC9wPjxwIGNsYXNzPU1zb05vcm1hbD48c3BhbiBzdHlsZT0nbXNvLWFzY2lpLWZvbnQtZmFtaWx5OkNhbGlicmk7bXNvLWhhbnNpLWZvbnQtZmFtaWx5OkNhbGlicmk7bXNvLWJpZGktZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7Y29sb3I6IzFGNDk3RDttc28tZmFyZWFzdC1sYW5ndWFnZTpFTi1VUyc-PG86cD4mbmJzcDs8L286cD48L3NwYW4-PC9wPjxkaXY-PGRpdiBzdHlsZT0nYm9yZGVyOm5vbmU7Ym9yZGVyLXRvcDpzb2xpZCAjRTFFMUUxIDEuMHB0O3BhZGRpbmc6My4wcHQgMGNtIDBjbSAwY20nPjxwIGNsYXNzPU1zb05vcm1hbD48Yj48c3BhbiBsYW5nPUVOLVVTIHN0eWxlPSdtc28tZmFyZWFzdC1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIjttc28tYW5zaS1sYW5ndWFnZTpFTi1VUyc-RnJvbTo8L3NwYW4-PC9iPjxzcGFuIGxhbmc9RU4tVVMgc3R5bGU9J21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iO21zby1hbnNpLWxhbmd1YWdlOkVOLVVTJz4gRnJhbmtpZXMgQXV0byBFbGVjdHJpY3MgJmx0O3NhbGVzQGZyYW5raWVzYXV0b2VsZWN0cmljcy5jb20uYXUmZ3Q7IDxicj48Yj5TZW50OjwvYj4gV2VkbmVzZGF5LCAxMSBNYXkgMjAyMiA3OjQ0IEFNPGJyPjxiPlRvOjwvYj4gcGJhdGxleTYxQGdtYWlsLmNvbTxicj48Yj5TdWJqZWN0OjwvYj4gWW91ciBGcmFua2llcyBBdXRvIEVsZWN0cmljcyBPcmRlciBIYXMgQmVlbiBVcGRhdGVkICgjMTE5NTE5KTxvOnA-PC9vOnA-PC9zcGFuPjwvcD48L2Rpdj48L2Rpdj48cCBjbGFzcz1Nc29Ob3JtYWw-PG86cD4mbmJzcDs8L286cD48L3A-PGRpdj48ZGl2IHN0eWxlPSdtc28tZWxlbWVudDpwYXJhLWJvcmRlci1kaXY7Ym9yZGVyOm5vbmU7Ym9yZGVyLWJvdHRvbTpkYXNoZWQgZ3JheSAxLjBwdDttc28tYm9yZGVyLWJvdHRvbS1hbHQ6ZGFzaGVkIGdyYXkgLjc1cHQ7cGFkZGluZzowY20gMGNtIDMuMHB0IDBjbSc-PGgyIHN0eWxlPSdib3JkZXI6bm9uZTttc28tYm9yZGVyLWJvdHRvbS1hbHQ6ZGFzaGVkIGdyYXkgLjc1cHQ7cGFkZGluZzowY207bXNvLXBhZGRpbmctYWx0OjBjbSAwY20gMy4wcHQgMGNtJz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjE2LjVwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7Y29sb3I6I0NDNjYwMCc-T3JkZXIgU3RhdHVzIENoYW5nZWQ8bzpwPjwvbzpwPjwvc3Bhbj48L2gyPjwvZGl2PjxwPjxzcGFuIHN0eWxlPSdmb250LXNpemU6OS4wcHQ7Zm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmJz5IaSBQZXRlcjxvOnA-PC9vOnA-PC9zcGFuPjwvcD48cD48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O2ZvbnQtZmFtaWx5OiJBcmlhbCIsc2Fucy1zZXJpZic-QW4gb3JkZXIgeW91IHJlY2VudGx5IHBsYWNlZCBvbiBvdXIgd2Vic2l0ZSBoYXMgaGFkIGl0cyBzdGF0dXMgY2hhbmdlZC48bzpwPjwvbzpwPjwvc3Bhbj48L3A-PHA-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZTo5LjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWYnPlRoZSBzdGF0dXMgb2Ygb3JkZXIgIzExOTUxOSBpcyBub3cgPHN0cm9uZz48c3BhbiBzdHlsZT0nZm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmJz5Db21wbGV0ZWQ8L3NwYW4-PC9zdHJvbmc-PG86cD48L286cD48L3NwYW4-PC9wPjxoMz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjEwLjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7Y29sb3I6I0NDNjYwMCc-T3JkZXIgRGV0YWlsczxvOnA-PC9vOnA-PC9zcGFuPjwvaDM-PHRhYmxlIGNsYXNzPU1zb05vcm1hbFRhYmxlIGJvcmRlcj0wIGNlbGxwYWRkaW5nPTAgd2lkdGg9IjEwMCUiIHN0eWxlPSd3aWR0aDoxMDAuMCU7bXNvLWNlbGxzcGFjaW5nOjEuNXB0O21zby15ZnRpLXRibGxvb2s6MTE4NCc-PHRyIHN0eWxlPSdtc28teWZ0aS1pcm93OjA7bXNvLXlmdGktZmlyc3Ryb3c6eWVzJz48dGQgd2lkdGg9IjI1JSIgbm93cmFwIHZhbGlnbj10b3Agc3R5bGU9J3dpZHRoOjI1LjAlO3BhZGRpbmc6Ljc1cHQgLjc1cHQgLjc1cHQgLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbD48Yj48c3BhbiBzdHlsZT0nZm9udC1zaXplOjEwLjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPk9yZGVyIFRvdGFsOjxvOnA-PC9vOnA-PC9zcGFuPjwvYj48L3A-PC90ZD48dGQgc3R5bGU9J3BhZGRpbmc6Ljc1cHQgLjc1cHQgLjc1cHQgLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbD48c3BhbiBzdHlsZT0nZm9udC1zaXplOjEwLjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPiQ1MTQuMTAgQVVEPG86cD48L286cD48L3NwYW4-PC9wPjwvdGQ-PC90cj48dHIgc3R5bGU9J21zby15ZnRpLWlyb3c6MSc-PHRkIHdpZHRoPSIyNSUiIG5vd3JhcCB2YWxpZ249dG9wIHN0eWxlPSd3aWR0aDoyNS4wJTtwYWRkaW5nOi43NXB0IC43NXB0IC43NXB0IC43NXB0Jz48cCBjbGFzcz1Nc29Ob3JtYWw-PGI-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZToxMC4wcHQ7Zm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmO21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iJz5EYXRlIFBsYWNlZDo8bzpwPjwvbzpwPjwvc3Bhbj48L2I-PC9wPjwvdGQ-PHRkIHN0eWxlPSdwYWRkaW5nOi43NXB0IC43NXB0IC43NXB0IC43NXB0Jz48cCBjbGFzcz1Nc29Ob3JtYWw-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZToxMC4wcHQ7Zm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmO21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iJz42dGggTWF5IDIwMjI8bzpwPjwvbzpwPjwvc3Bhbj48L3A-PC90ZD48L3RyPjx0ciBzdHlsZT0nbXNvLXlmdGktaXJvdzoyO21zby15ZnRpLWxhc3Ryb3c6eWVzJz48dGQgd2lkdGg9IjI1JSIgbm93cmFwIHZhbGlnbj10b3Agc3R5bGU9J3dpZHRoOjI1LjAlO3BhZGRpbmc6Ljc1cHQgLjc1cHQgLjc1cHQgLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbD48Yj48c3BhbiBzdHlsZT0nZm9udC1zaXplOjEwLjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPlBheW1lbnQgTWV0aG9kOjxvOnA-PC9vOnA-PC9zcGFuPjwvYj48L3A-PC90ZD48dGQgc3R5bGU9J3BhZGRpbmc6Ljc1cHQgLjc1cHQgLjc1cHQgLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbD48c3BhbiBzdHlsZT0nZm9udC1zaXplOjEwLjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPlBheVBhbDxvOnA-PC9vOnA-PC9zcGFuPjwvcD48L3RkPjwvdHI-PC90YWJsZT48aDM-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZToxMC4wcHQ7Zm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmO21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iO2NvbG9yOiNDQzY2MDAnPlByb2R1Y3RzIHNoaXBwZWQ8bzpwPjwvbzpwPjwvc3Bhbj48L2gzPjx0YWJsZSBjbGFzcz1Nc29Ob3JtYWxUYWJsZSBib3JkZXI9MCBjZWxsc3BhY2luZz0wIGNlbGxwYWRkaW5nPTAgd2lkdGg9IjEwMCUiIHN0eWxlPSd3aWR0aDoxMDAuMCU7bXNvLWNlbGxzcGFjaW5nOjBjbTttc28teWZ0aS10Ymxsb29rOjExODQ7bXNvLXBhZGRpbmctYWx0OjBjbSAwY20gMGNtIDBjbSc-PHRyIHN0eWxlPSdtc28teWZ0aS1pcm93OjA7bXNvLXlmdGktZmlyc3Ryb3c6eWVzJz48dGQgbm93cmFwIHN0eWxlPSdiYWNrZ3JvdW5kOiMwMjBCNkY7cGFkZGluZzozLjc1cHQgMy43NXB0IDMuNzVwdCAzLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbD48Yj48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O2ZvbnQtZmFtaWx5OiJBcmlhbCIsc2Fucy1zZXJpZjttc28tZmFyZWFzdC1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIjtjb2xvcjp3aGl0ZSc-Q2FydCBJdGVtczxvOnA-PC9vOnA-PC9zcGFuPjwvYj48L3A-PC90ZD48dGQgd2lkdGg9MTAwIG5vd3JhcCBzdHlsZT0nd2lkdGg6NzUuMHB0O2JhY2tncm91bmQ6IzAyMEI2RjtwYWRkaW5nOjMuNzVwdCAzLjc1cHQgMy43NXB0IDMuNzVwdCc-PHAgY2xhc3M9TXNvTm9ybWFsIGFsaWduPWNlbnRlciBzdHlsZT0ndGV4dC1hbGlnbjpjZW50ZXInPjxiPjxzcGFuIHN0eWxlPSdmb250LXNpemU6OS4wcHQ7Zm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmO21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iO2NvbG9yOndoaXRlJz5TS1U8bzpwPjwvbzpwPjwvc3Bhbj48L2I-PC9wPjwvdGQ-PHRkIHdpZHRoPTEwMCBub3dyYXAgc3R5bGU9J3dpZHRoOjc1LjBwdDtiYWNrZ3JvdW5kOiMwMjBCNkY7cGFkZGluZzozLjc1cHQgMy43NXB0IDMuNzVwdCAzLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbCBhbGlnbj1jZW50ZXIgc3R5bGU9J3RleHQtYWxpZ246Y2VudGVyJz48Yj48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O2ZvbnQtZmFtaWx5OiJBcmlhbCIsc2Fucy1zZXJpZjttc28tZmFyZWFzdC1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIjtjb2xvcjp3aGl0ZSc-UXR5PG86cD48L286cD48L3NwYW4-PC9iPjwvcD48L3RkPjwvdHI-PHRyIHN0eWxlPSdtc28teWZ0aS1pcm93OjEnPjx0ZCBzdHlsZT0nYm9yZGVyOm5vbmU7Ym9yZGVyLWJvdHRvbTpzb2xpZCAjQ0FDQUNBIDEuMHB0O21zby1ib3JkZXItYm90dG9tLWFsdDpzb2xpZCAjQ0FDQUNBIC43NXB0O3BhZGRpbmc6My43NXB0IDMuNzVwdCAzLjc1cHQgMy43NXB0Jz48cCBjbGFzcz1Nc29Ob3JtYWw-PHN0cm9uZz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O2ZvbnQtZmFtaWx5OiJDYWxpYnJpIixzYW5zLXNlcmlmO21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iJz5EeW5hbWF0IDEwNDU1IFh0cmVtZSBCbGFjayBCdWxrIFBhY2sgU291bmQgRGVhZGVuZXIgLSA5IFNoZWV0czwvc3Bhbj48L3N0cm9uZz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iJz4gPG86cD48L286cD48L3NwYW4-PC9wPjwvdGQ-PHRkIHdpZHRoPTEwMCBzdHlsZT0nd2lkdGg6NzUuMHB0O2JvcmRlcjpub25lO2JvcmRlci1ib3R0b206c29saWQgI0NBQ0FDQSAxLjBwdDttc28tYm9yZGVyLWJvdHRvbS1hbHQ6c29saWQgI0NBQ0FDQSAuNzVwdDtwYWRkaW5nOjMuNzVwdCAzLjc1cHQgMy43NXB0IDMuNzVwdCc-PHAgY2xhc3M9TXNvTm9ybWFsIGFsaWduPWNlbnRlciBzdHlsZT0ndGV4dC1hbGlnbjpjZW50ZXInPjxzcGFuIHN0eWxlPSdmb250LXNpemU6OS4wcHQ7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPjEwNDU1PG86cD48L286cD48L3NwYW4-PC9wPjwvdGQ-PHRkIHdpZHRoPTEwMCBzdHlsZT0nd2lkdGg6NzUuMHB0O2JvcmRlcjpub25lO2JvcmRlci1ib3R0b206c29saWQgI0NBQ0FDQSAxLjBwdDttc28tYm9yZGVyLWJvdHRvbS1hbHQ6c29saWQgI0NBQ0FDQSAuNzVwdDtwYWRkaW5nOjMuNzVwdCAzLjc1cHQgMy43NXB0IDMuNzVwdCc-PHAgY2xhc3M9TXNvTm9ybWFsIGFsaWduPWNlbnRlciBzdHlsZT0ndGV4dC1hbGlnbjpjZW50ZXInPjxzcGFuIHN0eWxlPSdmb250LXNpemU6OS4wcHQ7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPjE8bzpwPjwvbzpwPjwvc3Bhbj48L3A-PC90ZD48L3RyPjx0ciBzdHlsZT0nbXNvLXlmdGktaXJvdzoyO21zby15ZnRpLWxhc3Ryb3c6eWVzJz48dGQgc3R5bGU9J2JvcmRlcjpub25lO2JvcmRlci1ib3R0b206c29saWQgI0NBQ0FDQSAxLjBwdDttc28tYm9yZGVyLWJvdHRvbS1hbHQ6c29saWQgI0NBQ0FDQSAuNzVwdDtwYWRkaW5nOjMuNzVwdCAzLjc1cHQgMy43NXB0IDMuNzVwdCc-PHAgY2xhc3M9TXNvTm9ybWFsPjxzdHJvbmc-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZTo5LjBwdDtmb250LWZhbWlseToiQ2FsaWJyaSIsc2Fucy1zZXJpZjttc28tZmFyZWFzdC1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIic-RHluYW1hdCAxMDQzNSBYdHJlbWUgU291bmQgRGVhZGVuZXIgRG9vciBLaXQ8L3NwYW4-PC9zdHJvbmc-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZTo5LjBwdDttc28tZmFyZWFzdC1mb250LWZhbWlseToiVGltZXMgTmV3IFJvbWFuIic-IDxvOnA-PC9vOnA-PC9zcGFuPjwvcD48L3RkPjx0ZCB3aWR0aD0xMDAgc3R5bGU9J3dpZHRoOjc1LjBwdDtib3JkZXI6bm9uZTtib3JkZXItYm90dG9tOnNvbGlkICNDQUNBQ0EgMS4wcHQ7bXNvLWJvcmRlci1ib3R0b20tYWx0OnNvbGlkICNDQUNBQ0EgLjc1cHQ7cGFkZGluZzozLjc1cHQgMy43NXB0IDMuNzVwdCAzLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbCBhbGlnbj1jZW50ZXIgc3R5bGU9J3RleHQtYWxpZ246Y2VudGVyJz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iJz4xMDQzNTxvOnA-PC9vOnA-PC9zcGFuPjwvcD48L3RkPjx0ZCB3aWR0aD0xMDAgc3R5bGU9J3dpZHRoOjc1LjBwdDtib3JkZXI6bm9uZTtib3JkZXItYm90dG9tOnNvbGlkICNDQUNBQ0EgMS4wcHQ7bXNvLWJvcmRlci1ib3R0b20tYWx0OnNvbGlkICNDQUNBQ0EgLjc1cHQ7cGFkZGluZzozLjc1cHQgMy43NXB0IDMuNzVwdCAzLjc1cHQnPjxwIGNsYXNzPU1zb05vcm1hbCBhbGlnbj1jZW50ZXIgc3R5bGU9J3RleHQtYWxpZ246Y2VudGVyJz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iJz4yPG86cD48L286cD48L3NwYW4-PC9wPjwvdGQ-PC90cj48L3RhYmxlPjxoMz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjEwLjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiI7Y29sb3I6I0NDNjYwMCc-U2hpcG1lbnQgVHJhY2tpbmcgTnVtYmVycyAvIExpbmtzPG86cD48L286cD48L3NwYW4-PC9oMz48dWwgdHlwZT1kaXNjPjxsaSBjbGFzcz1Nc29Ob3JtYWwgc3R5bGU9J21zby1tYXJnaW4tdG9wLWFsdDphdXRvO21zby1tYXJnaW4tYm90dG9tLWFsdDphdXRvO21zby1saXN0OmwwIGxldmVsMSBsZm8xO3RhYi1zdG9wczpsaXN0IDM2LjBwdCc-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZTo5LjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPjxhIGhyZWY9IiIgdGFyZ2V0PSJfYmxhbmsiPk1QMDAxMjc3NDAxNjwvYT4gKEZsYXQgUmF0ZSk8bzpwPjwvbzpwPjwvc3Bhbj48L2xpPjwvdWw-PHA-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZTo5LjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWYnPjxhIGhyZWY9Imh0dHBzOi8vZnJhbmtpZXNhdXRvZWxlY3RyaWNzLmNvbS5hdS9vcmRlcnN0YXR1cy5waHAiPkNsaWNrIGhlcmUgdG8gdmlldyB0aGUgc3RhdHVzIG9mIHlvdXIgb3JkZXI8L2E-PG86cD48L286cD48L3NwYW4-PC9wPjxwIHN0eWxlPSdtYXJnaW4tdG9wOjkuMHB0Jz48c3Ryb25nPjxzcGFuIHN0eWxlPSdmb250LXNpemU6OS4wcHQ7Zm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmJz5GcmFua2llcyBBdXRvIEVsZWN0cmljczwvc3Bhbj48L3N0cm9uZz48c3BhbiBzdHlsZT0nZm9udC1zaXplOjkuMHB0O2ZvbnQtZmFtaWx5OiJBcmlhbCIsc2Fucy1zZXJpZic-IDxicj5BQk46IDUyIDYzMCA2MDEgMTg4PGJyPjxhIGhyZWY9Imh0dHBzOi8vZnJhbmtpZXNhdXRvZWxlY3RyaWNzLmNvbS5hdS8iPmh0dHBzOi8vZnJhbmtpZXNhdXRvZWxlY3RyaWNzLmNvbS5hdS88YnI-PC9hPjxvOnA-PC9vOnA-PC9zcGFuPjwvcD48cD48c3BhbiBzdHlsZT0nZm9udC1zaXplOjcuNXB0O2ZvbnQtZmFtaWx5OiJBcmlhbCIsc2Fucy1zZXJpZjtjb2xvcjojQjcxQzFDJz4qRGVsaXZlcmllcyB0byBOb3J0aGVybiBUZXJyaXRvcnkgbWF5IGhhdmUgZXh0cmEgZnJlaWdodCBjaGFyZ2VzPG86cD48L286cD48L3NwYW4-PC9wPjxkaXYgY2xhc3M9TXNvTm9ybWFsIGFsaWduPWNlbnRlciBzdHlsZT0nbWFyZ2luLXRvcDo2LjBwdDt0ZXh0LWFsaWduOmNlbnRlcic-PHNwYW4gc3R5bGU9J2ZvbnQtc2l6ZTo5LjBwdDtmb250LWZhbWlseToiQXJpYWwiLHNhbnMtc2VyaWY7bXNvLWZhcmVhc3QtZm9udC1mYW1pbHk6IlRpbWVzIE5ldyBSb21hbiInPjxociBzaXplPTEgd2lkdGg9IjEwMCUiIG5vc2hhZGUgc3R5bGU9J2NvbG9yOiM0NDQ0NDQnIGFsaWduPWNlbnRlcj48L3NwYW4-PC9kaXY-PGRpdj48cCBjbGFzcz1Nc29Ob3JtYWwgc3R5bGU9J21hcmdpbi10b3A6Ni4wcHQnPjxzcGFuIHN0eWxlPSdmb250LXNpemU6OC41cHQ7Zm9udC1mYW1pbHk6IkFyaWFsIixzYW5zLXNlcmlmO21zby1mYXJlYXN0LWZvbnQtZmFtaWx5OiJUaW1lcyBOZXcgUm9tYW4iO2NvbG9yOiM0NDQ0NDQnPkZyYW5raWVzIEF1dG8gRWxlY3RyaWNzIGlzIHBvd2VyZWQgYnkgQmlnY29tbWVyY2UuIDxhIGhyZWY9Imh0dHA6Ly93d3cuYmlnY29tbWVyY2UuY29tLyI-TGF1bmNoIHlvdXIgb3duIHN0b3JlIGZvciBmcmVlPC9hPiB3aXRoIEJpZ2NvbW1lcmNlLjxvOnA-PC9vOnA-PC9zcGFuPjwvcD48L2Rpdj48L2Rpdj48L2Rpdj48L2JvZHk-PC9odG1sPg==', null) );

        // $user = User::withTrashed()->where('id', 20)->first();
        // dd($user->tickets()->where('status_id', TicketStatus::STATUS_PENDING)->get()->toArray());

        $ticketIds = '30642,30640,30639,30637,30636,30635,30633,30632,30630,30627,30623,30622,30620,30619,30605,30596,30590,30589,30587,30579,30575,30574,30573,30572,30570,30563,30561,30560,30559,30558,30557,30555,30554,30551,30550,30549,30547,30546,30545,30544,30543,30540,30537,30536,30534,30525,30520,30515,30514,30511,30501,30488,30484,30476,30471,30468,30465,30462,30460,30459,30453,30441,30431,30406,30402,30394,30381,30371,30350,30344,30342,30341,30338,30324,30311,30303,30268,30246,30245,30244,30233,30228,30214,30212,30209,30203,30123,30121,30111,30087,30053,30048,30037,30009,29996,29978,29973,29968,29919,29896,29894,29882,29875,29790,29655,29627,29576,29462,29459,29437,29377,29311,29223,29203,29092,29035,28852,28374,27279,27278,27266,27264,27263,27261,27260,27255,27246,24716,21968,19630,19622,19613,19596,19553,19549,19548,19497,19492,19478,19407,19406,19405,19401,19391,19390,19353,19308,19292,19290,19289,19286,19278,19277,19256,19153,19152,19144,19068,19065,19031,19022,18901,18898,18777,18749,18748,18746,18745,18744,18676,18674,18649,18629,18569,18538,18474,18451,18446,18443,18273,18261,18256,18253,18251,18239,18230,18227,18096,18094,18073,17906,17893,17868,17860,17837,17836,17807,17667,17666,17640,17449,17441,17428,17336,17268,17252,17251,17186,17083,17063,17061,17048,17010,16922,16919,16801,16800,16775,16734,16712,16644,16624,16587,16586,16501,16500,16484,16483,16461,16406,16359,16343,16342,16341,16257,16249,16247,16243,16238,16173,16137,16136,16135,16134,16129,16071,16070,16069,16068,16056,16055,16051,16040,16039,16037,16036,16034,16033,16032,16031,16030,16025,15993,7786,6994,6246,1434,1124,1042,598';
        $ticketIds = explode(',', $ticketIds);

        foreach($ticketIds as $ticketId)
        {
            $assignTicket = AssignedTicket::where('ticket_id', $ticketId)->count();

            if(!$assignTicket)
            {
                dump($ticketId);
            }
        }

        dd();
        /*$user = User::withTrashed()->where('id', 20)->first();
        dump($user->name);
        $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->orderBy('created_at', 'DESC')->get()->toArray();

        $arrIds = [];

        foreach($tickets as $ticket)
        {
            array_push($arrIds, $ticket['id']);
        }

        dump($arrIds);

        dd( implode(',', $arrIds) );*/

        $ticket = new Ticket;
        dd($ticket->count_my_tickets());

        $ticket = Ticket::find(30339);

        dump($ticket);
        dump($ticket->assignedTo()->count());
        dump($ticket->assignedTo->user->name);

        dd();

        $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_SOLVED])->whereBetween('updated_at', 

                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                        )->orderBy('updated_at', 'DESC')->count();
        dd($tickets);

        $items = Item::select('*')

                        ->whereBetween('created_at', 

                            [Carbon::now()->subMonth(6), Carbon::now()]

                        )

                        ->get();

        dd();
        // dd(Session::all());
        $message = $this->messageEncode('SeKAmW0gbm90IFJvYmVydCBlbWFpbCB0aGUgcmlnaHQgcGVyc29uIG1pZ2h0IGhlbHAg8J-kpvCfj73igI3imYLvuI8NCg0KU2VudCBmcm9tIG15IGlQaG9uZQ0KDQo-IE9uIDEwIEFwciAyMDIyLCBhdCAxMDo0OSBhbSwgQ3VzdG9tZXIgU2VydmljZSA8c3VwcG9ydEBmcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1PiB3cm90ZToNCj4gDQo-IO-7vw0KPiBIaSBSb2JlcnQsDQo-ICANCj4gSG9wZSB5b3UgYXJlIGRvaW5nIHdlbGwuIEFjY291bnRpbmcgRGVwYXJ0bWVudCBpcyBvbmx5IG9wZW4gZHVyaW5nIGJ1c2luZXNzIGRheXMuDQo-ICANCj4gV2UgYWxyZWFkeSBtYWRlIGEgZm9sbG93IHVwIGFuZCB3ZSB1bmRlcnN0YW5kIHRoYXQgeW91IG5lZWQgdGhlIHJlZnVuZCBhcyBzb29uIGFzIHBvc3NpYmxlLg0KPiAgDQo-IFdlIHNlbnQgdGhpcyBjb25jZXJuIHRvIG91ciBlc2NhbGF0aW9uIHRlYW0gc28gdGhhdCB3ZSBjYW4gcHJvY2VzcyB0aGUgcmVmdW5kIHJpZ2h0IGF3YXkuDQo-ICANCj4gV2UgYXBwcmVjaWF0ZSB5b3VyIHBhdGllbmNlIGFuZCB1bmRlcnN0YW5kaW5nIHJlZ2FyZGluZyB0aGlzLiBQbGVhc2UgbGV0IHVzIGtub3cgaWYgdGhlcmUgaXMgYW55dGhpbmcgZWxzZSB3ZSBjYW4gaGVscCB5b3Ugd2l0aC4NCj4gIA0KPiAgDQo-IEFsbCB0aGUgYmVzdCwNCj4gRnJhbmtpZXMgQXV0byBFbGVjdHJpY3MNCg==', null);
        echo $message;
        dd($message);
        
        dd( GmailApi::threadDataDump('18010de425155679') );

        dd( GmailApi::tmpBuildTicketData('17fe39586b23bd7d') );
        dd( GmailApi::threadDataDump('17fe39586b23bd7d') );
        // dump( GmailApi::threadDataDump('17fe39586b23bd7d') );

        $imageData = GmailApi::tmpGetAttachmentData('180013f4839c428e', 'ANGjdJ9z_AadrBCkhtphHafXiKLrlHp3aZaNSDoyoxzwnWErdYSLu2pRq8C-Zzgqo9leWlA0JZIGEeXxR_8Q2I0koAqBcUG3H4RkVFt7G5EIsa2XEBDj9CFtmy6eYTxVh3EByA-JhFpiGc7jxbDH3-M9Ndc6TTehkU14D50tdGYIE6C6jmMpuOfZ9qx-PwmWpzvKKYMiJJdRojB0RJdgG0L3GVAfLq_nlctHoRjLFOq4UrYQFsCsH55bQeH8JPWrLlmlhJhxKI3vIu5J4I3vnjwEDp4dMLQM_0V2I2W7bd0VsW4wEFQhHuukVfeXCLt6d1g2nF2VhhhIBphRReIhJ3rjFgZ42RaRFdrBDVM7LP0zwrxMrLkVj1ulMH68J8MR2jQJXVxbhYP9LwrWRKjc');
        $image     = '<img src="data:image/jpeg;base64,'.$imageData.'" alt="Screen Shot 2022-01-18 at 4.46.17 pm.png" width="440"/>';
        echo $image;

        $imageData = GmailApi::tmpGetAttachmentData('180013f4839c428e', 'ANGjdJ_IcodO8QLaVkJMa8k2Q_nMuba1UEc88k9F8wN4jOeb4HNT0O9x059y8yE7nMm7k_jhKwL5ADm8fGpt5jpRJuzDzWKUXeBMXWaVCJXZ3xdAEWpB0mLJn23OpO-YdGBFFhw9gZWyiKUYoZ1XSFlrXYzkNa80tY-0clkzOK3zeYXjpb1Iizlc2Y3-2NtfnUKs1D_2w7MDHLbNg7636MGX_Ya2D1PFK3aQw8gvID_e9pUDri2iO5Nf6DwdzTqfhG82wz17lKF3Ft27mF_awBkxe3CZdYrWr4YwwJuKu43VcW4udCOoNS7dT0ALAmt1OTJb8G2wS8Xs-lGo97f3s-hIQKEpLy-1c6tu532dlVdMp_ooYk2vqHysDkiCPDXM1UHFhkItHCfuy4kGMoXi');
        $image     = '<img src="data:image/jpeg;base64,'.$imageData.'" alt="Screen Shot 2022-01-18 at 4.46.17 pm.png" width="440"/>';
        echo $image;
        dd('-end-');


        dd(User::faeAgents()->get());
        $now = \Carbon\Carbon::now()->format('Y-m-d');
        $date = '2022-04-01';
        dump($now);dump($date);
        dd($now == $date);
        // \App\EbayAPI::syncEbayMessages();

        dd();
        dump(Session::token());
        dump($request->header('X-CSRF-Token'));
        dd();
        // dd( User::find(20)->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->count() );

        $users = User::faeAgentsExcludeAnne()->withCount('tickets')->orderBy('tickets_count', 'asc')->get()->toArray();
        $tickets = Ticket::whereIn('id', [27318,27316,27314,27313,27312])->get()->toArray();
        dump($users);
        dump($tickets);

        $allUsers = $users;
        $user     = current($users);

        foreach ( $tickets as $ticket )
        {

            /*
             *  before assigning ticket, check if user reached limit
             * if (user limit reached)
             *  fetch all users that has not yet reached the limit
             */
            // dump('check: '.$user['name']);
            if ( $this->is_user_ticket_limit_reached( $user['id'] )  )
            {
                /* what if two consecutive agents has reached limit?
                 *  - what if all not available?
                 *      - then have checked if everyone reached limit then set unassigned if no one is available else
                  *       use the current user from the $usersLimitNotReached that can still cater tickets
                 */

                $usersLimitNotReached = $this->get_users_limit_not_reached();
                if ( empty( $usersLimitNotReached ) )
                {
                    //set ticket unassigned since no agent is available / all agents ticket limit reached
                    // $_ticket         = Ticket::find($ticket['id']);
                    // $_ticket->status = Ticket::STATUS_UNASSIGNED;
                    // $_ticket->save();
                    dump(1);
                }
                else
                {
                    $allUsers = $users = $usersLimitNotReached;
                    $user = current($users);
                    // $this->assignTicket($ticket, $user);
                    dump(2 .': '.$user['name']);
                    // maybe add also $user = next($users); here
                }

            }
            else
            {
                // $this->assignTicket($ticket, $user);
                dump(21 .': '.$user['name']);
                $user = next($users);
            }

            if(!$user)
            {
                $users = $allUsers;
                $user  = current($users);
                dump(3);
            }
            
        }


        dd(0);
        // $randString = random_bytes(5);
        // $randString = bin2hex($randString);
        // $fileName   = '00201SI5496-'.$randString;  //documentName.extension
        // dd($fileName);
        /*$users = User::faeAgentsExcludeAnne()->withCount('tickets')->orderBy('tickets_count', 'asc')->get()->toArray();
        dump($users);

        $user     = current($users);
        dump($user['id']);

        dd(1);*/

        dd( GmailApi::threadDataDump('17fe39586b23bd7d') );


        $now = \Carbon\Carbon::now()->format('Y-m-d');
        $_sunday = '2022-03-20';
        $_monday = '2022-03-21';
        $users = User::where('id', 8)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
        if ( $now == $_sunday || $now == $_monday )
        {
            $ticketsKarina = User::find(18)->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->whereDate('created_at', '=', $now)->get();

            if ( $ticketsKarina->count() < 10 )
            {
                $users = User::where('id', 18)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
            }
        }

        dd($users);

        // $result = Mail::to('rodney@frankiesautoelectrics.com.au')->send(new SendEmailWithNoReply());
        // $result = Mail::to('theodore@frankiesautoelectrics.com.au')->send(new SendEmailWithNoReply());

        dd();

        $message = $this->messageEncode('PGRpdiBkaXI9Imx0ciI-SXQgc2VlbXMgdGhpcyB0aGUgc3RhbmRhcmQgc2VydmljZSBmb3IgeW91ciBjb21wYW55LMKgPGEgaHJlZj0iaHR0cHM6Ly93d3cucHJvZHVjdHJldmlldy5jb20uYXUvbGlzdGluZ3MvZnJhbmtpZXMtYXV0by1lbGVjdHJpY3MtY2FyLWF1ZGlvLW9ubGluZS1zdG9yZSI-aHR0cHM6Ly93d3cucHJvZHVjdHJldmlldy5jb20uYXUvbGlzdGluZ3MvZnJhbmtpZXMtYXV0by1lbGVjdHJpY3MtY2FyLWF1ZGlvLW9ubGluZS1zdG9yZTwvYT48ZGl2Pjxicj48L2Rpdj48ZGl2PkkgZXhwZWN0IGEgcmVmdW5kLCBhbmQgaWYgeW91wqAgZG9uJiMzOTt0IEkgd2lsbCBnbyB0byBRQ0FUIGFuZCBhcyB0aGUgb3duZXIgb2YgYSBzb2NpYWwgbWVkaWEgYnVzaW5lc3MgSSB3aWxsIGVuc3VyZSB0aGUgd2lkZXN0IGF1ZGllbmNlIGFyZSBhd2FyZSBvZiB5b3VyIHZlcnkgcG9vciBzZXJ2aWNlIGFuZCBuZXV0cmFsaXNlIHRoZSBtYXNzaXZlIGFtb3VudHMgeW91IHNlZW0gdG8gc3BlbmQgb24gR29vZ2xlIEFkcy48L2Rpdj48ZGl2Pjxicj48L2Rpdj48ZGl2Pjxicj48L2Rpdj48ZGl2PsKgwqA8L2Rpdj48L2Rpdj48YnI-PGRpdiBjbGFzcz0iZ21haWxfcXVvdGUiPjxkaXYgZGlyPSJsdHIiIGNsYXNzPSJnbWFpbF9hdHRyIj5PbiBUdWUsIE1hciAxLCAyMDIyIGF0IDQ6MDAgUE0gRnJhbmtpZXMgQXV0byBFbGVjdHJpY3MgJmx0OzxhIGhyZWY9Im1haWx0bzpzdXBwb3J0QGZyYW5raWVzYXV0b2VsZWN0cmljcy5jb20uYXUiPnN1cHBvcnRAZnJhbmtpZXNhdXRvZWxlY3RyaWNzLmNvbS5hdTwvYT4mZ3Q7IHdyb3RlOjxicj48L2Rpdj48YmxvY2txdW90ZSBjbGFzcz0iZ21haWxfcXVvdGUiIHN0eWxlPSJtYXJnaW46MHB4IDBweCAwcHggMC44ZXg7Ym9yZGVyLWxlZnQ6MXB4IHNvbGlkIHJnYigyMDQsMjA0LDIwNCk7cGFkZGluZy1sZWZ0OjFleCI-PHU-PC91Pg0KDQogIA0KICANCg0KPGRpdiBsYW5nPSJlbi11cyIgc3R5bGU9Im1hcmdpbjowcHg7cGFkZGluZzowcHg7d2lkdGg6MTAwJSI-DQogIDxkaXYgc3R5bGU9InBhZGRpbmc6MTBweDtsaW5lLWhlaWdodDoxOHB4O2ZvbnQtZmFtaWx5OiZxdW90O0x1Y2lkYSBHcmFuZGUmcXVvdDssVmVyZGFuYSxBcmlhbCxzYW5zLXNlcmlmO2ZvbnQtc2l6ZToxMnB4O2NvbG9yOnJnYig2OCw2OCw2OCkiPg0KICAgIDxkaXYgc3R5bGU9ImNvbG9yOnJnYigxODEsMTgxLDE4MSkiPiMjLSBQbGVhc2UgdHlwZSB5b3VyIHJlcGx5IGFib3ZlIHRoaXMgbGluZSAtIyM8L2Rpdj4NCiAgICA8cCBkaXI9Imx0ciI-WW91ciByZXF1ZXN0ICgxOTAyNDQpIGhhcyBiZWVuIHJlY2VpdmVkIGFuZCBpcyBiZWluZyByZXZpZXdlZCBieSBvdXIgc3VwcG9ydCBzdGFmZi48L3A-PHAgZGlyPSJsdHIiPlRvIGFkZCBhZGRpdGlvbmFsIGNvbW1lbnRzLCByZXBseSB0byB0aGlzIGVtYWlsLjwvcD4NCiAgPC9kaXY-DQogIDxkaXYgc3R5bGU9InBhZGRpbmc6MTBweDtsaW5lLWhlaWdodDoxOHB4O2ZvbnQtZmFtaWx5OiZxdW90O0x1Y2lkYSBHcmFuZGUmcXVvdDssVmVyZGFuYSxBcmlhbCxzYW5zLXNlcmlmO2ZvbnQtc2l6ZToxMnB4O2NvbG9yOnJnYigxNzAsMTcwLDE3MCk7bWFyZ2luOjEwcHggMHB4IDE0cHg7Ym9yZGVyLXRvcDoxcHggc29saWQgcmdiKDIzOCwyMzgsMjM4KSI-DQogICAgVGhpcyBlbWFpbCBpcyBhIHNlcnZpY2UgZnJvbSBGcmFua2llcyBBdXRvIEVsZWN0cmljcy4gRGVsaXZlcmVkIGJ5IDxhIGhyZWY9Imh0dHBzOi8vd3d3LnplbmRlc2suY29tL3N1cHBvcnQvP3V0bV9jYW1wYWlnbj10ZXh0JmFtcDt1dG1fY29udGVudD1GcmFua2llcytBdXRvK0VsZWN0cmljcyZhbXA7dXRtX21lZGl1bT1wb3dlcmVkYnl6ZW5kZXNrJmFtcDt1dG1fc291cmNlPWVtYWlsLW5vdGlmaWNhdGlvbiIgc3R5bGU9ImNvbG9yOmJsYWNrIiB0YXJnZXQ9Il9ibGFuayI-WmVuZGVzazwvYT4NCiAgPC9kaXY-DQo8c3BhbiBzdHlsZT0iY29sb3I6cmdiKDI1NSwyNTUsMjU1KSIgYXJpYS1oaWRkZW49InRydWUiPls1TFY5NjAtODdZTF08L3NwYW4-DQoNCjwvZGl2Pg0KPC9ibG9ja3F1b3RlPjwvZGl2Pg0K', NULL);
        echo $message;
        dd( $message );

        dd( GmailApi::threadDataDump('17f6c7bb7294f7b5') );

        dd(session()->all());
        // $reminders = Reminder::authUserReminders()->get();
        // dump($reminders->count());
        // dd($reminders);

        $reminders = Reminder::onlyTrashed()->get();

        dump($reminders);

        foreach ($reminders as $reminder)
        {
            $interval               = $reminder->interval->first();
            $reminderIntervalRecord = \App\ReminderIntervalRecord::withTrashed()->where('reminder_id', $reminder->id)->first();
            dump($reminderIntervalRecord);
            dump($interval);

            $condition = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';
            dump($condition);
        }

        dd();

        // $this->getNumberOfTickets();

        // $this->countAgentsTickets();

        $d1 = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        $d2 = \Carbon\Carbon::now()->subYear()->toDateTimeString();
        dump($d1);
        dump($d2);
        dd();


        //loop through dates
        /*$ticket = Ticket::excludeEbay()
                          ->whereBetween('created_at', [ $date->format('Y-m-d') . ' 08:00:00', $date->format('Y-m-d') . ' 12:00:00'])
                          ->get();
        dump($ticket);*/



        // dd( GmailApi::threadDataDump('17ee14446f8c28dd') );
        // dd( GmailApi::tmpCreateTickets() );

        /*$str = 'The1 quick brown fox jumps over';

        $str = str_replace( array('1'), array('brown'), '2 white', $str);

        echo $str;dd();*/

        // $data = 'SGkgRnJhbmtpZeKAmXMNCg0KTXkgZGV0YWlscyBhcmUNCg0KTkFNRSAgIERhdmlkIFMgS25veA0KQlNCICAgICAgIDkyMzEwMA0KQUNDICAgICAgMzAzNDUyNjkyDQoNClJlZ2FyZHMgRGF2aWQgS25veA0KDQoNCj4gT24gMzEgRGVjIDIwMjEsIGF0IDExOjI2IGFtLCBDdXN0b21lciBTZXJ2aWNlIDxzdXBwb3J0QGZyYW5raWVzYXV0b2VsZWN0cmljcy5jb20uYXU-IHdyb3RlOg0KPiANCj4g77u_DQo-IEhpIERhdmlkLA0KPiAgDQo-IEdvb2QgZGF5IQ0KPiAgDQo-IFdlIGhhdmUgcmVjZWl2ZWQgYSByZXF1ZXN0IGZyb20gb3VyIEZpbmFuY2UgVGVhbSBhcyB0aGV5IGFyZSBwcm9jZXNzaW5nIHlvdXIgcmVmdW5kIHJlcXVlc3QuDQo-ICANCj4gTWF5IHdlIGhhdmUgeW91ciBiYW5rIGluZm9ybWF0aW9uIHRvIHByb2NlZWQ_IFBsZWFzZSBmaWxsIGluIHRoZSBiYW5rIGluZm9ybWF0aW9uIHRoYXQgd2UgbmVlZDoNCj4gIA0KPiBCU0I6DQo-IEFDQ09VTlQ6DQo-IE5BTUU6DQo-ICANCj4gV2UgaG9wZSB3ZSBhcmUgYWJsZSB0byBnZXQgdGhlIGRldGFpbHMgc28gd2UgY2FuIHByb2Nlc3MgdGhlIHJlZnVuZCBhcyBzb29uIGFzIHBvc3NpYmxlLg0KPiAgDQo-IEJlc3QgUmVnYXJkcywNCj4gIA0KPiBGcmFua2llJ3MgQXV0byBFbGVjdHJpY3MgDQo=';
        // $data = base64_decode($data);
        // $data = htmlspecialchars($data);

        dd( GmailApi::threadDataDump('17f1b707fcca58fe') );

        // echo base64_decode('PCFET0NUWVBFIGh0bWwgUFVCTElDICItLy9XM0MvL0RURCBIVE1MIDQuMCBUcmFuc2l0aW9uYWwvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvVFIvUkVDLWh0bWw0MC9sb29zZS5kdGQiPgo8aHRtbD48Ym9keT48cD5IaSB0aGVyZSANCkkmcnNxdW87bSBpbnRlcmVzdGVkIGluIGEgYW1wbGlmaWVyIGZyb20gYXVkaW8gY29udHJvbCBidXQgbm90IHRvIHN1cmUgd2hpY2ggb25lPyBJcyBpdCBwb3NzaWJsZSB0byBnZXQgYSBwaG9uZSBjYWxsIGFib3V0IHRoZXNlIHByb2R1Y3RzIGFuZCB0byBnaXZlIHlvdSBtb3JlIGRldGFpbHMgYWJvdXQgdGhlIHNldHVwIEkgaGF2ZSBhbmQgd2hhdCBJIGFtIHRyeWluZyB0byBhY2hpZXZlLiBUaGFuayB5b3UgDQoNCg0KUmljayBNdXJyYXkgPC9wPjwvYm9keT48L2h0bWw+Cg==');

        $messageData = 'PGRpdiBkaXI9Imx0ciI-SGkgS2VsbGksPGRpdj48YnI-PC9kaXY-PGRpdj5IZXJlIGFyZSBvdXIgcmV0dXJuIGRldGFpbHMuPC9kaXY-PGRpdj48YnI-PC9kaXY-PGRpdj5Zb3UgY2FuIGxlYXJuIG1vcmUgYWJvdXQgb3VyIFJldHVybnMgYW5kIFJlZnVuZHMgUG9saWN5IGJ5IGNsaWNraW5nIHRoZSBsaW5rIGJlbG93Ojxicj48YSBocmVmPSJodHRwczovL2ZyYW5raWVzYXV0b2VsZWN0cmljcy5jb20uYXUvcmVmdW5kLXNoaXBwaW5nLXBvbGljeS8iPmh0dHBzOi8vZnJhbmtpZXNhdXRvZWxlY3RyaWNzLmNvbS5hdS9yZWZ1bmQtc2hpcHBpbmctcG9saWN5LzwvYT48YnI-PGRpdj48YnI-PC9kaXY-PGRpdj5Zb3UgbWF5IG9ubHkgcmV0dXJuIG5ldywgdW5vcGVuZWQgaXRlbXMgd2l0aGluIDMwIGRheXMgb2YgZGVsaXZlcnkgZm9yIGFuIGV4Y2hhbmdlIG9yIGEgcmVmdW5kIGxlc3MgYSAyMCUgcmUtc3RvY2tpbmcgZmVlIGFuZCBhbnkgc2hpcHBpbmcgY29zdHMgd2hpY2ggYXJlIHVuYWJsZSB0byBiZSByZXRyaWV2ZWQuPGJyPjwvZGl2PjxkaXY-wqA8YnI-SWYgeW91IGFncmVlIHdpdGggb3VyIHJldHVybnMgY29uZGl0aW9ucywga2luZGx5IHNlbmQgdGhlIGl0ZW0gdG8gdXMgdXNpbmcgdGhlIGNoZWFwZXN0IHRyYWNrYWJsZSBtZXRob2QuIDxicj7CoDxicj5LaW5kbHkgc2VjdXJlIHRoZSBpdGVtcyBpbiBhIGJhZy9ib3ggd2hlcmUgdGhlIHByb2R1Y3QgYm94IHdvbiYjMzk7dCBnZXQgc2NyYXRjaGVkL2RhbWFnZWQgYW5kIGluY2x1ZGUgdGhlIGZvbGxvd2luZyBpbmZvcm1hdGlvbiBiZWxvdyBvbiBhIHBpZWNlIG9mIHBhcGVyLiA8YnI-wqA8YnI-TmFtZTxicj5PcmRlciBudW1iZXI8YnI-UmVhc29uIGZvciByZXR1cm5zOiAodGhlIHdyb25nIGl0ZW0gb3JkZXJlZCk8YnI-UmVxdWVzdDogKGV4Y2hhbmdlIHRvIHRoZSBEMjUwU0UpPGJyPsKgPGJyPlBsZWFzZSBkbyBub3Qgd3JpdGUvc3RpY2sgYW55dGhpbmcgb24gdGhlIHByb2R1Y3QvbWFudWZhY3R1cmVyJiMzOTtzIGJveC4gVGhlIGl0ZW1zIHNob3VsZCBiZSByZXR1cm5lZCBpbiBicmFuZCBuZXcgY29uZGl0aW9uIHdpdGggZXZlcnl0aGluZyB0aGV5IGNhbWUgd2l0aC48YnI-wqA8YnI-SGVyZSYjMzk7cyBvdXIgcmV0dXJuIGFkZHJlc3M6PGJyPkZyYW5raWVzIEF1dG8gRWxlY3RyaWNzPGJyPjY5IFByaW5jZXMgSGlnaHdheTxicj5BbGJpb24gUGFyayBSYWlsIE5TVyAyNTI3PGJyPsKgPGJyPk9uY2UgeW91IHNoaXBwZWQgdGhlIGl0ZW1zIGJhY2sgdG8gdXMsIHNlbmQgdXMgYSBzY3JlZW5zaG90IG9yIHBpY3R1cmUgb2YgdGhlIHJldHVybiBzaGlwcGluZyBsYWJlbCB3aXRoIHRoZSB0cmFja2luZyBudW1iZXIuPGJyPsKgPGJyPk5PVEU6IElmIHRoZSBpdGVtL3MgcmVjZWl2ZWQgaXMgbm90IGluIGJyYW5kIG5ldyBjb25kaXRpb24sIGl0IHdpbGwgYmUgcmV0dXJuZWQgYmFjayB0byB5b3UuPGJyPsKgPGJyPlNob3VsZCB5b3UgcmVxdWlyZSBmdXJ0aGVyIGFzc2lzdGFuY2Ugb3IgaWYgeW91IGhhdmUgYW55IHF1ZXN0aW9ucyBvciBjb25jZXJucywgcGxlYXNlIGRvbiYjMzk7dCBoZXNpdGF0ZSB0byByZWFjaCBvdXQgdG8gdXMgYXQgYW55IHRpbWUuPGJyPsKgPGJyPlN0YXkgc2FmZSwgaGF2ZSBhIGdvb2Qgd2Vla2VuZC48YnI-wqA8YnI-wqA8YnI-S2luZCByZWdhcmRzLDxicj5GcmFua2llcyBBdXRvIEVsZWN0cmljcyBTdXBwb3J0PGJyPjwvZGl2PjwvZGl2PjwvZGl2Pg0K';

        $messageData = base64_encode('<support@frankiesautoelectrics.com.au>');
        // echo base64_decode($messageData);
        // dump( GmailApi::threadDataDump('17e6b622a8642ffa') );

        $message = $this->messageEncode($messageData, NULL);
        // echo $message;
        // $message = str_replace( array('&Acirc;', '&acirc;', '&#128;', '&#153;'), '', $message);
        // $message = str_replace( 'Â', '', $message);

        // $message = html_entity_decode($message, ENT_QUOTES, "UTF-8");
        echo $message;
        dd( $message );

        // <img src="cid:998088D2-E42D-45FC-8F79-400B617E33D1-L0-001"><img src="cid:ADC049C9-10D5-4540-A7AE-CE2CFAE1BE53-L0-001">

        $imageData = GmailApi::tmpGetAttachmentData('17e6bd4c648014d8', 'ANGjdJ9sKqC3kOoms-Gz4xYjDeWiMGRmAyhMyEfibdCYL2qs2AuPUWs84WcHUH3ZzlDfKmpobxpj3xzs2N2BYvetO_Au0sn7H_5PAlfMJocf7GrSutccjxCmviiDmlxaYKGvwWRRgxsjc7BSeL2S8zqN1BiMebMuhb9EiDh6q8ujVT13oGuPr4FW15tNyiDmOuCln5feUkFT7_zKqKQ2XYM1nQycu703ZmPFRkTugZOx7sLQq4dMW33bTfPkm8gTOUdXOHiPg2Y3A0GSwJIoeSKR4SrJrfmYzsz_xexFrQw95tsj53dveM3sEYXupInVYpeolbgy8dizo0ImG-n35WOXupIciMGGjweAqnk6WpTt4LzLw2F2VkmHTk8RrdnHTruKC2p6JJ5gynhktche');
        $image     = '<img src="data:image/jpeg;base64,'.$imageData.'" alt="Screen Shot 2022-01-18 at 4.46.17 pm.png" width="440"/>';
        echo $image;
        dump($image);

        // $imageData = GmailApi::tmpGetAttachmentData('17dbb95843200d2a', 'ANGjdJ8R_VC9Khqf-hg3Z7ERYx81KAawCOL9cRdYW4ICJiq0VgLrO25BZluPXL-2YgChnWh4tBOCJ8Wsb0y64V0gvEm8eQVdaC00X43VASbz0BLcKbqLyxQgZ5foSHui_BGsomOIT2l63FSZvhhrq2ui5JNpVsM763-bq6jMrJ-_Ud5uKCRdKi5zfC3zjzeiHBYA9lutUFBes-rWajsA3mFHMsAXOLpZSBl1J6BQ0xuUgHuGemJm4FrGo4L8QSfgKETPwelUwRj9-fu6GX3AiVXzT5kgIanqHUptK4MRbzHq8AGV0h1o7-QpWCB-csd3g8oxmTnkG0CwYMVjMcx0dILhTjLbmWlE92XarjL9OIoPEiZRRwpCMD4RTfqJzpmPpePysnKuy-MDn-hwyeC9');
        // $image     = '<img src="data:image/jpeg;base64,'.$imageData.'" alt="image1" width="440"/>';
        // echo $image;
        // dump($image);
        

        dd(9);
        // dd( GmailApi::threadDataDump('17e0de1061d69f7f') );

        $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where('origin_id', 8)->count();

        dd($tickets);

        // dd( GmailApi::tmpMessageEncode( 'PGh0bWw-PGhlYWQ-PG1ldGEgaHR0cC1lcXVpdj0iY29udGVudC10eXBlIiBjb250ZW50PSJ0ZXh0L2h0bWw7IGNoYXJzZXQ9dXRmLTgiPjwvaGVhZD48Ym9keSBkaXI9ImF1dG8iPlRoYW5rIHlvdSE8ZGl2PknigJl2ZSBqdXN0IHNlbnQgaXQsIHNlZSB0cmFja2luZyBudW1iZXIgYmVsb3c8L2Rpdj48ZGl2PjxpbWcgc3JjPSJjaWQ6M0EyREEyMzItRTUwQS00NzE0LUIwMDctMzRCRkVBQTIzMjk4LUwwLTAwMSI-PGJyPjxicj48ZGl2IGRpcj0ibHRyIj5FbnZpYWRvIGRlc2RlIG1pIGlQaG9uZTwvZGl2PjxkaXYgZGlyPSJsdHIiPjxicj48YmxvY2txdW90ZSB0eXBlPSJjaXRlIj5FbCAyNCBub3YgMjAyMSwgYSBsYXMgMTI6MDEsIEN1c3RvbWVyIFNlcnZpY2UgJmx0O3N1cHBvcnRAZnJhbmtpZXNhdXRvZWxlY3RyaWNzLmNvbS5hdSZndDsgZXNjcmliacOzOjxicj48YnI-PC9ibG9ja3F1b3RlPjwvZGl2PjxibG9ja3F1b3RlIHR5cGU9ImNpdGUiPjxkaXYgZGlyPSJsdHIiPu-7vzxkaXY-SGksPC9kaXY-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPGRpdj5Hb29kIGRheSE8L2Rpdj4NCjxkaXY-Jm5ic3A7PC9kaXY-DQo8ZGl2PjxzcGFuPldlIGFyZSBzb3JyeSB0aGF0IHlvdSBvcmRlcmVkIHRoZSB3cm9uZyBpdGVtLjwvc3Bhbj48L2Rpdj4NCjxkaXY-Jm5ic3A7PC9kaXY-DQo8ZGl2PkZPUiBSRVRVUk5TL1JFUExBQ0VNRU5UL1JFRlVORDxicj5Zb3UgbWF5IG9ubHkgcmV0dXJuIG5ldywgdW5vcGVuZWQgaXRlbXMgd2l0aGluIDMwIGRheXMgb2YgZGVsaXZlcnkgZm9yIGEgcmVmdW5kIGxlc3MgYSAyMCUgcmVzdG9ja2luZyBmZWUgYW5kIGFueSBzaGlwcGluZyBjb3N0cyB3aGljaCBhcmUgdW5hYmxlIHRvIGJlIHJldHJpZXZlZC48L2Rpdj4NCjxkaXY-Jm5ic3A7PC9kaXY-DQo8ZGl2PllvdSBjYW4gbGVhcm4gbW9yZSBhYm91dCBvdXIgUmV0dXJucyBhbmQgUmVmdW5kcyBQb2xpY3kgYnkgY2xpY2tpbmcgdGhlIGxpbmsgYmVsb3c6PGJyPjxhIGhyZWY9IjNEJnF1b3Q7aHR0cHM6Ly9mcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1L3JlZnVuZC1zaGlwcGluZy1wb2xpY3kvJnF1b3Q7IiB0YXJnZXQ9Il9ibGFuayI-aHR0cHM6Ly9mcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1L3JlZnVuZC1zaGlwcGluZy1wb2xpY3kvPC9hPjwvZGl2Pg0KPGRpdj4mbmJzcDs8L2Rpdj4NCjxkaXY-Jm5ic3A7PC9kaXY-DQo8ZGl2PkJlbG93IGlzIG91ciBndWlkZWxpbmVzIGZvciB0aGUgcmV0dXJuOjxicj7igKIgVGhlIHVuaXQgbXVzdCBiZSBpbiBicmFuZCBuZXcgY29uZGl0aW9uLCB1bnVzZWQgYW5kIGluIHRoZSBleGFjdCBjb25kaXRpb24gdGhhdCB3ZSBzaGlwcGVkIGl0IG91dCB0byB5b3UsIHN0aWxsIGZhY3Rvcnkgc2VhbGVkIGFuZCB1bm9wZW5lZCwgdW51c2VkICZhbXA7IHVuc2NyYXRjaGVkIGFuZCBQTEVBU0UgRE8gTk9UIFdSSVRFIE9SIFNUSUNLIEFOWVRISU5HIE9OIFRIRSBQUk9EVUNUIEJPWCAoZm9yIHJldHVybi9yZXBsYWNlbWVudC9yZWZ1bmQpPGJyPuKAoiBBbGwgcGFydHMsIHBsYXN0aWNzIGNvdmVycywgZm9hbXMsIHNjcmV3cyBtYW51YWxzIGV0Yy4gbXVzdCBiZSBpbmNsdWRlZCB3aXRoIHRoZSBwcm9kdWN0PC9kaXY-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPGRpdj5QbGVhc2Uga25vdyB0aGF0IHRoZSByZXR1cm4gbGFiZWxzIHNob3VsZCBub3QgYmUgcGxhY2VkL3N0aWNrIGluIHRoZSBib3ggaXRzZWxmIGFzIGl0IHdpbGwgYXBwZWFyIGFzIHRoZSBpdGVtIGlzIG5vIGxvbmdlciByZS1zZWxsYWJsZSBhbmQgd2lsbCBiZSBjb25zaWRlcmVkIGFzIHVzZWQvdGFtcGVyZWQgaXRlbSBhbmQgd2UgY2Fubm90IGFkZCBpdCBiYWNrIHRvIG91ciBzZWxsYWJsZSBpbnZlbnRvcnkuPC9kaXY-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPGRpdj5LaW5kbHkgc2VjdXJlIHRoZSBpdGVtIGluIGEgYmFnL2JveCB3aGVyZSB0aGUgcHJvZHVjdCBib3ggd29uJ3QgZ2V0IHNjcmF0Y2hlZC9kYW1hZ2VkIGFuZCBpbmNsdWRlIHRoZSBmb2xsb3dpbmcgaW5mb3JtYXRpb24gYmVsb3cgaW5zaWRlIG9uIGEgcGllY2Ugb2YgcGFwZXIuPC9kaXY-DQo8ZGl2PuKAoiBOYW1lPGJyPuKAoiBPcmRlciBudW1iZXI8YnI-4oCiIFJlYXNvbiBmb3IgcmV0dXJuczogKFdST05HIElURU0gT1JERVJFRCk8L2Rpdj4NCjxkaXY-Jm5ic3A7PC9kaXY-DQo8ZGl2Pg0KPGRpdj5IZXJlJ3Mgb3VyIHJldHVybiBhZGRyZXNzOjwvZGl2Pg0KPGRpdj5GcmFua2llcyBBdXRvIEVsZWN0cmljczwvZGl2Pg0KPGRpdj42OSBQcmluY2VzIEhpZ2h3YXk8L2Rpdj4NCjxkaXY-QWxiaW9uIFBhcmsgUmFpbCBOU1cgMjUyNzwvZGl2Pg0KPGRpdj4mbmJzcDs8L2Rpdj4NCjwvZGl2Pg0KPGRpdj5PbmNlIHlvdSBzaGlwcGVkIHRoZSBpdGVtIGJhY2sgdG8gdXMsIHNlbmQgdXMgYSBzY3JlZW5zaG90IG9yIHBob3RvIG9mIHRoZSByZXR1cm4gc2hpcHBpbmcgbGFiZWwgd2l0aCB0aGUgdHJhY2tpbmcgbnVtYmVyIHRvIG1vbml0b3IgaXRzIHdheSBiYWNrIHRvIHVzIHRvIHByb2NlZWQuPC9kaXY-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPGRpdj5XZSBuZWVkIHRvIGhhdmUgdGhlIGl0ZW0gaW5zcGVjdGVkIGFuZCB2YWxpZGF0ZWQgdGhhdCBpdCBpcyBicmFuZCBuZXcgYW5kIGNvbXBsZXRlIGFuZCBjYW4gYmUgcmVmdW5kZWQuIFBsZWFzZSBub3RlIHRoYXQgaWYgd2UgZmluZCB0aGUgaXRlbSBpcyBub3QgbmV3LCBjdXN0b21lcnMgd2lsbCBiZSBjaGFyZ2VkIGZvciB0aGUgZnJlaWdodCBjb3N0cyB0byBzZW5kIHRoZSBpdGVtIGJhY2suPC9kaXY-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPGRpdj5XZSBoYXZlIHJlcXVlc3RlZCBhIEZVTEwgcmVmdW5kIGFzIHlvdSBoYXZlIG9yZGVyZWQgdGhlIGNvcnJlY3QgcGFydCBmcm9tIHVzIGFnYWluIHdpdGggT1JERVIgIzEwMzIxNCBhbmQgd2UgYXBwcmVjaWF0ZSB5b3VyIGJ1c2luZXNzLjwvZGl2Pg0KPGRpdj4mbmJzcDs8L2Rpdj4NCjxkaXY-TGV0IHVzIGtub3cgaWYgeW91IGhhdmUgYW55IG1vcmUgcXVlc3Rpb25zLCBjb21tZW50cywgb3IgY29uY2VybnMuPC9kaXY-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPGRpdj5CZXN0IHJlZ2FyZHMsPC9kaXY-DQo8ZGl2PiZuYnNwOzwvZGl2Pg0KPGRpdj5GcmFua2llcyBBdXRvIEVsZWN0cmljczwvZGl2Pg0KPC9kaXY-PC9ibG9ja3F1b3RlPjwvZGl2PjwvYm9keT48L2h0bWw-', null ) );
        dd( GmailApi::tmpCreateTickets('17d4f760c152b448') );
        $threads = GmailApi::buildTicketData('17d4f760c152b448');

        dd($threads);

        dd();
        $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where('origin_id', TicketOrigin::ORIGIN_EBAY)->get()->toArray();
        dump(count($tickets));
        $statusIds = $ticketIds = '';
        foreach($tickets as $ticket)
        {
            $statusIds .= $ticket['status_id'].',';
            $ticketIds .= $ticket['id'].',';
        }

        dump($statusIds);
        dd($ticketIds);

        dd();

        dd(EbayAPI::syncEbayMessages());
        dd( GmailApi::threadDataDump('17b2d45fa9ec482f') );

        dd();
        $_message = "PCFET0NUWVBFIGh0bWw+CjwhLS1kZmM3NDFkNi1kZjYzLTQ4ODItYjVkOC1kZTRjOTIzNjFiZTBfdjIzLS0+PGh0bWw+CiAgPGhlYWQ+CiAgICA8bWV0YSBodHRwLWVxdWl2PSJDb250ZW50LVR5cGUiIGNvbnRlbnQ9InRleHQvaHRtbDsgY2hhcnNldD11dGYtOCI+PHN0eWxlIHR5cGU9InRleHQvY3NzIj4KCmJvZHkgewogICAgCiAgIAl3aWR0aDogMTAwJTsKCW1hcmdpbi1yaWdodDogYXV0bzsKCW1hcmdpbi1sZWZ0OiBhdXRvOwoJYmFja2dyb3VuZDogI2ZmZmZmZjsKfQoKKiB7Cgktd2Via2l0LXRleHQtc2l6ZS1hZGp1c3Q6IG5vbmU7Cn0KCi5FeHRlcm5hbENsYXNzICogewoJbGluZS1oZWlnaHQ6IDEwMCU7Cn0KdGFibGUgewoJLypib3JkZXItY29sbGFwc2U6IGNvbGxhcHNlOyovCgltc28tdGFibGUtbHNwYWNlOiAwcHQ7Cgltc28tdGFibGUtcnNwYWNlOiAwcHQ7Cn0KLmFwcGxlTGlua3NGb290ZXIgYSB7Cgljb2xvcjogIzc4NmU2NSFpbXBvcnRhbnQ7Cgl0ZXh0LWRlY29yYXRpb246IG5vbmU7Cn0KaHIuaHIxIHsKCWJvcmRlci10b3A6IDFweCBzb2xpZCAjZGRkZGRkOwp9CgppbWcgeyBkaXNwbGF5OiBibG9jayAhaW1wb3J0YW50OyBvdXRsaW5lOiBub25lICFpbXBvcnRhbnQ7IHRleHQtZGVjb3JhdGlvbjogbm9uZSAhaW1wb3J0YW50OyAtbXMtaW50ZXJwb2xhdGlvbi1tb2RlOiBiaWN1YmljICFpbXBvcnRhbnQ7IH0KCnRke3dvcmQtd3JhcDogYnJlYWstd29yZDt9Cgp0ZC5idXR0b25QYWRkaW5nIHsKICAgIHBhZGRpbmc6IDlweCAxN3B4Owp9CgoKIAogQG1lZGlhIG9ubHkgc2NyZWVuIGFuZCAobWluLXdpZHRoOiA2MDBweCkgewouZm9yY2Utd2lkdGggewoJd2lkdGg6IDYwMHB4IWltcG9ydGFudDsKfQouZm9yY2Utd2lkdGg1NzggewoJd2lkdGg6IDU3OHB4IWltcG9ydGFudDsKfQoubW9iV3JhcHBlclRlbXBsYXRlIHsKCXdpZHRoOiA2MDBweCFpbXBvcnRhbnQ7CgloZWlnaHQ6IGluaGVyaXQhaW1wb3J0YW50Cn0KLmJ1dHRvbldyYXBwZXIgewoJbWF4LXdpZHRoOiA2MDBweDsKCXdpZHRoOiBpbmhlcml0OwoJbWluLXdpZHRoOiAyNDBweDsKfQouYXV0b1NpemVCdXR0b24gewogICAgbWF4LXdpZHRoOiA2MDBweDsKCXdpZHRoOiBpbmhlcml0Owp9CipbY2xhc3M9c2NhbGUtaW1hZ2VdIHsKCW1heC13aWR0aDogNjAwcHghaW1wb3J0YW50OwoJaGVpZ2h0OiBhdXRvIWltcG9ydGFudDsKCW1heC1oZWlnaHQ6IG5vbmUhaW1wb3J0YW50OwoJZGlzcGxheTogYmxvY2shaW1wb3J0YW50OwoJZm9udC1zaXplOiAxMnB4IWltcG9ydGFudDsKfQoqW2NsYXNzPWl0ZW1fY2FzZV9vcGVuXXsKICAgIHBhZGRpbmctcmlnaHQ6MzBweCFpbXBvcnRhbnQ7CiAgICBtaW4td2lkdGg6OTBweCFpbXBvcnRhbnQ7Cn0KfQogQG1lZGlhIG9ubHkgc2NyZWVuIGFuZCAobWluLXdpZHRoIDogNDgwcHgpIGFuZCAobWF4LXdpZHRoIDogNjAwcHgpIHsKICAgICAKLyogUmVzcG9uc2l2ZSBEZXNrdG9wIENTUyAqLwp0YWJsZVtjbGFzcz1tb2JXcmFwcGVyVGVtcGxhdGVdIHsKCXdpZHRoOiAxMDAlIWltcG9ydGFudDsKCW1heC13aWR0aDogNjAwcHghaW1wb3J0YW50OwoJaGVpZ2h0OiBpbmhlcml0IWltcG9ydGFudDsKCW1heC1oZWlnaHQ6IG5vbmUhaW1wb3J0YW50Owp9CnRhYmxlW2NsYXNzPWJ1dHRvbldyYXBwZXJdIHsKICAgIAoJd2lkdGg6IDEwMCUhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiA2MDBweCFpbXBvcnRhbnQ7CgloZWlnaHQ6IGluaGVyaXQhaW1wb3J0YW50OwoJbWF4LWhlaWdodDogbm9uZSFpbXBvcnRhbnQ7Cn0KdGFibGVbY2xhc3M9YXV0b1NpemVCdXR0b25dIHsKICAgIAoJd2lkdGg6IDEwMCUhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiA2MDBweCFpbXBvcnRhbnQ7CgloZWlnaHQ6IGluaGVyaXQhaW1wb3J0YW50OwoJbWF4LWhlaWdodDogbm9uZSFpbXBvcnRhbnQ7Cn0KdGFibGVbY2xhc3M9bW9iV3JhcHBlcl0gewoJd2lkdGg6IDEwMCUhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiA2MDBweCFpbXBvcnRhbnQ7CgloZWlnaHQ6IGluaGVyaXQhaW1wb3J0YW50OwoJbWF4LWhlaWdodDogbm9uZSFpbXBvcnRhbnQ7Cn0KaW1nW2NsYXNzPXNjYWxlSW1hZ2VdIHsKCXdpZHRoOiAxMDAlIWltcG9ydGFudDsKCW1heC13aWR0aDogNjAwcHghaW1wb3J0YW50OwoJaGVpZ2h0OiBhdXRvIWltcG9ydGFudDsKfQppbWdbY2xhc3M9c2NhbGVNb2JJbWFnZV0gewoJd2lkdGg6IDEwMCUhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiAzMDBweCFpbXBvcnRhbnQ7CgloZWlnaHQ6IGF1dG8haW1wb3J0YW50OwoJbWF4LWhlaWdodDogbm9uZSFpbXBvcnRhbnQ7CglkaXNwbGF5OiBibG9jayFpbXBvcnRhbnQ7Cglmb250LXNpemU6IDEycHghaW1wb3J0YW50Owp9CnRhYmxlW2NsYXNzPXNob3dNb2JpbGVdIHsKCWRpc3BsYXk6IHRhYmxlIWltcG9ydGFudDsKCWhlaWdodDogaW5oZXJpdCFpbXBvcnRhbnQ7CgltYXgtaGVpZ2h0OiBub25lIWltcG9ydGFudDsKCW1heC13aWR0aDogMTAwJSFpbXBvcnRhbnQ7Cgl3aWR0aDogMTAwJSFpbXBvcnRhbnQ7CglsaW5lLWhlaWdodDogMTAwJSFpbXBvcnRhbnQ7CglwYWRkaW5nOiAwcHghaW1wb3J0YW50OwoJb3ZlcmZsb3c6IHZpc2libGUhaW1wb3J0YW50OwoJZm9udC1zaXplOiAxNHB4IWltcG9ydGFudDsKfQp0YWJsZVtjbGFzcz1oaWRlVHJlbmRdIHsKCWRpc3BsYXk6IG5vbmUhaW1wb3J0YW50OwoJZm9udC1zaXplOiAwcHghaW1wb3J0YW50OwoJbWF4LWhlaWdodDogMHB4IWltcG9ydGFudDsKCW1heC13aWR0aDogMHB4IWltcG9ydGFudDsKCWxpbmUtaGVpZ2h0OiAwcHghaW1wb3J0YW50OwoJcGFkZGluZzogMHB4IWltcG9ydGFudDsKCW92ZXJmbG93OiBoaWRkZW4haW1wb3J0YW50Owp9CipbY2xhc3M9bW92ZUxpbmtzTW9iaWxlXXsKICAgIGRpc3BsYXk6bm9uZTsKICAgIGNvbG9yOnJlZDsKICAgIG1pbi13aWR0aDozMDBweDsKICAgIHdpZHRoOmluaGVyaXQ7Cn0gCipbY2xhc3M9aXRlbV9jYXNlX29wZW5dewogICAgcGFkZGluZy1yaWdodDozMHB4IWltcG9ydGFudDsKICAgIG1pbi13aWR0aDo5MHB4IWltcG9ydGFudDsKfQoKfQogQG1lZGlhIHNjcmVlbiBhbmQgKG1heC13aWR0aCA6IDQ4MHB4KSB7Ci8qIEhlYWRlciAmIEZvb3RlciBUZW1wbGF0ZSBDU1MgKi8KCnRhYmxlW2NsYXNzPW1vYldyYXBwZXJUZW1wbGF0ZV0gewoJd2lkdGg6IDEwMCUhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiA0ODBweCFpbXBvcnRhbnQ7CgloZWlnaHQ6IGluaGVyaXQhaW1wb3J0YW50OwoJbWF4LWhlaWdodDogbm9uZSFpbXBvcnRhbnQ7Cn0KdGFibGVbY2xhc3M9YnV0dG9uV3JhcHBlcl0gewoJd2lkdGg6IDEwMCUhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiA0ODBweCFpbXBvcnRhbnQ7CgloZWlnaHQ6IGluaGVyaXQhaW1wb3J0YW50OwoJbWF4LWhlaWdodDogbm9uZSFpbXBvcnRhbnQ7Cn0KdGFibGVbY2xhc3M9YXV0b1NpemVCdXR0b25dIHsKCXdpZHRoOiAxMDAlIWltcG9ydGFudDsKCW1heC13aWR0aDogNjAwcHghaW1wb3J0YW50OwoJaGVpZ2h0OiBpbmhlcml0IWltcG9ydGFudDsKCW1heC1oZWlnaHQ6IG5vbmUhaW1wb3J0YW50Owp9CnRkW2NsYXNzPXJlc2l6ZUZvb3RlclNwYWNlcl0gewoJd2lkdGg6IDc3cHghaW1wb3J0YW50Owp9CnRkW2NsYXNzPW1vYmlsZVBhZGRpbmddIHsKCXBhZGRpbmc6IDAgMTJweCAwIDEycHghaW1wb3J0YW50Owp9Ci8qIFJlc3BvbnNpdmUgTW9iaWxlIENTUyAqLwp0YWJsZVtjbGFzcz1tb2JXcmFwcGVyXSB7Cgl3aWR0aDogMTAwJSFpbXBvcnRhbnQ7CgltYXgtd2lkdGg6IDQ4MHB4IWltcG9ydGFudDsKCWhlaWdodDogaW5oZXJpdCFpbXBvcnRhbnQ7CgltYXgtaGVpZ2h0OiBub25lIWltcG9ydGFudDsKfQppbWdbY2xhc3M9c2NhbGVJbWFnZV0gewoJZGlzcGxheTogbm9uZSFpbXBvcnRhbnQ7Cn0KaW1nW2NsYXNzPXNjYWxlTW9iSW1hZ2VdIHsKCXdpZHRoOiAxMDAlIWltcG9ydGFudDsKCW1heC13aWR0aDogMzAwcHghaW1wb3J0YW50OwoJaGVpZ2h0OiBhdXRvIWltcG9ydGFudDsKCW1heC1oZWlnaHQ6IG5vbmUhaW1wb3J0YW50OwoJZGlzcGxheTogYmxvY2shaW1wb3J0YW50OwoJZm9udC1zaXplOiAxMnB4IWltcG9ydGFudDsKfQp0YWJsZVtpZD13cmFwcGVyXSB7Cgl3aWR0aDogMTAwJSAhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiA0ODBweCAhaW1wb3J0YW50Owp9CnRhYmxlW2lkPW1vYkNvbnRlbnRdIHsKCXdpZHRoOiAyODJweCFpbXBvcnRhbnQ7Cn0KdGRbY2xhc3M9c3RhY2tdIHsKCWRpc3BsYXk6IGJsb2NrIWltcG9ydGFudDsKCXdpZHRoOiAxMDAlIWltcG9ydGFudDsKfQp0aFtjbGFzcz1zdGFja10gewoJZGlzcGxheTogYmxvY2shaW1wb3J0YW50OwoJd2lkdGg6IDEwMCUhaW1wb3J0YW50Owp9Ci8qIEhpZGUvU2hvdyBNb2JpbGUgQ1NTICovCnRhYmxlW2NsYXNzPWhpZGVdIHsKCWRpc3BsYXk6IG5vbmUhaW1wb3J0YW50OwoJZm9udC1zaXplOiAwcHghaW1wb3J0YW50OwoJbWF4LWhlaWdodDogMHB4IWltcG9ydGFudDsKCW1heC13aWR0aDogMHB4IWltcG9ydGFudDsKCWxpbmUtaGVpZ2h0OiAwcHghaW1wb3J0YW50OwoJcGFkZGluZzogMHB4IWltcG9ydGFudDsKCW92ZXJmbG93OiBoaWRkZW4haW1wb3J0YW50Owp9CmltZ1tjbGFzcz1oaWRlXSwgdGRbY2xhc3M9aGlkZV0sIHRoW2NsYXNzPWhpZGVdLCBkaXZbY2xhc3M9aGlkZV0gewoJZGlzcGxheTogbm9uZSFpbXBvcnRhbnQ7Cglmb250LXNpemU6IDBweCFpbXBvcnRhbnQ7CgltYXgtaGVpZ2h0OiAwcHghaW1wb3J0YW50OwoJbWF4LXdpZHRoOiAwcHghaW1wb3J0YW50OwoJbGluZS1oZWlnaHQ6IDBweCFpbXBvcnRhbnQ7CglwYWRkaW5nOiAwcHghaW1wb3J0YW50OwoJb3ZlcmZsb3c6IGhpZGRlbiFpbXBvcnRhbnQ7Cn0KdGRbY2xhc3M9c2hvd01vYmlsZV0gewoJZGlzcGxheTogYmxvY2shaW1wb3J0YW50OwoJaGVpZ2h0OiBhdXRvIWltcG9ydGFudDsKCW1heC1oZWlnaHQ6IGluaGVyaXQhaW1wb3J0YW50OwoJbWF4LXdpZHRoOiAxMDAlIWltcG9ydGFudDsKCXdpZHRoOiAxMDAlIWltcG9ydGFudDsKCWxpbmUtaGVpZ2h0OiAxMDAlIWltcG9ydGFudDsKCXBhZGRpbmc6IDBweCFpbXBvcnRhbnQ7CglvdmVyZmxvdzogdmlzaWJsZSFpbXBvcnRhbnQ7Cglmb250LXNpemU6IDE0cHghaW1wb3J0YW50Owp9CnRhYmxlW2NsYXNzPXNob3dNb2JpbGVdIHsKCWRpc3BsYXk6IHRhYmxlIWltcG9ydGFudDsKCWhlaWdodDogaW5oZXJpdCFpbXBvcnRhbnQ7CgltYXgtaGVpZ2h0OiBub25lIWltcG9ydGFudDsKCW1heC13aWR0aDogMTAwJSFpbXBvcnRhbnQ7Cgl3aWR0aDogMTAwJSFpbXBvcnRhbnQ7CglsaW5lLWhlaWdodDogMTAwJSFpbXBvcnRhbnQ7CglwYWRkaW5nOiAwcHghaW1wb3J0YW50OwoJb3ZlcmZsb3c6IHZpc2libGUhaW1wb3J0YW50OwoJZm9udC1zaXplOiAxNHB4IWltcG9ydGFudDsKfQp0YWJsZVtjbGFzcz1zaG93TW9iaWxlLWhlcm9dIHsKCWRpc3BsYXk6IHRhYmxlIWltcG9ydGFudDsKCWhlaWdodDogaW5oZXJpdCFpbXBvcnRhbnQ7CgltYXgtaGVpZ2h0OiBub25lIWltcG9ydGFudDsKCW1heC13aWR0aDogMzIwcHghaW1wb3J0YW50OwoJd2lkdGg6IDEwMCUhaW1wb3J0YW50OwoJbGluZS1oZWlnaHQ6IDEwMCUhaW1wb3J0YW50OwoJcGFkZGluZzogMHB4IWltcG9ydGFudDsKCW92ZXJmbG93OiB2aXNpYmxlIWltcG9ydGFudDsKCWZvbnQtc2l6ZTogMTRweCFpbXBvcnRhbnQ7Cn0KLyogQ3VzdG9tIE1vYmlsZSBDU1MgKi8KdGRbY2xhc3M9cGFkZGluZy1yZW1vdmVdIHsKCXBhZGRpbmc6IDAgMCAwIDAhaW1wb3J0YW50Owp9CnNwYW5bY2xhc3M9bW9iaWxlLWxpbmUtYnJlYWtdIHsKCWRpc3BsYXk6IGJsb2NrIWltcG9ydGFudDsKfQp0ZFtjbGFzcz1oZXJvLWlubmVyLXBhZGRpbmddIHsKCXBhZGRpbmc6IDBweCAyMHB4IDBweCAyMHB4IWltcG9ydGFudDsKfQppbWdbY2xhc3M9c2NhbGUtaGVyby1zaGFkb3ddIHsKCXdpZHRoOiAxMDAlIWltcG9ydGFudDsKCW1heC13aWR0aDogMTgwcHghaW1wb3J0YW50OwoJaGVpZ2h0OiBhdXRvIWltcG9ydGFudDsKCW1heC1oZWlnaHQ6IG5vbmUhaW1wb3J0YW50OwoJZGlzcGxheTogYmxvY2shaW1wb3J0YW50Owp9CnRhYmxlW2NsYXNzPWhpZGVUcmVuZF0gewoJZGlzcGxheTogbm9uZSFpbXBvcnRhbnQ7Cglmb250LXNpemU6IDBweCFpbXBvcnRhbnQ7CgltYXgtaGVpZ2h0OiAwcHghaW1wb3J0YW50OwoJbWF4LXdpZHRoOiAwcHghaW1wb3J0YW50OwoJbGluZS1oZWlnaHQ6IDBweCFpbXBvcnRhbnQ7CglwYWRkaW5nOiAwcHghaW1wb3J0YW50OwoJb3ZlcmZsb3c6IGhpZGRlbiFpbXBvcnRhbnQ7Cn0KKltjbGFzcz1zY2FsZS1pbWFnZS1tb2JpbGVdIHsKCW1heC13aWR0aDogMzIwcHghaW1wb3J0YW50OwoJaGVpZ2h0OiBhdXRvIWltcG9ydGFudDsKCW1heC1oZWlnaHQ6IG5vbmUhaW1wb3J0YW50OwoJZGlzcGxheTogYmxvY2shaW1wb3J0YW50OwoJZm9udC1zaXplOiAxMnB4IWltcG9ydGFudDsKfQoqW2NsYXNzPWZlZWRiYWNrTW9iaWxlTF17Cgl3aWR0aDo3MCUhaW1wb3J0YW50Owp9CipbY2xhc3M9ZmVlZGJhY2tNb2JpbGVSXXsKCXdpZHRoOjMwJSFpbXBvcnRhbnQ7Cn0KKltjbGFzcz1pdGVtX2Nhc2Vfb3Blbl17CiAgICBwYWRkaW5nLXJpZ2h0OjBweCFpbXBvcnRhbnQ7CiAgICBtaW4td2lkdGg6NjBweCFpbXBvcnRhbnQ7Cgp9Cgp9IAogICAgCkBtZWRpYSBvbmx5IHNjcmVlbiBhbmQgKG1pbi13aWR0aDogNDAwcHgpIHsKKltjbGFzcz1tb3ZlTGlua3NNb2JpbGVdewogICAgZGlzcGxheTpub25lOwogICAgbWluLXdpZHRoOjMwMHB4OwogICAgd2lkdGg6aW5oZXJpdDsKfSAKCiAgIAp9Cjwvc3R5bGU+CiAgPC9oZWFkPgogIDxib2R5IHlhaG9vPSJmaXgiPgogICAgPG1ldGEgbmFtZT0iZm9ybWF0LWRldGVjdGlvbiIgY29udGVudD0iYWRkcmVzcz1ubyI+CiAgICA8bWV0YSBuYW1lPSJmb3JtYXQtZGV0ZWN0aW9uIiBjb250ZW50PSJkYXRlPW5vIj4KICAgIDxtZXRhIG5hbWU9ImZvcm1hdC1kZXRlY3Rpb24iIGNvbnRlbnQ9InRlbGVwaG9uZT1ubywgZGF0ZT1ubywgYWRkcmVzcz1ubyI+CgoKPHRhYmxlIGlkPSJwYWRkaW5nQ29udGFpbmVyIiB3aWR0aD0iMTAwJSIgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJiYWNrZ3JvdW5kLWNvbG9yOiAjZmZmZmZmOyI+CiAgICA8dHI+CiAgICAgICAgPHRkIHdpZHRoPSIxMDAlIj48IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgIDx0YWJsZSB3aWR0aD0iNjAwIiBhbGlnbj0iY2VudGVyIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCI+CiAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgIDx0ZD4KICAgICAgICA8IVtlbmRpZl0tLT4KICAgICAgICAgICAgPHRhYmxlIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjI0IiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIGJvcmRlcj0iMCIgY2xhc3M9Im1vYldyYXBwZXJUZW1wbGF0ZSIgYWxpZ249ImNlbnRlciIgc3R5bGU9ImJhY2tncm91bmQtY29sb3I6ICNmZmZmZmY7IG1heC13aWR0aDogNjAwcHg7Ij4KICAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgICA8dGQgc3R5bGU9IiBwYWRkaW5nOiAwOyIgd2lkdGg9IjYwMCI+CiAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgPC90YWJsZT4KICAgICAgICAgICAgPCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KICAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgPC90YWJsZT4KICAgICAgICAKICAgICAgICA8IVtlbmRpZl0tLT4KICAgICAgICA8L3RkPgogICAgPC90cj4KPC90YWJsZT4gPHRhYmxlIGlkPSJwcmVoZWFkZXJDb250YWluZXIiIHdpZHRoPSIxMDAlIiBib3JkZXI9IjAiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgYWxpZ249ImNlbnRlciIgc3R5bGU9ImJhY2tncm91bmQtY29sb3I6ICNmZmZmZmY7Ij4KICAgIDx0cj4KICAgICAgICA8dGQgd2lkdGg9IjEwMCUiPjwhLS1baWYgKGd0ZSBtc28gOSl8KElFKV0+CiAgICAgICAgPHRhYmxlIHdpZHRoPSI2MDAiIGFsaWduPSJjZW50ZXIiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgYm9yZGVyPSIwIj4KICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAgPHRkPgogICAgICAgIDwhW2VuZGlmXS0tPgogICAgICAgICAgICA8dGFibGUgd2lkdGg9IjEwMCUiIGNsYXNzPSJtb2JXcmFwcGVyVGVtcGxhdGUiIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgYWxpZ249ImNlbnRlciIgc3R5bGU9Im1heC13aWR0aDogNjAwcHg7YmFja2dyb3VuZC1jb2xvcjogI2ZmZmZmZjsgYm9yZGVyOjFweCBzb2xpZCAjREREREREOyI+CiAgICAgICAgICAgIDx0Ym9keT4KICAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgICA8dGQgc3R5bGU9IiBwYWRkaW5nOiAwOyIgd2lkdGg9IjYwMCI+CiAgICAgICAgICAgICAgICAgICAgICAgIDxwIHN0eWxlPSJmb250LWZhbWlseTogIEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsIAo7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IGxpbmUtaGVpZ2h0OiBub3JtYWw7IGNvbG9yOiAjNzY3Njc2OyB0ZXh0LWFsaWduOiBsZWZ0OyBmb250LXNpemU6IDEzcHg7IG1hcmdpbjogMTJweCAwIDEycHggMTZweDsiIGFsaWduPSJsZWZ0IiBvcGFjaXR5PSIxMDAlIj5XZSdsbCByZXZpZXcgdGhpcyBjYXNlIGFuZCBtYWtlIGEgZmluYWwgZGVjaXNpb248L3A+CiAgICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgIDwvdHI+CiAgICAgICAgICAgIDwvdGJvZHk+CiAgICAgICAgICAgIDwvdGFibGU+CiAgICAgICAgPC90ZD4KICAgIDwvdHI+CjwvdGFibGU+IDx0YWJsZSBpZD0ibG9nb0NvbnRhaW5lciIgd2lkdGg9IjEwMCUiIGJvcmRlcj0iMCIgY2VsbHNwYWNpbmc9IjAiIGNlbGxwYWRkaW5nPSIwIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iYmFja2dyb3VuZC1jb2xvcjogI2ZmZmZmZjsiPgogICAgPHRyPgogICAgICAgIDx0ZCB3aWR0aD0iMTAwJSI+PCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KICAgICAgICA8dGFibGUgd2lkdGg9IjYwMCIgYWxpZ249ImNlbnRlciIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBib3JkZXI9IjAiPgogICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICA8dGQ+CiAgICAgICAgPCFbZW5kaWZdLS0+CiAgICAgICAgICAgIDx0YWJsZSB3aWR0aD0iMTAwJSIgY2xhc3M9Im1vYldyYXBwZXJUZW1wbGF0ZSIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iYmFja2dyb3VuZC1jb2xvcjogI2Y5ZjlmOTsgYm9yZGVyLWxlZnQ6IDFweCBzb2xpZCAjZGRkZGRkOyBib3JkZXItcmlnaHQ6IDFweCBzb2xpZCAjZGRkZGRkOyBtYXgtd2lkdGg6IDYwMHB4OyI+CiAgICAgICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICAgICAgPHRkIHZhbGlnbj0idG9wIiB3aWR0aD0iNjAwIiBzdHlsZT0icGFkZGluZzogMTZweCAwIDhweCAxNnB4OyBib3JkZXItY29sbGFwc2U6IGNvbGxhcHNlICFpbXBvcnRhbnQ7IGJvcmRlci1zcGFjaW5nOiAwICFpbXBvcnRhbnQ7Ij4KICAgICAgICAgICAgICAgICAgICAgICAgPGEgaHJlZj0iaHR0cHM6Ly9yb3Zlci5lYmF5LmNvbS9yb3Zlci8wL2UxMjU2OS5tbnVsbC5sMzEyNy83P2V1aWQ9NjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkmYW1wO2J1PTQ0MjMwMDA3MTE1JmFtcDtzZWduYW1lPTEyNTY5JmFtcDtjcmQ9MjAyMTEwMDcxNzA4MjImYW1wO29zdWI9LTF+MSZhbXA7Y2g9b3Nnb29kJmFtcDtsb2M9aHR0cHMlM0ElMkYlMkZ3d3cuZWJheS5jb20uYXUlMkZ1bGslMkZzdGFydCUyRnNob3AmYW1wO3NvalRhZ3M9YnU9YnUsY2g9Y2gsc2VnbmFtZT1zZWduYW1lLGNyZD1jcmQsdXJsPWxvYyxvc3ViPW9zdWIiIHN0eWxlPSJ0ZXh0LWRlY29yYXRpb246IG5vbmU7IGNvbG9yOiAjMDY1NGJhOyI+PGltZyBzcmM9Imh0dHBzOi8vcC5lYmF5c3RhdGljLmNvbS9hdy9lbWFpbC9lQmF5TG9nby5wbmciIHdpZHRoPSI4MCIgaGVpZ2h0PSIzMiIgYm9yZGVyPSIwIiBhbHQ9ImVCYXkiIGFsaWduPSJsZWZ0IiBzdHlsZT0iZGlzcGxheTogaW5saW5lIGJsb2NrOyBvdXRsaW5lOiBub25lOyB0ZXh0LWRlY29yYXRpb246IG5vbmU7IC1tcy1pbnRlcnBvbGF0aW9uLW1vZGU6IGJpY3ViaWM7IGJvcmRlcjogbm9uZTsiPjwvYT48aW1nIHNyYz0iaHR0cHM6Ly93d3cuZWJheWFkc2VydmljZXMuY29tL21hcmtldGluZ3RyYWNraW5nL3YxL2ltcHJlc3Npb24/bWtldnQ9NCZhbXA7bWtwaWQ9MCZhbXA7ZW1zaWQ9ZTEyNTY5JmFtcDtta2NpZD03JmFtcDtjaD1vc2dvb2QmYW1wO2V1aWQ9NjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkmYW1wO2J1PTQ0MjMwMDA3MTE1JmFtcDtvc3ViPS0xJTdFMSZhbXA7Y3JkPTIwMjExMDA3MTcwODIyJmFtcDtzZWduYW1lPTEyNTY5JmFtcDtzb2pUYWdzPWVtaWQlM0RidSUyQ2NoJTNEY2glMkNidSUzRGJ1JTJDb3N1YiUzRG9zdWIlMkNjcmQlM0RjcmQlMkNzZWduYW1lJTNEc2VnbmFtZSUyQ2NobmwlM0Rta2NpZCIgYWx0PSIiIHN0eWxlPSJib3JkZXI6IG5vbmU7IGhlaWdodDoxOyBkaXNwbGF5OiBub25lICFpbXBvcnRhbnQ7Ij4KICAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgPC90YWJsZT4KICAgICAgICA8L3RkPgogICAgPC90cj4KPC90YWJsZT4KIDx0YWJsZSBpZD0iYm9keUNvbnRhaW5lciIgd2lkdGg9IjEwMCUiIGJvcmRlcj0iMCIgY2VsbHNwYWNpbmc9IjAiIGNlbGxwYWRkaW5nPSIwIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iIGJhY2tncm91bmQtY29sb3I6ICNmZmZmZmY7Ij4KICAgIDx0cj4KICAgICAgICA8dGQgd2lkdGg9IjEwMCUiIHZhbGlnbj0idG9wIiBzdHlsZT0iYm9yZGVyOiBub25lOyI+PCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KICAgICAgICA8dGFibGUgd2lkdGg9IjYwMCIgYWxpZ249ImNlbnRlciIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBib3JkZXI9IjAiPgogICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICA8dGQ+CiAgICAgICAgPCFbZW5kaWZdLS0+CiAgICAgICAgICAgPHRhYmxlIHdpZHRoPSIxMDAlIiBjbGFzcz0ibW9iV3JhcHBlclRlbXBsYXRlIiBib3JkZXI9IjAiIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgYWxpZ249ImNlbnRlciIgc3R5bGU9ImJhY2tncm91bmQtY29sb3I6ICNmOWY5Zjk7IGJvcmRlci1sZWZ0OjFweCBzb2xpZCAjREREREREIDsgYm9yZGVyLXJpZ2h0OjFweCBzb2xpZCAjREREREREOyBtYXgtd2lkdGg6IDYwMHB4OyI+CiAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgIDx0ZCB2YWxpZ249InRvcCI+CiAgICAgICAgICAgICAgICAgICAgICAgPHAgc3R5bGU9ImZvbnQtZmFtaWx5OiBIZWx2ZXRpY2EgTmV1ZSwgSGVsdmV0aWNhLCBBcmlhbAo7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IGxpbmUtaGVpZ2h0OiBub3JtYWw7IGNvbG9yOiAjMzMzMzMzOyB0ZXh0LWFsaWduOiBsZWZ0OyBmb250LXNpemU6IDE4cHg7IG1hcmdpbjogMTZweCAxNnB4IDAgMTZweDsiIGFsaWduPSJsZWZ0Ij5XZSdsbCByZXZpZXcgdGhpcyBjYXNlIGFuZCBtYWtlIGEgZmluYWwgZGVjaXNpb248L3A+CiAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICA8L3RyPgogICAgICAgICAgIDwvdGFibGU+IAogICAgICAgIDwvdGQ+CiAgICA8L3RyPgo8L3RhYmxlPiA8dGFibGUgaWQ9ImJvZHlDb250YWluZXIiIHdpZHRoPSIxMDAlIiBib3JkZXI9IjAiIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgYWxpZ249ImNlbnRlciIgc3R5bGU9IiBiYWNrZ3JvdW5kLWNvbG9yOiAjZmZmZmZmOyI+CiAgICA8dHI+CiAgICAgICAgPHRkIHdpZHRoPSIxMDAlIiB2YWxpZ249InRvcCIgc3R5bGU9ImJvcmRlcjogbm9uZTsiPjwhLS1baWYgKGd0ZSBtc28gOSl8KElFKV0+CiAgICAgICAgPHRhYmxlIHdpZHRoPSI2MDAiIGFsaWduPSJjZW50ZXIiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgYm9yZGVyPSIwIj4KICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAgPHRkPgogICAgICAgIDwhW2VuZGlmXS0tPgogICAgICAgICAgIDx0YWJsZSB3aWR0aD0iMTAwJSIgY2xhc3M9Im1vYldyYXBwZXJUZW1wbGF0ZSIgYm9yZGVyPSIwIiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJiYWNrZ3JvdW5kLWNvbG9yOiAjZjlmOWY5OyBib3JkZXItbGVmdDoxcHggc29saWQgI0RERERERCA7IGJvcmRlci1yaWdodDoxcHggc29saWQgI0RERERERDsgbWF4LXdpZHRoOiA2MDBweDsiPgogICAgICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICAgIDx0ZCB2YWxpZ249InRvcCI+CiAgICAgICAgICAgICAgICAgICAgICA8cCBzdHlsZT0iZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsCjsgZm9udC13ZWlnaHQ6IG5vcm1hbDsgbGluZS1oZWlnaHQ6IG5vcm1hbDsgY29sb3I6ICMzMzMzMzM7IHRleHQtYWxpZ246IGxlZnQ7IGZvbnQtc2l6ZTogMTRweDsgbWFyZ2luOiAyNHB4IDE2cHggMCAxNnB4OyIgYWxpZ249ImxlZnQiIG9wYWNpdHk9IjEwMCUiPkhpIEZyYW5rLDwvcD4KICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICA8L3RhYmxlPiAKICAgICAgICA8L3RkPgogICAgPC90cj4KPC90YWJsZT4gCgoKCgoKCgoKCjx0YWJsZSBpZD0iYm9keUNvbnRhaW5lciIgd2lkdGg9IjEwMCUiIGJvcmRlcj0iMCIgY2VsbHNwYWNpbmc9IjAiIGNlbGxwYWRkaW5nPSIwIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iIGJhY2tncm91bmQtY29sb3I6ICNmZmZmZmY7Ij4KICAgIDx0cj4KICAgICAgICA8dGQgd2lkdGg9IjEwMCUiIHZhbGlnbj0idG9wIiBzdHlsZT0iYm9yZGVyOiBub25lOyI+PCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KICAgICAgICA8dGFibGUgd2lkdGg9IjYwMCIgYWxpZ249ImNlbnRlciIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBib3JkZXI9IjAiPgogICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICA8dGQ+CiAgICAgICAgPCFbZW5kaWZdLS0+CiAgICAgICAgICAgIDx0YWJsZSB3aWR0aD0iMTAwJSIgY2xhc3M9Im1vYldyYXBwZXJUZW1wbGF0ZSIgYm9yZGVyPSIwIiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJiYWNrZ3JvdW5kLWNvbG9yOiAjZjlmOWY5OyBib3JkZXItbGVmdDoxcHggc29saWQgI0RERERERCA7IGJvcmRlci1yaWdodDoxcHggc29saWQgI0RERERERDsgbWF4LXdpZHRoOiA2MDBweDsiPgoKICAgICAgICAgICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICAgICAgICAgIDx0ZCB2YWxpZ249InRvcCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8cCBzdHlsZT0iZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsCjsgZm9udC13ZWlnaHQ6IG5vcm1hbDsgbGluZS1oZWlnaHQ6IG5vcm1hbDsgY29sb3I6ICMzMzMzMzM7IHRleHQtYWxpZ246IGxlZnQ7IGZvbnQtc2l6ZTogMTRweDsgbWFyZ2luOiAyNHB4IDE2cHggMCAxNnB4OyIgYWxpZ249ImxlZnQiIG9wYWNpdHk9IjEwMCUiPlRoZSBidXllciBoYXMgYXNrZWQgZUJheSBDdXN0b21lciBTZXJ2aWNlIHRvIHJldmlldyB0aGlzIGNhc2UgYW5kIG1ha2UgYSBmaW5hbCBkZWNpc2lvbi48L3A+CiAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+CiAgICAgICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgCiAgICAgICAgCiAgICAgICAgICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAgICAgICAgICA8dGQgdmFsaWduPSJ0b3AiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgPHAgc3R5bGU9ImZvbnQtZmFtaWx5OiBIZWx2ZXRpY2EgTmV1ZSwgSGVsdmV0aWNhLCBBcmlhbAo7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IGxpbmUtaGVpZ2h0OiBub3JtYWw7IGNvbG9yOiAjMzMzMzMzOyB0ZXh0LWFsaWduOiBsZWZ0OyBmb250LXNpemU6IDE0cHg7IG1hcmdpbjogMjRweCAxNnB4IDAgMTZweDsiIGFsaWduPSJsZWZ0IiBvcGFjaXR5PSIxMDAlIj5XZSdsbCBnZXQgYmFjayB0byB5b3Ugd2l0aGluIDQ4IGhvdXJzLjwvcD4KICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgICAgICA8L3RyPgogICAgICAgIAogICAgICAgICAgICAgICAgCiAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgIAogICAgICAgICAgICA8L3RhYmxlPiAKICAgICAgICAgICA8IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgPC90cj4KICAgICAgICA8L3RhYmxlPiAgICAKICAgICAgICA8IVtlbmRpZl0tLT4KICAgICAgICA8L3RkPgogICAgPC90cj4KPC90YWJsZT4gCgoKPHRhYmxlIGlkPSJib2R5Q29udGFpbmVyIiB3aWR0aD0iMTAwJSIgYm9yZGVyPSIwIiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSIgYmFja2dyb3VuZC1jb2xvcjogI2ZmZmZmZjsiPgogICAgPHRyPgogICAgICAgIDx0ZCB3aWR0aD0iMTAwJSIgdmFsaWduPSJ0b3AiIHN0eWxlPSJib3JkZXI6IG5vbmU7Ij48IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgIDx0YWJsZSB3aWR0aD0iNjAwIiBhbGlnbj0iY2VudGVyIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCI+CiAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgIDx0ZD4KICAgICAgICA8IVtlbmRpZl0tLT4KICAgICAgICAgICA8dGFibGUgd2lkdGg9IjEwMCUiIGNsYXNzPSJtb2JXcmFwcGVyVGVtcGxhdGUiIGJvcmRlcj0iMCIgY2VsbHNwYWNpbmc9IjAiIGNlbGxwYWRkaW5nPSIwIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iYmFja2dyb3VuZC1jb2xvcjogI2Y5ZjlmOTsgYm9yZGVyLWxlZnQ6MXB4IHNvbGlkICNEREREREQgOyBib3JkZXItcmlnaHQ6MXB4IHNvbGlkICNEREREREQ7IG1heC13aWR0aDogNjAwcHg7Ij4KCiAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgIDx0ZCB2YWxpZ249InRvcCIgc3R5bGU9InBhZGRpbmc6IDE2cHggMTZweCAxNnB4IDE2cHg7Ij48IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgIDx0YWJsZSB3aWR0aD0iaW5oZXJpdCIgYWxpZ249ImxlZnQiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgYm9yZGVyPSIwIj4KICAgICAgICAgICAgPHRyPgogICAgICAgICAgICAgICAgPHRkPgogICAgICAgIDwhW2VuZGlmXS0tPgogICAgICAgIDx0YWJsZSB3aWR0aD0iMTAwJSIgYWxpZ249ImxlZnQiIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgYm9yZGVyPSIwIiBjbGFzcz0iYXV0b1NpemVCdXR0b24iPgogICAgICAgICAgICAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPHRkIHZhbGlnbj0idG9wIiBhbGlnbj0iY2VudGVyIiBiZ2NvbG9yPSIjMDA3OWJjIiBzdHlsZT0iYm94LXNoYWRvdzogMnB4IDNweCAwICNlNWU1ZTU7IGZpbHRlcjogcHJvZ2lkOkRYSW1hZ2VUcmFuc2Zvcm0uTWljcm9zb2Z0LmdyYWRpZW50KCBzdGFydENvbG9yc3RyPScjMDA3OWJjJywgZW5kQ29sb3JzdHI9JyMwMDUxOWUnLEdyYWRpZW50VHlwZT0wICk7IGJhY2tncm91bmQtaW1hZ2U6IGxpbmVhci1ncmFkaWVudCh0byBib3R0b20sICAjMDA3OWJjIDAlLCMwMDUxOWUgMTAwJSk7IGJhY2tncm91bmQtY29sb3I6ICMwMDc5YmM7IHBhZGRpbmc6IDEwcHggMTdweDsgYm9yZGVyOiAwcHggc29saWQgIzAwNTE5ZTtib3JkZXItcmFkaXVzOjNweDsgYm9yZGVyOiAxcHggc29saWQgIzMzMzMzMzsiPgogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPGEgaHJlZj0iaHR0cHM6Ly93d3cuZWJheS5jb20uYXUvcmVzL0Nhc2UvVmlld0Nhc2U/aWQ9NTI3OTU0NjE3NSZhbXA7bWtldnQ9MSZhbXA7bWtwaWQ9MCZhbXA7ZW1zaWQ9ZTEyNTY5Lmw4MzYxJmFtcDtta2NpZD03JmFtcDtjaD1vc2dvb2QmYW1wO2V1aWQ9NjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkmYW1wO2J1PTQ0MjMwMDA3MTE1JmFtcDtvc3ViPS0xJTdFMSZhbXA7Y3JkPTIwMjExMDA3MTcwODIyJmFtcDtzZWduYW1lPTEyNTY5JmFtcDtzb2pUYWdzPWNoJTNEY2glMkNidSUzRGJ1JTJDb3N1YiUzRG9zdWIlMkNjcmQlM0RjcmQlMkNzZWduYW1lJTNEc2VnbmFtZSUyQ2NobmwlM0Rta2NpZCIgc3R5bGU9InRleHQtZGVjb3JhdGlvbjogbm9uZTsgY29sb3I6ICNmZmZmZmY7IGZvbnQtc2l6ZTogMTRweDsgbGluZS1oZWlnaHQ6IG5vcm1hbDsgZm9udC13ZWlnaHQ6IGJvbGQ7IGZvbnQtZmFtaWx5OiBIZWx2ZXRpY2EsIEFyaWFsLCBTYW5zLVNlcmlmOyB0ZXh0LXNoYWRvdzogMXB4IDFweCAwICMwMDUxOWU7IHBhZGRpbmc6IDEwcHggMTVweDsgd2hpdGUtc3BhY2U6IG5vd3JhcDsiPlNlZSBjYXNlIGRldGFpbHM8L2E+ICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RyPgogICAgICAgICAgICAgICAgICAgICAgPC90YWJsZT4gCiAgICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICA8L3RhYmxlPiAKICAgICAgICA8L3RkPgogICAgPC90cj4KPC90YWJsZT4gCgoJPHRhYmxlIGlkPSJib2R5Q29udGFpbmVyIiB3aWR0aD0iMTAwJSIgYm9yZGVyPSIwIiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJiYWNrZ3JvdW5kLWNvbG9yOiAjZmZmZmZmOyI+CgkJPHRyPgoJCQk8dGQgd2lkdGg9IjEwMCUiIHZhbGlnbj0idG9wIiBzdHlsZT0iYm9yZGVyOiBub25lOyI+CgkJCQk8IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgIDx0YWJsZSB3aWR0aD0iNjAwIiBhbGlnbj0iY2VudGVyIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCI+CiAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgIDx0ZD4KICAgICAgICA8IVtlbmRpZl0tLT4KCgkJPHRhYmxlIHdpZHRoPSIxMDAlIiBjbGFzcz0ibW9iV3JhcHBlclRlbXBsYXRlIiBib3JkZXI9IjAiIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgYWxpZ249ImNlbnRlciIgc3R5bGU9ImJhY2tncm91bmQtY29sb3I6ICNmOWY5Zjk7IGJvcmRlci1sZWZ0OiAxcHggc29saWQgI0RERERERDsgYm9yZGVyLXJpZ2h0OiAxcHggc29saWQgI0RERERERDsgbWF4LXdpZHRoOiA2MDBweDsgdGV4dC1hbGlnbjogY2VudGVyOyI+CgkJCQoJCQk8dHI+CgkJCSAgICAKCQkJPHRkIHN0eWxlPSJwYWRkaW5nOiAwIDE2cHggMCAxNnB4OyI+CgkJCQk8dGFibGUgd2lkdGg9IjEwMCUiIGJvcmRlcj0iMCIgY2VsbHNwYWNpbmc9IjAiIGNlbGxwYWRkaW5nPSIwIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iYmFja2dyb3VuZC1jb2xvcjogI2Y5ZjlmOTsgIHRleHQtYWxpZ246IGNlbnRlcjtib3JkZXItdG9wOiAxcHggc29saWQgI0RERERERDtib3JkZXItYm90dG9tOiAxcHggc29saWQgI0RERERERDsiPgoJCQkJCTx0cj4KCQkJCQkJPHRkIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgdmFsaWduPSJ0b3AiIHN0eWxlPSIgcGFkZGluZzowcHg7IHRleHQtYWxpZ246bGVmdCI+CiAgICAgICAgPCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KICAgICAgICAgICAgICAgICAgICA8dGFibGUgd2lkdGg9IjE2MCIgaGVpZ2h0PSIxODQiIGFsaWduPSJsZWZ0IiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCI+CiAgICAgICAgICAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgICAgICAgICAgIDx0ZD4KICAgICAgICA8IVtlbmRpZl0tLT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAJCQkJCQkJPHRhYmxlIHZhbGlnbj0idG9wIiBhbGlnbj0iY2VudGVyIiB3aWR0aD0iMTYwcHgiIGhlaWdodD0iMTg0cHgiIGNlbGxwYWRkaW5nPSIwIiBjZWxsc3BhY2luZz0iMCIgc3R5bGU9InZlcnRpY2FsLWFsaWduOnRvcDsgYm9yZGVyLXNwYWNpbmc6IDAgIWltcG9ydGFudDsgd2lkdGg6IDE2MHB4OyBoZWlnaHQ6MTg0cHggO2Rpc3BsYXk6IGlubGluZS1ibG9jazsiIGJvcmRlcj0iMCI+CiAgICAJCQkJCQkJCTx0cj4KICAgIAkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgc3R5bGU9InBhZGRpbmc6MHB4OyI+CiAgICAKICAgIAkJCQkJCQkJCQk8dGFibGUgd2lkdGg9IjEwMCUiIHZhbGlnbj0idG9wIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIHN0eWxlPSIgYm9yZGVyLXNwYWNpbmc6IDA7IHdpZHRoOiAxMDAlOyAgaGVpZ2h0OiAxMDAlOyB2ZXJ0aWNhbC1hbGlnbjogdGV4dC10b3A7IiBib3JkZXI9IjAiPgogICAgCiAgICAJCQkJCQkJCQkJCTx0cj4KICAgIAkJCQkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgc3R5bGU9InBhZGRpbmc6MTZweCAwICAxNnB4IDAgOyI+CiAgICAKICAgIAkJCQkJCQkJCQkJCQk8cCBzdHlsZT0id2lkdGg6MTAwJTsgbWFyZ2luOjA7cGFkZGluZzowIj4KICAgIAkJCQkJCQkJCQkJCQkJCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8aW1nIGhlaWdodD0iMTYwIiB3aWR0aD0iMTYwIiBzcmM9Imh0dHBzOi8vaS5lYmF5aW1nLmNvbS9pbWFnZXMvZy8tazRBQU9Td3NRVmdWRkVkL3MtbDIwMC5qcGciIGJvcmRlcj0iMCIgYWx0PSJBZXJwcm8gQVBQMDczIFByaW1hcnkgSVNPIEhhcm5lc3MgdG8gU3VpdCBOaXNzYW4gVmVoaWNsZXMiIHN0eWxlPSJkaXNwbGF5OiBibG9jazsgb3V0bGluZTogbm9uZTsgdGV4dC1kZWNvcmF0aW9uOiBub25lOyAtbXMtaW50ZXJwb2xhdGlvbi1tb2RlOiBiaWN1YmljOyBib3JkZXItcmFkaXVzOiAzcHg7IG1hcmdpbjogMDsgaGVpZ2h0OjE2MHB4O3dpZHRoOjE2MHB4O3dvcmQtd3JhcDpicmVhay13b3JkOyI+CiAgICAJCQkJCQkJCQkJCQkJCiAgICAJCQkJCQkJCQkJCQkJICAgIDwvcD4gCiAgICAJCQkJCQkJCQkJCQk8L3RkPgogICAgCQkJCQkJCQkJCQk8L3RyPgogICAgCQkJCQkJCQkJCTwvdGFibGU+CiAgICAJCQkJCQkJCQkJCiAgICAJCQkJCQkJCQk8L3RkPgogICAgCQkJCQkJCQk8L3RyPgkJCiAgICAJCQkJCQkJCiAgICAJCQkJCQkJPC90YWJsZT4KICAgIAk8IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgICAgICAgICA8L3RyPgogICAgICAgICAgICAgICAgICAgIDwvdGFibGU+CiAgICAgICAgICAgICAKICAgICAgICA8IVtlbmRpZl0tLT4KICAgICAgICAKICAgICAgICAgICAgICAgIDwhLS1baWYgKGd0ZSBtc28gOSl8KElFKV0+CiAgICAgICAgICAgICAgICAgICAgPHRhYmxlIHdpZHRoPSIzOTIiICBhbGlnbj0ibGVmdCIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBib3JkZXI9IjAiPgogICAgICAgICAgICAgICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICA8dGQ+CiAgICAgICAgPCFbZW5kaWZdLS0+CgoKCQkJCQkJCTx0YWJsZSB3aWR0aD0iMTAwJSIgYm9yZGVyPSIwIiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIGFsaWduPSJjZW50ZXIiIHZhbGlnbj0idG9wIiBzdHlsZT0ibWF4LXdpZHRoOjM5MnB4OyB3aWR0aDoxMDAlOyBib3JkZXItc3BhY2luZzogMCAhaW1wb3J0YW50OyBkaXNwbGF5OiBpbmxpbmUtYmxvY2s7ICB0ZXh0LWFsaWduOiBjZW50ZXI7Ij4KCQkJCQkJCQk8dHI+CgkJCQkJCQkJCTx0ZCBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIHZhbGlnbj0idG9wIiBzdHlsZT0id2lkdGg6MzU2cHg7IHBhZGRpbmc6MHB4OyIgYWxpZ249ImNlbnRlciI+CgkJCQkJCQkJCSAgICAKCQkJCQkJCQkgICAgICAgIDwhLS1baWYgIW1zbyA5XT48IS0tPgoJCQkJCQkJCQkgICAgICAgIDx0YWJsZSBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIHZhbGlnbj0idG9wIiBhbGlnbj0iY2VudGVyIiB3aWR0aD0iaW5pdGlhbCIgc3R5bGU9ImJvcmRlci1zcGFjaW5nOiAwOyB3aWR0aDogaW5pdGlhbDsgdGV4dC1hbGlnbjogY2VudGVyOyAgIGhlaWdodDogaW5oZXJpdDsgIHZlcnRpY2FsLWFsaWduOiB0ZXh0LXRvcDsiIGJvcmRlcj0iMCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8IS0tU2FtcGxlIGNvbW1lbnQtLT4gCiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwhLS1baWYgKGd0ZSBtc28gOSl8KElFKV0+CgkJCQkJCQkJCSAgICA8dGFibGUgIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgIHZhbGlnbj0idG9wIiBhbGlnbj0iY2VudGVyIiAgIHdpZHRoPSIxMDAlIgoJCQkJCQkJCQkJCXN0eWxlPSJib3JkZXItc3BhY2luZzogMDsgd2lkdGg6IDEwMCU7IHRleHQtYWxpZ246IGNlbnRlcjsgICBoZWlnaHQ6IGluaGVyaXQ7ICB2ZXJ0aWNhbC1hbGlnbjogdGV4dC10b3A7IgoJCQkJCQkJCQkJCWJvcmRlcj0iMCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8IVtlbmRpZl0tLT4KCQkJCQkJCQkJCQk8dHI+CgkJCQkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgc3R5bGU9InBhZGRpbmc6MTZweCAwICAxNnB4IDE2cHggOyAiPgoJCQkJCQkJCQkJCQkJPHRhYmxlIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCIgdmFsaWduPSJ0b3AiIGFsaWduPSJjZW50ZXIiIHdpZHRoPSIxMDAlIj4KCQkJCQkJCQkJCQkJCQk8dHI+CgkJCQkJCQkJCQkJCQkJCTx0ZCBjb2xzcGFuPSIyIiB2YWxpZ249InRvcCIgc3R5bGU9IiBmb250LWZhbWlseTogSGVsdmV0aWNhIE5ldWUsIEhlbHZldGljYSwgQXJpYWw7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IGxpbmUtaGVpZ2h0OiBub3JtYWw7IGNvbG9yOiAjMzMzMzMzOyBmb250LXNpemU6IDE4cHg7ICBwYWRkaW5nLWJvdHRvbToxNnB4O3RleHQtZGVjb3JhdGlvbjogbm9uZSI+CgkJCQkJCQkJCQkJCQkJCSAgICA8cCB2YWxpZ249InRvcCIgc3R5bGU9InBhZGRpbmc6MDsgbWFyZ2luOjA7IGZvbnQtZmFtaWx5OiBIZWx2ZXRpY2EgTmV1ZSwgSGVsdmV0aWNhLCBBcmlhbDsgZm9udC13ZWlnaHQ6IG5vcm1hbDsgbGluZS1oZWlnaHQ6IG5vcm1hbDsgY29sb3I6ICMzMzMzMzM7IHRleHQtYWxpZ246IGxlZnQ7IGZvbnQtc2l6ZTogMThweDsgd29yZC13cmFwOiBicmVhay13b3JkOyI+CgkJCQkJCQkJCQkJCQkJCSAgICAgICAgPC9wPjxkaXYgc3R5bGU9InRleHQtYWxpZ246bGVmdDt3aWR0aDozMDBweDtvdmVyZmxvdzphdXRvIj4KCQkJCQkJCQkJCQkJCQkJCUFlcnBybyBBUFAwNzMgUHJpbWFyeSBJU08gSGFybmVzcyB0byBTdWl0IE5pc3NhbiBWZWhpY2xlcwoJCQkJCQkJCQkJCQkJCQkJPC9kaXY+CgkJCQkJCQkJCQkJCQkJCQkKCQkJCQkJCQkJCQkJCQkJCTwvdGQ+CgkJCQkJCQkJCQkJCQkJPC90cj4KICAgIAkJCQkJCQkJCQkJCQkJPHRyPgogICAgCQkJCQkJCQkJCQkJCQkJPHRkIHZhbGlnbj0idG9wIiBjbGFzcz0iaXRlbV9jYXNlX29wZW4iIHN0eWxlPSIgZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgZm9udC1zaXplOiAxNHB4O21pbi13aWR0aDo5MHB4O3BhZGRpbmc6MCAwIDhweCAwOyI+CiAgICAgICAgCQkJCQkJCQkJCQkJCQkJCTxwIHZhbGlnbj0idG9wIiBzdHlsZT0ibWFyZ2luOjA7dGV4dC1hbGlnbjogbGVmdDsiPgogICAgICAgIAkJCQkJCQkJCQkJCQkJCQkgICAgT3JkZXIgbnVtYmVyOgogICAgICAgIAkJCQkJCQkJCQkJCQkJCQk8L3A+CiAgICAJCQkJCQkJCQkJCQkJCQkJPC90ZD4KICAgIAkJCQkJCQkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgc3R5bGU9InRleHQtYWxpZ246IGxlZnQ7d2lkdGg6MTAwJSA7IGZvbnQtZmFtaWx5OiBIZWx2ZXRpY2EgTmV1ZSwgSGVsdmV0aWNhLCBBcmlhbDsgZm9udC13ZWlnaHQ6IG5vcm1hbDsgbGluZS1oZWlnaHQ6IG5vcm1hbDsgY29sb3I6ICMzMzMzMzM7ICBmb250LXNpemU6IDE0cHg7IHdvcmQtYnJlYWs6YnJlYWstYWxsO3BhZGRpbmc6MCAwIDhweCAwOyI+CiAgICAJCQkJCQkJCQkJCQkJCQkgICAgICAgICAgICAwNC0wNzU5Ny01Njk2MwogICAgCQkJCQkJCQkJCQkJCQkJPC90ZD4KICAgIAkJCQkJCQkJCQkJCQkJPC90cj4KCQkJCQkJCQkJCQkJCQk8dHI+CgkJCQkJCQkJCQkJCQkJICAgIDwhLS1baWYgKGd0ZSBtc28gOSl8KElFKV0+CgkJCQkJCQkJCQkJCQkJICAgICAgICA8dGQgdmFsaWduPSJ0b3AiIHdpZHRoPSI1MCUiIGFsaWduPSJsZWZ0Ij4KCQkJCQkJCQkJCQkJCQkgICAgICAgIDx0YWJsZSBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIHZhbGlnbj0idG9wIiBhbGlnbj0ibGVmdCIgIHdpZHRoPSIxMDAlIj4KCQkJCQkJCQkJCQkJCQkgICAgICAgIDx0cj4KCQkJCQkJCQkJCQkJCQkgICAgPCFbZW5kaWZdLS0+CgkJCQkJCQkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgc3R5bGU9IndpZHRoOjUwJTsgZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgIGZvbnQtc2l6ZTogMTRweDsgbWluLXdpZHRoOjkwcHg7cGFkZGluZzowIDAgOHB4IDA7Ij4KCQkJCQkJCQkJCQkJCQkJICAgIAoJCQkJCQkJCQkJCQkJCQkgICAgPHAgdmFsaWduPSJ0b3AiIHN0eWxlPSJtYXJnaW46MDt0ZXh0LWFsaWduOiBsZWZ0OyI+CgkJCQoKICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgQnV5ZXI6CiAgICAgICAgICAgICAgIAogICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIAoJCQkJCQkJCQkJCQkJCQkgICAgPC9wPgoJCQkJCQkJCQkJCQkJCQk8L3RkPgoJCQkJCQkJCQkJCQkJCQk8IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgoJCQkJCQkJCQkJCQkJCQk8L3RyPgoJCQkJCQkJCQkJCQkJCQk8L3RhYmxlPgoJCQkJICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwhW2VuZGlmXS0tPiAKCQkJCQkJCQkJCQkJCQkJPCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KCQkJCQkJCQkJCQkJCQkgICAgICAgIDx0ZCB2YWxpZ249InRvcCIgYWxpZ249ImxlZnQiPiAKCQkJCQkJCQkJCQkJCQkgICAgICAgIDx0YWJsZSBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIHZhbGlnbj0idG9wIiBhbGlnbj0ibGVmdCIgIHdpZHRoPSIxMDAlIj4KCQkJCQkJCQkJCQkJCQkgICAgICAgIDx0cj4KCQkJCQkJCQkJCQkJCQkgICAgPCFbZW5kaWZdLS0+CgkJCQkJCQkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgc3R5bGU9IndpZHRoOjEwMCUgOyBmb250LWZhbWlseTogSGVsdmV0aWNhIE5ldWUsIEhlbHZldGljYSwgQXJpYWw7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IGxpbmUtaGVpZ2h0OiBub3JtYWw7IGNvbG9yOiAjMzMzMzMzOyAgZm9udC1zaXplOiAxNHB4OyB3b3JkLWJyZWFrOiBicmVhay1hbGw7cGFkZGluZzowIDAgOHB4IDA7Ij4KCQkJCQkJCQkJCQkJCQkJICAgIDxwIHZhbGlnbj0idG9wIiBzdHlsZT0ibWFyZ2luOjA7Zm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgdGV4dC1hbGlnbjogbGVmdDsgZm9udC1zaXplOiAxNHB4OyB3b3JkLWJyZWFrOiBicmVhay1hbGw7Ij4KICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgcmFqaXZfbGsKCQkJCQkJCQkJCQkJCQkJCTwvcD4KCQkJCQkJCQkJCQkJCQkJPC90ZD4KCQkJCQkJCQkJCQkJCQkJPCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KCQkJCQkJCQkJCQkJCQkJPC90cj4KCQkJCQkJCQkJCQkJCQkJPC90YWJsZT4KCQkJCSAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIDwvdGQ+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICA8IVtlbmRpZl0tLT4gCgkJCQkJCQkJCQkJCQkJPC90cj4KCQkJCQkJCQkJCQkJCQk8dHI+CgkJCQkJCQkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgY2xhc3M9Iml0ZW1fY2FzZV9vcGVuIiBzdHlsZT0iZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgIGZvbnQtc2l6ZTogMTRweDsgbWluLXdpZHRoOjkwcHg7cGFkZGluZzowIDAgOHB4IDA7Ij4KCQkJCQkJCQkJCQkJCQkJCTxwIHZhbGlnbj0idG9wIiBzdHlsZT0ibWFyZ2luOjA7dGV4dC1hbGlnbjogbGVmdDsiPgoJCQkJCQkJCQkJCQkJCQkJUXVhbnRpdHk6CgkJCQkJCQkJCQkJCQkJCQk8L3A+PC90ZD4KCQkJCQkJCQkJCQkJCQkJPHRkIHZhbGlnbj0idG9wIiBzdHlsZT0iZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgIGZvbnQtc2l6ZTogMTRweDsgd29yZC1icmVhazpicmVhay1hbGw7dGV4dC1hbGlnbjogbGVmdDtwYWRkaW5nOjAgMCA4cHggMDsiPgoJCQkJCQkJCQkJCQkJCQkJMTwvdGQ+CgkJCQkJCQkJCQkJCQkJPC90cj4KCQkJCQkJCQkJCQkJCQk8dHI+CgkJCQkJCQkJCQkJCQkJCTx0ZCB2YWxpZ249InRvcCIgY2xhc3M9Iml0ZW1fY2FzZV9vcGVuIiBzdHlsZT0iZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgIGZvbnQtc2l6ZTogMTRweDttaW4td2lkdGg6OTBweDtwYWRkaW5nOjAgMCA4cHggMDsiPgoJCQkJCQkJCQkJCQkJCQkgICAgPHAgdmFsaWduPSJ0b3AiIHN0eWxlPSJtYXJnaW46MDt0ZXh0LWFsaWduOiBsZWZ0OyI+CgkJCQkJCQkJCQkJCQkJCQlDYXNlICM6CgkJCQkJCQkJCQkJCQkJCQk8L3A+PC90ZD4KCQkJCQkJCQkJCQkJCQkJPHRkIHZhbGlnbj0idG9wIiBzdHlsZT0iZm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgIGZvbnQtc2l6ZTogMTRweDsgd29yZC1icmVhazpicmVhay1hbGw7dGV4dC1kZWNvcmF0aW9uOiBub25lO3BhZGRpbmc6MCAwIDhweCAwOyI+CgkJCQkJCQkJCQkJCQkJCSAgICA8cCB2YWxpZ249InRvcCIgc3R5bGU9IiBtYXJnaW46MDsgdGV4dC1kZWNvcmF0aW9uOiBub25lOyBjb2xvcjogIzMzMzMzMyFpbXBvcnRhbnQ7dGV4dC1hbGlnbjogbGVmdDsiPgoJCQkJCQkJCQkJCQkJCQkJNTI3OTU0NjE3NQoJCQkJCQkJCQkJCQkJCQkJPC9wPgoJCQkJCQkJCQkJCQkJCQkJPC90ZD4KCQkJCQkJCQkJCQkJCQk8L3RyPgoJCQkJCQkJCQkJCQkJCTx0cj4KCQkJCQkJCQkJCQkJCQkJPHRkIHZhbGlnbj0idG9wIiBjbGFzcz0iaXRlbV9jYXNlX29wZW4iIHN0eWxlPSJmb250LWZhbWlseTogSGVsdmV0aWNhIE5ldWUsIEhlbHZldGljYSwgQXJpYWw7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IGxpbmUtaGVpZ2h0OiBub3JtYWw7IGNvbG9yOiAjMzMzMzMzOyBmb250LXNpemU6IDE0cHg7IG1pbi13aWR0aDo5MHB4O3BhZGRpbmc6MCAwIDhweCAwOyAiPgoJCQkJCQkJCQkJCQkJCQkgICAgPHAgY2xhc3M9Iml0ZW1fY2FzZV9vcGVuIiBzdHlsZT0ibWFyZ2luOjA7Zm9udC1mYW1pbHk6IEhlbHZldGljYSBOZXVlLCBIZWx2ZXRpY2EsIEFyaWFsOyBmb250LXdlaWdodDogbm9ybWFsOyBsaW5lLWhlaWdodDogbm9ybWFsOyBjb2xvcjogIzMzMzMzMzsgdGV4dC1hbGlnbjogbGVmdDsgZm9udC1zaXplOiAxNHB4OyB3b3JkLXdyYXA6YnJlYWstd29yZDsiPgoJCQkJCQkJCQkJCQkJCQkJQ2FzZSBvcGVuZWQ6CgkJCQkJCQkJCQkJCQkJCQk8L3A+PC90ZD4KCQkJCQkJCQkJCQkJCQkJPHRkIHZhbGlnbj0idG9wIiBjbGFzcz0iaXRlbV9jYXNlX29wZW4iIHN0eWxlPSJmb250LWZhbWlseTogSGVsdmV0aWNhIE5ldWUsIEhlbHZldGljYSwgQXJpYWw7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IGxpbmUtaGVpZ2h0OiBub3JtYWw7IGNvbG9yOiAjMzMzMzMzOyBmb250LXNpemU6IDE0cHg7IHdvcmQtYnJlYWs6YnJlYWstYWxsO3BhZGRpbmc6MCAwIDhweCAwOyI+CgkJCQkJCQkJCQkJCQkJCSAgICA8cCB2YWxpZ249InRvcCIgc3R5bGU9IiBtYXJnaW46MDsgdGV4dC1kZWNvcmF0aW9uOiBub25lOyBjb2xvcjogIzMzMzMzMyFpbXBvcnRhbnQ7dGV4dC1hbGlnbjogbGVmdDsiPgoJCQkJCQkJCQkJCQkJCQkJCTggT2N0IDIwMjEKCQkJCQkJCQkJCQkJCQkJCTwvcD4KCQkJCQkJCQkJCQkJCQkJPC90ZD4KCQkJCQkJCQkJCQkJCQk8L3RyPgoJCQkJCQkJCQkJCQkJCQoJCQkJCQkJCQkJCQkJICAgIAkKCQkJCQkJCQkJCQkJCSAgICAJCgkJCQkJCQkJCQkJCQkgICAgPC90YWJsZT4KCQkJCQkJCQkJCQkgICAgCTwvdGQ+CiAgICAgICAgICAgICAgICAJCQkJCQkJCTwvdHI+CiAgICAgICAgICAgICAgICAJCQkJCQkJPC90YWJsZT4JCgkJCQkJCQkJCQkJCQogICAgICAgIAkJCQkJCQkJCTwvdGQ+CiAgICAgICAgCQkJCQkJCQk8L3RyPgogICAgICAgIAkJCQkJCQk8L3RhYmxlPgogICAgICAgIAkJCQkJCTwvdGQ+CiAgICAgICAgCQkJCQk8L3RyPgogICAgICAgIAkJCQk8L3RhYmxlPgogICAgICAgIAkJCQkKICAgICAgICAJCQkJCiAgICAJPCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KICAgICAgICAgICAgICAgICAgICAgICAgICAgPC90ZD4KICAgICAgICAgICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgICAgICAgICA8L3RhYmxlPgogICAgICAgICAgICAgCiAgICAgICAgPCFbZW5kaWZdLS0+CiAgICAgICAgCQkJCQogICAgICAgIAkJCQkJCTwvdGQ+CiAgICAgICAgCQkJCQk8L3RyPgogICAgICAgIAkJCQk8L3RhYmxlPgogICAgICAgIAkJCSA8IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgICAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgICAgICAgICA8L3RyPgogICAgICAgICAgICAgICAgICAgIDwvdGFibGU+CiAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICA8IVtlbmRpZl0tLT4JCgkJCTwvdGQ+CgkJPC90cj4KCTwvdGFibGU+CgkKIDx0YWJsZSBpZD0icGFkZGluZ0NvbnRhaW5lciIgd2lkdGg9IjEwMCUiIGJvcmRlcj0iMCIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iYmFja2dyb3VuZC1jb2xvcjogI2ZmZmZmZjsiPgogICAgPHRyPgogICAgICAgIDx0ZCB3aWR0aD0iMTAwJSI+PCEtLVtpZiAoZ3RlIG1zbyA5KXwoSUUpXT4KICAgICAgICA8dGFibGUgd2lkdGg9IjYwMCIgYWxpZ249ImNlbnRlciIgY2VsbHBhZGRpbmc9IjAiIGNlbGxzcGFjaW5nPSIwIiBib3JkZXI9IjAiPgogICAgICAgICAgICA8dHI+CiAgICAgICAgICAgICAgICA8dGQ+CiAgICAgICAgPCFbZW5kaWZdLS0+CiAgICAgICAgICAgIDx0YWJsZSB3aWR0aD0iMTAwJSIgY2VsbHNwYWNpbmc9IjAiIGNlbGxwYWRkaW5nPSIwIiBib3JkZXI9IjAiIGNsYXNzPSJtb2JXcmFwcGVyVGVtcGxhdGUiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJiYWNrZ3JvdW5kLWNvbG9yOiAjZjlmOWY5OyBib3JkZXItbGVmdDogMXB4IHNvbGlkICNkZGRkZGQ7IGJvcmRlci1yaWdodDogMXB4IHNvbGlkICNkZGRkZGQ7IG1heC13aWR0aDogNjAwcHg7Ij4KICAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgICA8dGQgc3R5bGU9IiBwYWRkaW5nOiAyMHB4OyIgd2lkdGg9IjYwMCI+CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgPC90YWJsZT4KICAgICAgICA8L3RkPgogICAgPC90cj4KPC90YWJsZT4gPHRhYmxlIHJvbGU9InByZXNlbnRhdGlvbiIgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGlkPSJmb290ZXIiIHdpZHRoPSIxMDAlIiBhbGlnbj0iY2VudGVyIiBzdHlsZT0iYm9yZGVyLWNvbGxhcHNlOiBjb2xsYXBzZSAhaW1wb3J0YW50OyBib3JkZXItc3BhY2luZzogMCAhaW1wb3J0YW50OyBib3JkZXI6IG5vbmU7IGJhY2tncm91bmQtY29sb3I6ICNmZmZmZmY7IHRhYmxlLWxheW91dDogZml4ZWQ7IG1hcmdpbjogMDsiPgo8dHI+Cjx0ZCB3aWR0aD0iMTAwJSIgdmFsaWduPSJ0b3AiIHN0eWxlPSJib3JkZXItY29sbGFwc2U6IGNvbGxhcHNlICFpbXBvcnRhbnQ7IGJvcmRlci1zcGFjaW5nOiAwICFpbXBvcnRhbnQ7IGJvcmRlcjogbm9uZTsgcGFkZGluZzogMHB4IDAgMHB4IDA7Ij4KPHRhYmxlIHJvbGU9InByZXNlbnRhdGlvbiIgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGNsYXNzPSJkZXZpY2Utd2lkdGgiIHdpZHRoPSI1ODQiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJib3JkZXItY29sbGFwc2U6IGNvbGxhcHNlICFpbXBvcnRhbnQ7IGJvcmRlci1zcGFjaW5nOiAwICFpbXBvcnRhbnQ7IGJvcmRlcjogbm9uZTsgdGFibGUtbGF5b3V0OiBmaXhlZDsiPgo8dHI+Cjx0ZCB2YWxpZ249InRvcCIgc3R5bGU9ImJvcmRlci1jb2xsYXBzZTogY29sbGFwc2UgIWltcG9ydGFudDsgYm9yZGVyLXNwYWNpbmc6IDAgIWltcG9ydGFudDsgYm9yZGVyOiBub25lOyBwYWRkaW5nOiAxNnB4IDAgMCAwOyI+CjxkaXYgaWQ9IlJlZmVyZW5jZUlkIj4KPHAgYWxpZ249ImxlZnQiIHN0eWxlPSJmb250LWZhbWlseTogTWFya2V0IFNhbnMsIEhlbHZldGljYSwgQXJpYWwsIHNhbnMtc2VyaWY7IGZvbnQtd2VpZ2h0OiBub3JtYWw7IHRleHQtYWxpZ246IGxlZnQ7IGNvbG9yOiAjMTExODIwOyBmb250LXNpemU6IDEycHg7IGxpbmUtaGVpZ2h0OiAxOHB4OyB3b3JkLXdyYXA6IGJyZWFrLXdvcmQ7IHdvcmQtYnJlYWs6IGJyZWFrLXdvcmQ7IG1hcmdpbjogMDsgbWFyZ2luOiAwIDAgMTRweDsiPiBFbWFpbCByZWZlcmVuY2UgSUQ6IFsjNjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkjXSA8L3A+IDwvZGl2Pgo8cCBhbGlnbj0ibGVmdCIgc3R5bGU9ImZvbnQtZmFtaWx5OiBNYXJrZXQgU2FucywgSGVsdmV0aWNhLCBBcmlhbCwgc2Fucy1zZXJpZjsgZm9udC13ZWlnaHQ6IG5vcm1hbDsgdGV4dC1hbGlnbjogbGVmdDsgY29sb3I6ICM3Njc2NzY7IGZvbnQtc2l6ZTogMTJweDsgbGluZS1oZWlnaHQ6IDE4cHg7IHdvcmQtd3JhcDogYnJlYWstd29yZDsgd29yZC1icmVhazogYnJlYWstd29yZDsgbWFyZ2luOiAwOyBtYXJnaW46IDAgMCAxMHB4OyI+IFdlIGRvbid0IGNoZWNrIHRoaXMgbWFpbGJveCwgc28gcGxlYXNlIGRvbid0IHJlcGx5IHRvIHRoaXMgbWVzc2FnZS4gSWYgeW91IGhhdmUgYSBxdWVzdGlvbiwgZ28gdG8gPGEgc3R5bGU9InRleHQtZGVjb3JhdGlvbjogdW5kZXJsaW5lOyBjb2xvcjogIzc2NzY3NiIgaHJlZj0iaHR0cHM6Ly9yb3Zlci5lYmF5LmNvbS9yb3Zlci8wL2UxMjU2OS5tMTg1Mi5sNjM2OS83P2V1aWQ9NjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkmYW1wO2J1PTQ0MjMwMDA3MTE1JmFtcDtzZWduYW1lPTEyNTY5JmFtcDtjcmQ9MjAyMTEwMDcxNzA4MjImYW1wO29zdWI9LTF+MSZhbXA7Y2g9b3Nnb29kJmFtcDtsb2M9aHR0cHMlM0ElMkYlMkZvY3NuZXh0LmViYXkuY29tLmF1JTJGb2NzJTJGaG9tZSZhbXA7c29qVGFncz1idT1idSxjaD1jaCxzZWduYW1lPXNlZ25hbWUsY3JkPWNyZCx1cmw9bG9jLG9zdWI9b3N1YiI+SGVscCAmYW1wOyBDb250YWN0PC9hPi4gPC9wPjxwIGFsaWduPSJsZWZ0IiBzdHlsZT0iZm9udC1mYW1pbHk6IE1hcmtldCBTYW5zLCBIZWx2ZXRpY2EsIEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXdlaWdodDogbm9ybWFsOyB0ZXh0LWFsaWduOiBsZWZ0OyBjb2xvcjogIzc2NzY3NjsgZm9udC1zaXplOiAxMnB4OyBsaW5lLWhlaWdodDogMThweDsgd29yZC13cmFwOiBicmVhay13b3JkOyB3b3JkLWJyZWFrOiBicmVhay13b3JkOyBtYXJnaW46IDA7IG1hcmdpbjogMCAwIDEwcHg7Ij4gZUJheSBzZW50IHRoaXMgbWVzc2FnZSB0byBGcmFuayBGYXJydWdpYSAoYnJhbmRiZWFzdCkuIExlYXJuIG1vcmUgYWJvdXQgPGEgaHJlZj0iaHR0cHM6Ly9yb3Zlci5lYmF5LmNvbS9yb3Zlci8wL2UxMjU2OS5tMTg1Mi5sMzE2Ny83P2V1aWQ9NjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkmYW1wO2J1PTQ0MjMwMDA3MTE1JmFtcDtzZWduYW1lPTEyNTY5JmFtcDtjcmQ9MjAyMTEwMDcxNzA4MjImYW1wO29zdWI9LTF+MSZhbXA7Y2g9b3Nnb29kJmFtcDtsb2M9aHR0cHMlM0ElMkYlMkZwYWdlcy5lYmF5LmNvbS5hdSUyRmhlbHAlMkZhY2NvdW50JTJGcHJvdGVjdGluZy1hY2NvdW50Lmh0bWwmYW1wO3NvalRhZ3M9YnU9YnUsY2g9Y2gsc2VnbmFtZT1zZWduYW1lLGNyZD1jcmQsdXJsPWxvYyxvc3ViPW9zdWIiIHN0eWxlPSJ0ZXh0LWRlY29yYXRpb246IHVuZGVybGluZTsgY29sb3I6ICM3Njc2NzYiPmFjY291bnQgcHJvdGVjdGlvbjwvYT4uIGVCYXkgaXMgY29tbWl0dGVkIHRvIHlvdXIgcHJpdmFjeS4gTGVhcm4gbW9yZSBhYm91dCBvdXIgPGEgaHJlZj0iaHR0cHM6Ly9yb3Zlci5lYmF5LmNvbS9yb3Zlci8wL2UxMjU2OS5tMTg1Mi5sMzE2OC83P2V1aWQ9NjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkmYW1wO2J1PTQ0MjMwMDA3MTE1JmFtcDtzZWduYW1lPTEyNTY5JmFtcDtjcmQ9MjAyMTEwMDcxNzA4MjImYW1wO29zdWI9LTF+MSZhbXA7Y2g9b3Nnb29kJmFtcDtsb2M9aHR0cHMlM0ElMkYlMkZwYWdlcy5lYmF5LmNvbS5hdSUyRmhlbHAlMkZwb2xpY2llcyUyRnByaXZhY3ktcG9saWN5Lmh0bWwmYW1wO3NvalRhZ3M9YnU9YnUsY2g9Y2gsc2VnbmFtZT1zZWduYW1lLGNyZD1jcmQsdXJsPWxvYyxvc3ViPW9zdWIiIHN0eWxlPSJ0ZXh0LWRlY29yYXRpb246IHVuZGVybGluZTsgY29sb3I6ICM3Njc2NzYiPnByaXZhY3kgbm90aWNlPC9hPiBhbmQgPGEgaHJlZj0iaHR0cHM6Ly9yb3Zlci5lYmF5LmNvbS9yb3Zlci8wL2UxMjU2OS5tMTg1Mi5sMzE2NS83P2V1aWQ9NjEwM2JjYTIxNDE2NDNmZjlkZjE4YTU4ZjEwNDBlYTkmYW1wO2J1PTQ0MjMwMDA3MTE1JmFtcDtzZWduYW1lPTEyNTY5JmFtcDtjcmQ9MjAyMTEwMDcxNzA4MjImYW1wO29zdWI9LTF+MSZhbXA7Y2g9b3Nnb29kJmFtcDtsb2M9aHR0cHMlM0ElMkYlMkZwYWdlcy5lYmF5LmNvbS5hdSUyRmhlbHAlMkZwb2xpY2llcyUyRnVzZXItYWdyZWVtZW50Lmh0bWwmYW1wO3NvalRhZ3M9YnU9YnUsY2g9Y2gsc2VnbmFtZT1zZWduYW1lLGNyZD1jcmQsdXJsPWxvYyxvc3ViPW9zdWIiIHN0eWxlPSJ0ZXh0LWRlY29yYXRpb246IHVuZGVybGluZTsgY29sb3I6ICM3Njc2NzYiPnVzZXIgYWdyZWVtZW50PC9hPjxzcGFuIGFyaWEtaGlkZGVuPSJ0cnVlIj4uPC9zcGFuPiA8L3A+CjxwIGFsaWduPSJsZWZ0IiBzdHlsZT0iZm9udC1mYW1pbHk6IE1hcmtldCBTYW5zLCBIZWx2ZXRpY2EsIEFyaWFsLCBzYW5zLXNlcmlmOyBmb250LXdlaWdodDogbm9ybWFsOyB0ZXh0LWFsaWduOiBsZWZ0OyBjb2xvcjogIzc2NzY3NjsgZm9udC1zaXplOiAxMnB4OyBsaW5lLWhlaWdodDogMThweDsgd29yZC13cmFwOiBicmVhay13b3JkOyB3b3JkLWJyZWFrOiBicmVhay13b3JkOyBtYXJnaW46IDA7IG1hcmdpbjogMCAwIDEwcHg7Ij4gwqkxOTk14oCMLTIwMjEgZUJheSBJbmMuLCBlQmF5IE1hcmtldHBsYWNlcyBHbWJILCBIZWx2ZXRpYXN0cmFzc2UgMTUvMTcsIDMwMDUgQmVybiwgU3dpdHplcmxhbmQgPC9wPiA8L3RkPgo8L3RyPgo8L3RhYmxlPgo8L3RkPgo8L3RyPgo8L3RhYmxlPgogPHRhYmxlIGlkPSJwYWRkaW5nQ29udGFpbmVyIiB3aWR0aD0iMTAwJSIgYm9yZGVyPSIwIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGFsaWduPSJjZW50ZXIiIHN0eWxlPSJiYWNrZ3JvdW5kLWNvbG9yOiAjZmZmZmZmOyI+CiAgICA8dHI+CiAgICAgICAgPHRkIHdpZHRoPSIxMDAlIj48IS0tW2lmIChndGUgbXNvIDkpfChJRSldPgogICAgICAgIDx0YWJsZSB3aWR0aD0iNjAwIiBhbGlnbj0iY2VudGVyIiBjZWxscGFkZGluZz0iMCIgY2VsbHNwYWNpbmc9IjAiIGJvcmRlcj0iMCI+CiAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgIDx0ZD4KICAgICAgICA8IVtlbmRpZl0tLT4KICAgICAgICAgICAgPHRhYmxlIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjI0IiBjZWxsc3BhY2luZz0iMCIgY2VsbHBhZGRpbmc9IjAiIGJvcmRlcj0iMCIgY2xhc3M9Im1vYldyYXBwZXJUZW1wbGF0ZSIgYWxpZ249ImNlbnRlciIgc3R5bGU9ImJhY2tncm91bmQtY29sb3I6ICNmZmZmZmY7IG1heC13aWR0aDogNjAwcHg7Ij4KICAgICAgICAgICAgICAgIDx0cj4KICAgICAgICAgICAgICAgICAgICA8dGQgc3R5bGU9IiBwYWRkaW5nOiAwOyIgd2lkdGg9IjYwMCI+CiAgICAgICAgICAgICAgICAKICAgICAgICAgICAgICAgICAgICA8L3RkPgogICAgICAgICAgICAgICAgPC90cj4KICAgICAgICAgICAgPC90YWJsZT4KICAgICAgICA8L3RkPgogICAgPC90cj4KPC90YWJsZT48L2JvZHk+CjwvaHRtbD4K";
        
        $html = base64_decode($_message);
        $hostName = parse_url(url('/'))['host'];

        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        foreach ($doc->getElementsByTagName('a') as $link) {

            $href = $link->getAttribute('href');

            if ( !Str::contains($href, $hostName) )
            {
                $link->setAttribute('target', '_blank');
            }

        }

        $html = $doc->saveHTML();


        $decodedMessage = $html;
        $decodedMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $decodedMessage );
        $decodedMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $decodedMessage );
        $decodedMessage = str_replace("progress-bar", "", $decodedMessage);
        $_message = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');
        $_message = $decodedMessage;


        //temporary fix for plain text, for some reason on some messages the gmail only returns text/plain and not text/html
        //if message has no html, automatically add <p> on every white/breaklines
        if ( $_message == strip_tags($_message)  )
        {
            $_message = preg_replace("/[\r\n]/","<p></br>",$_message);
        }


        echo $_message;


        dd();
        $t = new Ticket;
        dump($t->get_users_limit_not_reached());
        if ( empty( $t->get_users_limit_not_reached() ) )
        {
            dd(123);
        }
        dd();

        $users          = User::faeAgents()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
        foreach($users as $key => $user){ 
            // $user->ticketLimit;
            if ( $user->ticketLimit->first()->limit != 0 )
            {
                unset($users[$key]);
            }
        }
        dump($users->toArray());

        $limit = 1;
        $_users = User::faeAgents()->whereHas('ticketLimit', function($q) use($limit){
            $q->where('limit', '<', $limit);
        })
        ->get()->toArray();

        dd($_users);


        dd();
        // $notifications = \App\Notification::all()->take(5);
        $notifications = \App\Notification::orderBy('created_at', 'DESC')->get()->take(2);

        dd($notifications);

        foreach($notifications as $notification)
        {
            $model     = $notification->subject_type;
            $subjectId = $notification->subject_id;

            $user = User::find($notification->sender_id);
            $notification->user = $user;
            // dump( $user->roundedAvatar() );

            // dd($model::find($subjectId));
        }



        dd($notifications);

        // $userTickets = Ticket::whereHas('assignedTo', function($q){
        //                     $q->where('user_id', Auth::id());
        //                 })
        //                 ->take(20)
        //                 ->get();
        $userTickets = Ticket::excludeFacebook()->excludeEbay()->whereHas('assignedTo', function($q){
                                $q->where('user_id', Auth::id());
                            })
                            ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                            ->orderBy('thread_started_at', 'DESC')
                            ->take(20)
                            ->get();

        $allTickets = Ticket::excludeFacebook()->excludeEbay()->whereHas('assignedTo', function($q){
                            $q->where('user_id', '<>', Auth::id());
                        })
                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                        ->orderBy('thread_started_at', 'DESC')
                        ->take(20)
                        ->get();

        $data = $userTickets->merge($allTickets);
        dd($data);
        // dump($userTickets);
        dd($allTickets);

        dd();
        $send_mail = 'rodney@frankiesautoelectrics.com.au';
  
        dispatch(new App\Jobs\SendEmailJob($send_mail));


        dd();
        $users = User::query()->whereIn('id', [2,4]);
        dd($users);

        $string = '
        example.com
        www.example.com
        http://example.com
        https://example.com
        http://www.example.com
        https://www.example.com
        https://www.example2.com
        <div><img src="https://www.example.com"></div>';

        $string = preg_replace('$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', ' <a href="$1" target="_blank">$1</a> ', $string." ");
        $string = preg_replace('$(www\.[a-z0-9_./?=&#-]+)(?![^<>]*>)$i', '<a target="_blank" href="http://$1"  target="_blank">$1</a> ', $string." ");

        echo $string;
        dd();
        $attachments = GmailApi::threadDataDump('17ad1f01a005ea46');
        dd();

        $testData = 'PGh0bWw-PGhlYWQ-PG1ldGEgaHR0cC1lcXVpdj0iY29udGVudC10eXBlIiBjb250ZW50PSJ0ZXh0L2h0bWw7IGNoYXJzZXQ9dXRmLTgiPjwvaGVhZD48Ym9keSBkaXI9ImF1dG8iPkhpPGRpdj48YnI-PC9kaXY-PGRpdj5JIGNvbGxlY3RlZCBteSBvcmRlciBmcm9tIHRoZSBjb3VyaWVyIGRlcG90IHllc3RlcmRheS4gV2hlbiBJIG9wZW5lZCB0aGUgcGFja2FnaW5nIGJvdGggYm94ZXMgd2VyZSBpbnNpZGUsIGhvd2V2ZXIgdGhlIGJveCB3aXRoIHRoZSBzb2xhciBjb250cm9sbGVyIG1vbml0b3Igb25seSBoYWQgdGhlIGNhYmxlIGluc2lkZS4gVGhlIHBvc3RhZ2UgYmFnIHdhcyBpbiB0YWN0IHdoZW4gSSBjb2xsZWN0ZWQgaXQsIG5laXRoZXIgb2YgdGhlIGJveGVzIHdlcmUgc2VjdXJpdHkgc2VhbGVkL3RhcGVkLiBUaGVyZSB3YXMgbm8gcGFja2luZyBzbGlwL2ludm9pY2UgaW5zaWRlIHRoZSBiYWcuIEnigJl2ZSBhdHRhY2hlZCBhIHBob3RvIG9mIHRoZSBkZWxpdmVyeSBkb2NrZXQgb24gdGhlIGZyb250IG9mIHRoZSBiYWcuIENhbiB5b3UgcGxlYXNlIGxvb2sgaW50byB0aGUgbWlzc2luZyBtb25pdG9yLiZuYnNwOzwvZGl2PjxkaXY-PGltZyBzcmM9ImNpZDoxN0NCRjE3Ni1EQkY3LTQxMjAtOEMwRi1DQ0MxQjE5QTQyQTEtTDAtMDAxIj48YnI-PC9kaXY-PGRpdj5UaGFua3MsPC9kaXY-PGRpdj5TdGV2ZTxicj48YnI-PGRpdiBkaXI9Imx0ciI-U2VudCBmcm9tIG15IGlQaG9uZTwvZGl2PjxkaXYgZGlyPSJsdHIiPjxicj48YmxvY2txdW90ZSB0eXBlPSJjaXRlIj5PbiAzMCBKdW4gMjAyMSwgYXQgMToxMCBwbSwgRnJhbmtpZXMgQXV0byBFbGVjdHJpY3MgJmx0O3NhbGVzQGZyYW5raWVzYXV0b2VsZWN0cmljcy5jb20uYXUmZ3Q7IHdyb3RlOjxicj48YnI-PC9ibG9ja3F1b3RlPjwvZGl2PjxibG9ja3F1b3RlIHR5cGU9ImNpdGUiPjxkaXYgZGlyPSJsdHIiPu-7vw0KPGRpdiBzdHlsZT0icGFkZGluZzogMCAyMHB4IDIwcHggMjBweDsiPg0KICAgIDxoMiBzdHlsZT0iZm9udC1zaXplOjIycHg7IHBhZGRpbmctYm90dG9tOjRweDsgY29sb3I6I2NjNjYwMDsgYm9yZGVyLWJvdHRvbTpkYXNoZWQgMXB4IGdyYXkiPk9yZGVyIFN0YXR1cyBDaGFuZ2VkPC9oMj4NCg0KICAgIDxwPkhpIFN0ZXZlbjwvcD4NCg0KICAgIDxwPkFuIG9yZGVyIHlvdSByZWNlbnRseSBwbGFjZWQgb24gb3VyIHdlYnNpdGUgaGFzIGhhZCBpdHMgc3RhdHVzIGNoYW5nZWQuPC9wPg0KDQogICAgPHA-VGhlIHN0YXR1cyBvZiBvcmRlciAjODk2NDAgaXMgbm93IDxzdHJvbmc-U2hpcHBlZDwvc3Ryb25nPjwvcD4NCg0KICAgIDxoMyBzdHlsZT0iZm9udC1zaXplOjEzcHg7IGNvbG9yOiNjYzY2MDA7Ij5PcmRlciBEZXRhaWxzPC9oMz4NCiAgICA8dGFibGUgd2lkdGg9IjEwMCUiPg0KICAgICAgICA8dGJvZHk-PHRyPg0KICAgICAgICAgICAgPHRkIHN0eWxlPSJmb250LWZhbWlseTogQXJpYWw7IGZvbnQtc2l6ZTogMTNweDsgZm9udC13ZWlnaHQ6IGJvbGQ7IiBub3dyYXA9Im5vd3JhcCIgdmFsaWduPSJ0b3AiIHdpZHRoPSIyNSUiPk9yZGVyIFRvdGFsOjwvdGQ-DQogICAgICAgICAgICA8dGQgc3R5bGU9ImZvbnQtZmFtaWx5OiBBcmlhbDsgZm9udC1zaXplOiAxM3B4OyI-JDE5Ny45MCBBVUQ8L3RkPg0KICAgICAgICA8L3RyPg0KICAgICAgICANCiAgICAgICAgPHRyPg0KICAgICAgICAgICAgPHRkIHN0eWxlPSJmb250LWZhbWlseTogQXJpYWw7IGZvbnQtc2l6ZTogMTNweDsgZm9udC13ZWlnaHQ6IGJvbGQ7IiBub3dyYXA9Im5vd3JhcCIgdmFsaWduPSJ0b3AiIHdpZHRoPSIyNSUiPkRhdGUgUGxhY2VkOjwvdGQ-DQogICAgICAgICAgICA8dGQgc3R5bGU9ImZvbnQtZmFtaWx5OiBBcmlhbDsgZm9udC1zaXplOiAxM3B4OyI-Mjl0aCBKdW4gMjAyMTwvdGQ-DQogICAgICAgIDwvdHI-DQogICAgICAgIDx0cj4NCiAgICAgICAgICAgIDx0ZCBzdHlsZT0iZm9udC1mYW1pbHk6IEFyaWFsOyBmb250LXNpemU6IDEzcHg7IGZvbnQtd2VpZ2h0OiBib2xkOyIgbm93cmFwPSJub3dyYXAiIHZhbGlnbj0idG9wIiB3aWR0aD0iMjUlIj5QYXltZW50IE1ldGhvZDo8L3RkPg0KICAgICAgICAgICAgPHRkIHN0eWxlPSJmb250LWZhbWlseTogQXJpYWw7IGZvbnQtc2l6ZTogMTNweDsiPkJyYWludHJlZSAoUGF5UGFsKTwvdGQ-DQogICAgICAgIDwvdHI-DQogICAgPC90Ym9keT48L3RhYmxlPg0KDQogICAgPGgzIHN0eWxlPSJmb250LXNpemU6MTNweDsgY29sb3I6I2NjNjYwMDsiPlByb2R1Y3RzIHNoaXBwZWQ8L2gzPg0KDQo8dGFibGUgd2lkdGg9IjEwMCUiIGNlbGxzcGFjaW5nPSIwIiBjZWxscGFkZGluZz0iMCI-DQogICAgPHRib2R5Pjx0cj4NCiAgICAgICAgPHRkIHN0eWxlPSJmb250LWZhbWlseTpBcmlhbDsgZm9udC1zaXplOjEycHg7IGJhY2tncm91bmQtY29sb3I6IzAyMEI2RjsgY29sb3I6d2hpdGU7IHBhZGRpbmc6NXB4OyBmb250LXdlaWdodDpib2xkIiBub3dyYXA9IiI-Q2FydCBJdGVtczwvdGQ-DQogICAgICAgIDx0ZCBzdHlsZT0iZm9udC1mYW1pbHk6QXJpYWw7IGZvbnQtc2l6ZToxMnB4OyBiYWNrZ3JvdW5kLWNvbG9yOiMwMjBCNkY7IGNvbG9yOndoaXRlOyBwYWRkaW5nOjVweDsgZm9udC13ZWlnaHQ6Ym9sZCIgbm93cmFwPSJub3dyYXAiIGFsaWduPSJjZW50ZXIiIHdpZHRoPSIxMDAiPlNLVTwvdGQ-DQogICAgICAgIDx0ZCBzdHlsZT0iZm9udC1mYW1pbHk6QXJpYWw7IGZvbnQtc2l6ZToxMnB4OyBiYWNrZ3JvdW5kLWNvbG9yOiMwMjBCNkY7IGNvbG9yOndoaXRlOyBwYWRkaW5nOjVweDsgZm9udC13ZWlnaHQ6Ym9sZCIgbm93cmFwPSJub3dyYXAiIGFsaWduPSJjZW50ZXIiIHdpZHRoPSIxMDAiPlF0eTwvdGQ-DQogICAgPC90cj4NCiAgICAgICAgPHRyPg0KICAgICAgICA8dGQgc3R5bGU9InBhZGRpbmc6NXB4OyBmb250LXNpemU6MTJweDsgYm9yZGVyLWJvdHRvbTpzb2xpZCAxcHggI0NBQ0FDQSI-DQogICAgICAgICAgICA8c3Ryb25nPlJlZGFyYyBTUlAwMjQwIDIwQSBTb2xhciBSZWd1bGF0b3IgPC9zdHJvbmc-DQogICAgICAgIDwvdGQ-DQogICAgICAgIDx0ZCBzdHlsZT0icGFkZGluZzo1cHg7IGZvbnQtc2l6ZToxMnB4OyBib3JkZXItYm90dG9tOnNvbGlkIDFweCAjQ0FDQUNBIiB3aWR0aD0iMTAwIiBhbGlnbj0iY2VudGVyIj5TUlAwMjQwPC90ZD4NCiAgICAgICAgPHRkIHN0eWxlPSJwYWRkaW5nOjVweDsgZm9udC1zaXplOjEycHg7IGJvcmRlci1ib3R0b206c29saWQgMXB4ICNDQUNBQ0EiIHdpZHRoPSIxMDAiIGFsaWduPSJjZW50ZXIiPjE8L3RkPg0KICAgIDwvdHI-DQogICAgPHRyPg0KICAgICAgICA8dGQgc3R5bGU9InBhZGRpbmc6NXB4OyBmb250LXNpemU6MTJweDsgYm9yZGVyLWJvdHRvbTpzb2xpZCAxcHggI0NBQ0FDQSI-DQogICAgICAgICAgICA8c3Ryb25nPlJFREFSQyBTUlAwMjQwLVJNIFNvbGFyIFJlZ3VsYXRvciBSZW1vdGUgTW9uaXRvcjwvc3Ryb25nPg0KICAgICAgICA8L3RkPg0KICAgICAgICA8dGQgc3R5bGU9InBhZGRpbmc6NXB4OyBmb250LXNpemU6MTJweDsgYm9yZGVyLWJvdHRvbTpzb2xpZCAxcHggI0NBQ0FDQSIgd2lkdGg9IjEwMCIgYWxpZ249ImNlbnRlciI-U1JQMDI0MFJNPC90ZD4NCiAgICAgICAgPHRkIHN0eWxlPSJwYWRkaW5nOjVweDsgZm9udC1zaXplOjEycHg7IGJvcmRlci1ib3R0b206c29saWQgMXB4ICNDQUNBQ0EiIHdpZHRoPSIxMDAiIGFsaWduPSJjZW50ZXIiPjE8L3RkPg0KICAgIDwvdHI-DQoNCjwvdGJvZHk-PC90YWJsZT4NCg0KDQogICAgDQoNCiAgICA8aDMgc3R5bGU9ImZvbnQtc2l6ZToxM3B4OyBjb2xvcjojY2M2NjAwOyI-U2hpcG1lbnQgVHJhY2tpbmcgTnVtYmVycyAvIExpbmtzPC9oMz4NCjx1bD48bGk-PGEgaHJlZj0iIiB0YXJnZXQ9Il9ibGFuayI-TVAwMDA3MjUxNzY0PC9hPiAoRmxhdCBSYXRlKTwvbGk-PC91bD4NCg0KDQogICAgDQoNCiAgICA8cD48YSBocmVmPSJodHRwczovL2ZyYW5raWVzYXV0b2VsZWN0cmljcy5jb20uYXUvb3JkZXJzdGF0dXMucGhwIj5DbGljayBoZXJlIHRvIHZpZXcgdGhlIHN0YXR1cyBvZiB5b3VyIG9yZGVyPC9hPjwvcD4NCg0KICAgIDxwIHN0eWxlPSJtYXJnaW4tdG9wOiAxMnB4OyI-PHN0cm9uZz5GcmFua2llcyBBdXRvIEVsZWN0cmljczwvc3Ryb25nPiA8YnI-QUJOOiA1MiA2MzAgNjAxIDE4ODxicj4gPGEgaHJlZj0iaHR0cHM6Ly9mcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1LyI-aHR0cHM6Ly9mcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1Lzxicj48L2E-PC9wPg0KPHAgc3R5bGU9ImNvbG9yOiAjYjcxYzFjOyBmb250LXNpemU6IDAuODVlbSI-KkRlbGl2ZXJpZXMgdG8gTm9ydGhlcm4gVGVycml0b3J5IG1heSBoYXZlIGV4dHJhIGZyZWlnaHQgY2hhcmdlczwvcD4NCjxociBzdHlsZT0iaGVpZ2h0OiAxcHg7IGJvcmRlci1zdHlsZTogbm9uZTsgY29sb3I6ICM0NDQ7IGJhY2tncm91bmQ6ICMwMDA7IG1hcmdpbi10b3A6IDhweDsiIHNpemU9IjEiPg0KPGRpdiBzdHlsZT0iZm9udC1zaXplOiAxMXB4OyBjb2xvcjogIzQ0NDsiPkZyYW5raWVzIEF1dG8gRWxlY3RyaWNzIGlzIHBvd2VyZWQgYnkgQmlnY29tbWVyY2UuIDxhIGhyZWY9Imh0dHA6Ly93d3cuYmlnY29tbWVyY2UuY29tLyI-TGF1bmNoIHlvdXIgb3duIHN0b3JlIGZvciBmcmVlPC9hPiB3aXRoIEJpZ2NvbW1lcmNlLjwvZGl2Pg0KPC9kaXY-DQoNCg0KDQo8L2Rpdj48L2Jsb2NrcXVvdGU-PC9kaXY-PC9ib2R5PjwvaHRtbD4=';

        // $testData = trim( base64_decode($testData) );

        $testData = $this->messageEncode($testData, $attachments);
        dd($testData);

        $d = GmailApi::threadDataDump('17a56257a5fccb70');

        dd($d);

        dd();
        // $userPerformanceLog = UserPerformanceLog::where([
        //     ['ticket_id', '=', 21299],
        // ])->get();

        // dump($userPerformanceLog->first());
        // dd($userPerformanceLog->count());
        // $expire = \Carbon\Carbon::now()->addMinutes(10);

        // $tickets = Cache::remember('ticket', $expire, function() {
        //     return Ticket::orderBy('subject')->get();
        // });

        // $tickets = Ticket::orderBy('subject')->get();

        // return $tickets;
        dd( HOUR(TIMEDIFF('2021-06-03 11:03:26', '2021-06-03 11:03:11')) );

        // $file = \App\File::where('name', '2a25595f4ea1-a53ba5css7d7.png')->exists();
        // dd($file);
        // $assignedTicket = AssignedTicket::where('ticket_id', 2430)->get();
        // dump($assignedTicket->count());
        // dd();
        dd(GmailApi::createTickets());dd();
        
        // $file = File::whereIn('id', [63,64])->get();
        // dd($file);
        $attachments = GmailApi::threadDataDump('179bfcb5b068ce5e'); dd();
        // $attachments = GmailApi::threadDataDump('178f2547e07cd8c5');
        // $attachments = GmailApi::threadDataDump('17982fdeafeef909');
        // dd( Storage::url('public/attachments/pexels-isuru-udesh-mangala-2556988-c3780fa7aa.jpg') );
        // dd(URL::to('/'));

        // $filePath   = URL::to('/') . Storage::url('public/attachments/pexels-isuru-udesh-mangala-2556988-c3780fa7aa.jpg');
        $filePath   = URL::to('/') . Storage::url('public/attachments/file-sample_100kB-da14d27564.doc');
        echo '<a href="'.$filePath.'" download>Download</a>';
        dd($filePath);
        $headers = array(
            'Content-Type:' => 'image/jpg',
        );
        
        $d = Storage::disk('public/attachments')->get('pexels-isuru-udesh-mangala-2556988-c3780fa7aa.jpg');
        dd($d);

        // dd( Storage::get('public/attachments/pexels-isuru-udesh-mangala-2556988-c3780fa7aa.jpg') );
        // dd( Storage::path('public/attachments/pexels-isuru-udesh-mangala-2556988-c3780fa7aa.jpg') );
        $filename = 'pexels-isuru-udesh-mangala-2556988-c3780fa7aa.jpg';
        return response()->download(Storage::path('public/attachments/pexels-isuru-udesh-mangala-2556988-c3780fa7aa.jpg'), $filename);
        dd();
        // dd($attachments);

        // $attachments = GmailApi::threadDataDump('178b50ed432d5f85');
        // dd($attachments);
        // $attachments = GmailApi::threadDataDump('178c87e31f101a40'); //marcelo
        // dump( '-attachments-' );
        // dump($attachments);
        // foreach ( $attachments as $attachment )
        // {
        //     dump($attachment['attachment_id']);
        //     echo '<img src="data:image/jpg;base64,'.$attachment['data'].'">';
        //     dd($attachment['data']);
        // }

        // $msg = $this->messageEncode('PGh0bWw-PGJvZHkgc3R5bGU9ImZvbnQtZmFtaWx5OiBBcmlhbDsgZm9udC1zaXplOiAxMnB4OyI-DQoJPGRpdiBzdHlsZT0icGFkZGluZzogMCAyMHB4IDIwcHggMjBweDsiPg0KCQk8aDIgc3R5bGU9ImZvbnQtc2l6ZToyMnB4OyBoZWlnaHQ6MzBweDsgY29sb3I6I2NjNjYwMDsgYm9yZGVyLWJvdHRvbTpkYXNoZWQgMXB4IGdyYXkiPidzcmt0MzI0NjIyQG1haWwucnUnIHN1Ym1pdHRlZCB0aGUgZm9ybSBmcm9tIHlvdXIgJ0NvbnRhY3QgRm9ybScgcGFnZTwvaDI-DQoNCgkJPHA-QSB1c2VyIGhhcyBzdWJtaXR0ZWQgdGhlIGNvbnRhY3QgZm9ybSBvbiB5b3VyIHN0b3JlLjwvcD4NCg0KCQk8aDMgc3R5bGU9ImZvbnQtc2l6ZToxM3B4OyBjb2xvcjojY2M2NjAwOyI-SGVyZSBhcmUgdGhlaXIgZGV0YWlsczo8L2gzPg0KDQoJCTx0YWJsZSB3aWR0aD0iMTAwJSIgY2VsbHBhZGRpbmc9IjQiIGNlbGxzcGFjaW5nPSIwIiBib3JkZXI9IjAiPg0KCQkJPHRyPg0KCTx0ZCBzdHlsZT0iZm9udC1mYW1pbHk6IEFyaWFsOyBmb250LXNpemU6IDEycHg7IiB3aWR0aD0iMjUlIj48c3Ryb25nPkZ1bGwgTmFtZTo8L3N0cm9uZz48L3RkPiA8dGQgc3R5bGU9ImZvbnQtZmFtaWx5OiBBcmlhbDsgZm9udC1zaXplOiAxMnB4OyI-RGVsb3Jlc0JyYWljPC90ZD4NCjwvdHI-DQoNCjx0cj4NCgk8dGQgc3R5bGU9ImZvbnQtZmFtaWx5OiBBcmlhbDsgZm9udC1zaXplOiAxMnB4OyIgd2lkdGg9IjI1JSI-PHN0cm9uZz5FbWFpbCBBZGRyZXNzOjwvc3Ryb25nPjwvdGQ-IDx0ZCBzdHlsZT0iZm9udC1mYW1pbHk6IEFyaWFsOyBmb250LXNpemU6IDEycHg7Ij5zcmt0MzI0NjIyQG1haWwucnU8L3RkPg0KPC90cj4NCg0KPHRyPg0KCTx0ZCBzdHlsZT0iZm9udC1mYW1pbHk6IEFyaWFsOyBmb250LXNpemU6IDEycHg7IiB3aWR0aD0iMjUlIj48c3Ryb25nPlBob25lIE51bWJlcjo8L3N0cm9uZz48L3RkPiA8dGQgc3R5bGU9ImZvbnQtZmFtaWx5OiBBcmlhbDsgZm9udC1zaXplOiAxMnB4OyI-ODI0OTY0MTIxODE8L3RkPg0KPC90cj4NCg0KDQoJCQk8dHI-DQoJCQkJPHRkIHN0eWxlPSJmb250LWZhbWlseTogQXJpYWw7IGZvbnQtc2l6ZTogMTJweDsiIGNvbHNwYW49IjIiPiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQkNGA0YXQsNC90LPQtdC70YzRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCQ0YHRgtGA0LDRhdCw0L3QuCZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0JHQsNGA0L3QsNGD0LvQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0JHQtdC70LPQvtGA0L7QtNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQkdGA0Y_QvdGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0JLQtdC70LjQutC-0Lwg0J3QvtCy0LPQvtGA0L7QtNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQstC-INCS0LvQsNC00LjQvNC40YDQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LLQviDQktC70LDQtNC40LLQvtGB0YLQvtC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCS0L7Qu9Cz0L7Qs9GA0LDQtNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQktC-0YDQvtC90LXQttC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQldC60LDRgtC10YDQuNC90LHRg9GA0LPQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0JjQttC10LLRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCY0LLQsNC90L7QstC-Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQmNGA0LrRg9GC0YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQmtCw0LvRg9Cz0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCa0LDQt9Cw0L3QuCZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0JrQsNC70LjQvdC40L3Qs9GA0LDQtNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQmtC10LzQtdGA0L7QstC-Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQmtC40YDQvtCy0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCa0L7RgdGC0YDQvtC80LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCa0YDQsNGB0L3QvtC00LDRgNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQmtGA0LDRgdC90L7Rj9GA0YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQmtGD0YDRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCb0LjQv9C10YbQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQnNCw0LPQvdC40YLQvtCz0L7RgNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0JzQvtGB0LrQstC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQnNGD0YDQvNCw0L3RgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCd0LDQsdC10YDQtdC20L3Ri9GFINCn0LXQu9C90LDRhSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0J3QuNC20L3QtdC8INCd0L7QstCz0L7RgNC-0LTQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0J3QuNC20L3QtdC8INCi0LDQs9C40LvQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0J3QvtCy0L7QutGD0LfQvdC10YbQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQndC-0LLQvtGB0LjQsdC40YDRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCe0LzRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCe0YDQu9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQntGA0LXQvdCx0YPRgNCz0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCf0LXRgtGA0L7Qt9Cw0LLQvtC00YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQn9C10L3Qt9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQn9C10YDQvNC4Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQoNC-0YHRgtC-0LLQtSDQvdCwINCU0L7QvdGDJmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQoNGP0LfQsNC90LgmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCh0LDQvNCw0YDQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KHQsNC90LrRgiDQn9C10YLQtdGA0LHRg9GA0LPQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KHQn9CxJmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQodCw0YDQsNC90YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQodCw0YDQsNGC0L7QstC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQodC10LLQsNGB0YLQvtC_0L7Qu9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQodC80L7Qu9C10L3RgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCh0L7Rh9C4Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQodGC0LDQstGA0L7Qv9C-0LvQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KHRg9GA0LPRg9GC0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCh0YLQtdGA0LvQuNGC0LDQvNCw0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KLQstC10YDQuCZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KLQvtC70YzRj9GC0YLQuCZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KLQvtC80YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQotGD0LvQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KLRjtC80LXQvdC4Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQo9C70YzRj9C90L7QstGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KPRhNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQpdCw0LHQsNGA0L7QstGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KfQtdC70Y_QsdC40L3RgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCn0LXRgNC10L_QvtCy0YbQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KfQtdCx0L7QutGB0LDRgNCw0YUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCv0YDQvtGB0LvQsNCy0LvQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KHQsNC70LDQstCw0YLQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0J3QuNC20L3QtdC60LDQvNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0JDQt9C-0LLQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0J3QvtCy0L7Rh9C10YDQutCw0YHRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCi0LDQs9Cw0L3RgNC-0LPQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC60L7QvNC_0YzRjtGC0LXRgNC-0LIg0LIg0KLQsNC80LHQvtCy0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCh0LjQvNGE0LXRgNC-0L_QvtC70LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCR0LDQu9Cw0YjQuNGF0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCb0Y7QsdC10YDRhtCw0YUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCc0YvRgtC40YnQsNGFJmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0LrQvtC80L_RjNGO0YLQtdGA0L7QsiDQsiDQn9C-0LTQvtC70YzRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQutC-0LzQv9GM0Y7RgtC10YDQvtCyINCyINCl0LjQvNC60LDRhSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQkNGA0YXQsNC90LPQtdC70YzRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0JDRgdGC0YDQsNGF0LDQvdC4Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCR0LDRgNC90LDRg9C70LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0JHQtdC70LPQvtGA0L7QtNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCR0YDRj9C90YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCS0LXQu9C40LrQvtC8INCd0L7QstCz0L7RgNC-0LTQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQstC-INCS0LvQsNC00LjQvNC40YDQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQstC-INCS0LvQsNC00LjQstC-0YHRgtC-0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQktC-0LvQs9C-0LPRgNCw0LTQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQktC-0YDQvtC90LXQttC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCV0LrQsNGC0LXRgNC40L3QsdGD0YDQs9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCY0LbQtdCy0YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCY0LLQsNC90L7QstC-Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCY0YDQutGD0YLRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0JrQsNC70YPQs9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCa0LDQt9Cw0L3QuCZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQmtCw0LvQuNC90LjQvdCz0YDQsNC00LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0JrQtdC80LXRgNC-0LLQviZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQmtC40YDQvtCy0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0JrQvtGB0YLRgNC-0LzQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQmtGA0LDRgdC90L7QtNCw0YDQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQmtGA0LDRgdC90L7Rj9GA0YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCa0YPRgNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQm9C40L_QtdGG0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQnNCw0LPQvdC40YLQvtCz0L7RgNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQnNC-0YHQutCy0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0JzRg9GA0LzQsNC90YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCd0LDQsdC10YDQtdC20L3Ri9GFINCn0LXQu9C90LDRhSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQndC40LbQvdC10Lwg0J3QvtCy0LPQvtGA0L7QtNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCd0LjQttC90LXQvCDQotCw0LPQuNC70LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0J3QvtCy0L7QutGD0LfQvdC10YbQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCd0L7QstC-0YHQuNCx0LjRgNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQntC80YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCe0YDQu9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCe0YDQtdC90LHRg9GA0LPQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQn9C10YLRgNC-0LfQsNCy0L7QtNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQn9C10L3Qt9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCf0LXRgNC80LgmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KDQvtGB0YLQvtCy0LUg0L3QsCDQlNC-0L3RgyZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQoNGP0LfQsNC90LgmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KHQsNC80LDRgNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCh0LDQvdC60YIg0J_QtdGC0LXRgNCx0YPRgNCz0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KHQn9CxJmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCh0LDRgNCw0L3RgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KHQsNGA0LDRgtC-0LLQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQodC10LLQsNGB0YLQvtC_0L7Qu9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCh0LzQvtC70LXQvdGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQodC-0YfQuCZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQodGC0LDQstGA0L7Qv9C-0LvQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQodGD0YDQs9GD0YLQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQodGC0LXRgNC70LjRgtCw0LzQsNC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KLQstC10YDQuCZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQotC-0LvRjNGP0YLRgtC4Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCi0L7QvNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQotGD0LvQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQotGO0LzQtdC90LgmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KPQu9GM0Y_QvdC-0LLRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KPRhNC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCl0LDQsdCw0YDQvtCy0YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCn0LXQu9GP0LHQuNC90YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCn0LXRgNC10L_QvtCy0YbQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQp9C10LHQvtC60YHQsNGA0LDRhSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQr9GA0L7RgdC70LDQstC70LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KHQsNC70LDQstCw0YLQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQndC40LbQvdC10LrQsNC80YHQutC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCQ0LfQvtCy0LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0J3QvtCy0L7Rh9C10YDQutCw0YHRgdC60LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0KLQsNCz0LDQvdGA0L7Qs9C1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCi0LDQvNCx0L7QstC1Jmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCh0LjQvNGE0LXRgNC-0L_QvtC70LUmbHQ7L2EmZ3Q7IDxiciAvPg0KJmx0O2EgaHJlZj1odHRwczovL2JpdC5seS8zcmdCY25WJmd0O9Cg0LXQvNC-0L3RgiDQvdC-0YPRgtCx0YPQutC-0LIg0LIg0JHQsNC70LDRiNC40YXQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQm9GO0LHQtdGA0YbQsNGFJmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCc0YvRgtC40YnQsNGFJmx0Oy9hJmd0OyA8YnIgLz4NCiZsdDthIGhyZWY9aHR0cHM6Ly9iaXQubHkvM3JnQmNuViZndDvQoNC10LzQvtC90YIg0L3QvtGD0YLQsdGD0LrQvtCyINCyINCf0L7QtNC-0LvRjNGB0LrQtSZsdDsvYSZndDsgPGJyIC8-DQombHQ7YSBocmVmPWh0dHBzOi8vYml0Lmx5LzNyZ0JjblYmZ3Q70KDQtdC80L7QvdGCINC90L7Rg9GC0LHRg9C60L7QsiDQsiDQpdC40LzQutCw0YUmbHQ7L2EmZ3Q7PC90ZD4NCgkJCTwvdHI-DQoJCTwvdGFibGU-DQoNCgkJPHAgc3R5bGU9Im1hcmdpbi10b3A6IDEycHg7Ij48c3Ryb25nPkZyYW5raWVzIEF1dG8gRWxlY3RyaWNzPC9zdHJvbmc-IDxiciAvPkFCTjogNTIgNjMwIDYwMSAxODg8YnIgLz4gPGEgaHJlZj0iaHR0cHM6Ly9mcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1LyI-aHR0cHM6Ly9mcmFua2llc2F1dG9lbGVjdHJpY3MuY29tLmF1LzxiciAvPjwvYT48L3A-DQo8aHIgc3R5bGU9ImhlaWdodDogMXB4OyBib3JkZXItc3R5bGU6IG5vbmU7IGNvbG9yOiAjNDQ0OyBiYWNrZ3JvdW5kOiAjMDAwOyBtYXJnaW4tdG9wOiA4cHg7IiBzaXplPSIxIiAvPg0KPGRpdiBzdHlsZT0iZm9udC1zaXplOiAxMXB4OyBjb2xvcjogIzQ0NDsiPkZyYW5raWVzIEF1dG8gRWxlY3RyaWNzIGlzIHBvd2VyZWQgYnkgQmlnY29tbWVyY2UuIDxhIGhyZWY9Imh0dHA6Ly93d3cuYmlnY29tbWVyY2UuY29tLyI-TGF1bmNoIHlvdXIgb3duIHN0b3JlIGZvciBmcmVlPC9hPiB3aXRoIEJpZ2NvbW1lcmNlLjwvZGl2Pg0KCTwvZGl2Pg0KPC9ib2R5PjwvaHRtbD4NCg==', $attachments);


        // dump($msg);
        // $msg = base64_decode($msg);
        // echo $msg;

        // GmailApi::threadDataDump('178c9c234d272fe1'); //brandon
        // $files = File::whereIn('name', ['1dc67e8efc50.png','28b172d29e65.png'])->get();
        // dd($files);
        // $file = File::find(6);
        // $fileSize = Storage::size("public/attachments/".$file->name);
        // dd($fileSize);
        // $fileContent = Storage::get("public/attachments/".$file->name);
        // $fileContent = base64_encode($d);
        // $mimeType = Storage::mimeType("public/attachments/".$file->name);
        // dd($mimeType);

        // $filePath   = asset("storage/attachments/".$file->name);
        // $finfo      = finfo_open(FILEINFO_MIME_TYPE);               // return mime type ala mimetype extension
        // $mimeType   = finfo_file($finfo, $filePath);
        // dd($mimeType);
        // dd(base_path().'/public');
        // $tmp = Array('file_ids' => Array(1,2));
        // $tmp = json_encode($tmp);
        // dump($tmp);
        // $tmp = json_decode($tmp, true);
        // dump($tmp['file_ids']);

        // dd(GmailApi::createTickets());

        // $ticket = Ticket::where('thread_id', '178a0bdc3ac911f9')->first();
        // $_messageId = $ticket->messages->last()->message_id

        // $users[0] = Array(
        //             "id" => 9,
        //             "name" => "Anne Michelle Sudla",
        //             "email" => "anne@frankiesautoelectrics.com.au",
        //             "avatar" => "af32b857fb79cb5f.jpg",
        //             "email_verified_at" => "2021-02-28 19:57:05",
        //             "last_seen" => "2021-03-09 11:37:02",
        //             "is_online" => 0,
        //             "created_at" => "2021-02-28 19:57:05",
        //             "updated_at" => "2021-03-09 11:37:02",
        //             "deleted_at" => null,
        //             "tickets_count" => 4,
        //             ); 
        // $users[1] = Array(
        //             "id" => 8,
        //             "name" => "Ma. Christina Crisostomo",
        //             "email" => "christina@frankiesautoelectrics.com.au",
        //             "avatar" => "d0a34df4282dd7c5.jpg",
        //             "email_verified_at" => "2021-02-28 19:57:05",
        //             "last_seen" => "2021-03-15 17:25:53",
        //             "is_online" => 0,
        //             "created_at" => "2021-02-28 19:57:05",
        //             "updated_at" => "2021-03-15 17:25:53",
        //             "deleted_at" => null,
        //             "tickets_count" => 11,
        //             ); 
        // $users[2] = Array(
        //             "id" => 9,
        //             "name" => "Anne Michelle Sudla",
        //             "email" => "anne@frankiesautoelectrics.com.au",
        //             "avatar" => "af32b857fb79cb5f.jpg",
        //             "email_verified_at" => "2021-02-28 19:57:05",
        //             "last_seen" => "2021-03-09 11:37:02",
        //             "is_online" => 0,
        //             "created_at" => "2021-02-28 19:57:05",
        //             "updated_at" => "2021-03-09 11:37:02",
        //             "deleted_at" => null,
        //             "tickets_count" => 4,
        //             ); 

        // dump($users);

        // $user = current($users);

        // dump($user);

        // dump(next($users));
        // dump(next($users));
        // dump(next($users));
        // dump(next($users));


        // dd(EbayAPI::syncEbayMessages());
        // $users = User::faeAgents()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();

        // dd($users);
        // $d = EmailSupportAddress::active()->whereIn('email', ['rodneydcro3@gmail.com'])->get()->count();
        // $user  = $users->first();
        // dump($user);
        // $user = $user->next();
        // dump($user);
        // $user = $user->next();
        // dump($user);

        // dd(GmailApi::createTickets());
        // $ticketModel = new Ticket;
        // $file = GmailApi::threadDataDump('1786c448b4015fd7');

        // $ticketModel->saveAttachment($file);
        // $attachments = GmailApi::threadDataDump('1785daa02e483056');
        // $randString = random_bytes(6);
        // $randString = bin2hex($randString);
        // $file=fopen('../storage/app/attachments/'.$randString.'.'.$attachments['fileExt'],'w+');
        // fwrite($file, base64_decode($attachments['data'])); //1786169253b95cbe or 1785daa02e483056 or 17866b609aa9d2a8
        // fclose($file);


        // $this->createDoc('Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');
        // $this->createDoc( GmailApi::threadDataDump('1785daa02e483056') );

        // dd( GmailApi::threadDataDump('17849378ff9aab4b') );

        dd();

        // dump( User::faeAgents()->get() );
        // dd( User::ebayAgents()->get() );
        // $dateString = '2021-03-16 22:13:05.0 +00:00';
        
        // $date = explode('.', $dateString);
        // $date = explode('+', current($date));
        // $date = trim( current($date) );

        // $date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date, 'US/Arizona');
        
        // dump( $date->setTimezone('Australia/Sydney') );
        // dump( $date->format('Y-m-d H:i:s') );

        // dd();
        
        // Log::info('Initiating Cron Job: Minute SyncEbayTickets');

        // $start = date('h:i:s');
        
        // EbayAPI::syncEbayMessages();

        // $end = date('h:i:s');
        
        // Log::info('Sync Ebay Tickets Start - End Time: '.$start.' - '.$end);
        dd();

        // dump( User::find(1)->first() );
        // dd( Auth::user()->first() );
        // dd(EbayApi::getMessageToDump());


        // if ( $user->hasCustomPages() )
        // {
        //     $uri = '/tickets/custom/ebay';
        //     $slug = substr($uri, strrpos($uri, '/') + 1);

        //     $userCustompage = UserCustomPage::where([
        //                             'user_id' => $user->id,
        //                             'slug'    => $slug
        //                         ])->first();
        //     if ( $userCustompage->count() )
        //     {

        //         $tickets        = '';
        //         $pageConditions = $userCustompage->pageConditions()->orderBy('operator', 'ASC')->get();
                
        //         foreach ( $pageConditions as $key => $pageCondition )
        //         {

        //             //setup eloquent on first iteration
        //             if ( $key === 0 )
        //             {
        //                 $tickets = Ticket::where($pageCondition->filter.'_id', $pageCondition->filter_id);
        //             }
        //             else
        //             {

        //                 if ( $pageCondition->operator == 'AND' )
        //                 {
        //                     $tickets->where($pageCondition->filter.'_id', $pageCondition->filter_id);
        //                 }
        //                 else
        //                 {
        //                     $tickets->orWhere($pageCondition->filter.'_id', $pageCondition->filter_id);
        //                 }

        //             }
                    
        //         }

        //         $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);
        //         dd($tickets);
        //     }
        // }

        // $authGmail = new AuthGmail;

        //for authorizing gmail account
        // if ( $authGmail->credentials_in_browser() )
        // {
        //     $emailSupportIdToAuthAsSuffix = $request->session()->get('email_support_id_to_auth');

        //     //generate the token
        //     $authGmail->create_client($emailSupportIdToAuthAsSuffix);

        //     //update email support address to active after generating token
        //     $_id                         = (int)$request->session()->get('email_support_id_to_auth');
        //     $emailSupportAddress         = EmailSupportAddress::find( $_id );
        //     $emailSupportAddress->status = EmailSupportAddress::STATUS_ACTIVE;
        //     $emailSupportAddress->save();
            
        //     $request->session()->forget('email_support_id_to_auth');

        //     return redirect('channels/email')->with('authSuccess', 'Authorization complete.');
        // }
        // else
        // {
        //     return view('/');
        // }

        // $phpWord = new \PhpOffice\PhpWord\PhpWord();


        // $section = $phpWord->addSection();


        // $description = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

        // $section->addText($description);

        // $file = 'helloworlddd.docx';
        // header("Content-Description: File Transfer");
        // header('Content-Disposition: attachment; filename="' . $file . '"');
        // header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        // header('Content-Transfer-Encoding: binary');
        // header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        // header('Expires: 0');


        // $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        
        // // $objWriter->save("php://output");
        // $objWriter->save( storage_path($file));

        // Log::info('Initiating Cron Job: Minute SyncEbayTickets');

        // $start = date('h:i:s');
        
        // EbayAPI::syncEbayMessages();
        
        // $end = date('h:i:s');

        // Log::info('Sync Ebay Tickets Start - End Time: '.$start.' - '.$end);

        //
        // Log::info('Initiating Cron Job: Minute SyncTickets');

        // $start = date('h:i:s');
        
        // GmailApi::createTickets();
        
        // // EbayAPI::syncEbayMessages();
        
        // $end = date('h:i:s');

        // Log::info('Sync Tickets Start - End Time: '.$start.' - '.$end);
        
        // dd(Chat::doesntHave('chatMessages')->get());

        // $_image = \Storage::get('images/chat8be1d4ec12621d91.png');

        // $_image = base64_encode($_image);

        // echo '<img src="data:image/jpeg;charset=utf-8;base64,'.$_image.'" width="64">';
        // dd();
        // $user = User::find( Auth::id() );

        // dd( $user->chatLogs );

        //get the ids of chat that u dont have msg and not empty messages
        // $chats = Chat::whereHas('chatMessages', function($a){
        //     $a->where('from', 'agent');
        //     $a->where('user_id', 1);
        // })->get('id')->toArray();

        // $chatIds = Array();
        // foreach( array_values($chats) as $val )
        // {
        //     array_push($chatIds, $val['id']);
        // }

        // dd($chatIds);

        // $users = User::all();
        // foreach ($users as $user) {
        //     if (Cache::has('user-is-online-' . $user->id))
        //     {
        //         Log::info(Cache::get('user-is-online-' . $user->id));
        //         echo $user->name . " is online. Last seen: " . \Carbon\Carbon::parse($user->last_seen)->diffForHumans() . " <br>";
        //     }
        //     else
        //     {
        //         echo $user->name . " is offline. Last seen: " . \Carbon\Carbon::parse($user->last_seen)->diffForHumans() . " <br>";
        //     }
        // }

        // dd($customer->exists);
        // $chat = Chat::find(1);
        // dd($chat->chatMessages);
        // Facebook::facebookInstance();
        // Facebook::syncConversation(3827);
        // Facebook::syncConversations();
        // Facebook::sendFacebookMessage();
        // Facebook::syncAccountPages();
        
        dd();

        $fb = new \Facebook\Facebook([
            'app_id' => '765592150703088',
            'app_secret' => 'af06bdf5e2018a0830b1eaad693c7477',
            'default_graph_version' => 'v2.4',
          ]);
        
        // $accessToken = \Session::put('fb-access-token', '');

        if(empty(\Session::get('fb-access-token'))) {
            $cookieLife  = 30;
            // $cookieValue = str_random(32);
            $cookieValue = Str::random(32);
            $validUntil  = \Carbon\Carbon::now()->addMinutes($cookieLife)->timestamp;
            \Cookie::queue(\Cookie::make('facebook_auth', $cookieValue, $cookieLife));
            cache(['fb-auth-cookie' => "{$cookieValue}|{$validUntil}"], $cookieLife);
            // dump(\Cache::get('fb-auth-cookie'));
            $permissions = ['email', 'user_posts', 'pages_read_engagement', 'pages_read_user_content', 'pages_messaging']; // optional
            $callback = 'https://phplaravel-370483-1521810.cloudwaysapps.com/channels/facebook/oauth';
            $helper = $fb->getRedirectLoginHelper();
            $loginUrl = $helper->getLoginUrl($callback, $permissions);
            return redirect($loginUrl)->withCookies(\Cookie::getQueuedCookies());
        }

        $accessToken = \Session::get('fb-access-token');
        dump($accessToken);
        dump($fb->get('/3504449106297880', $accessToken));
        $response = $fb->get('/me/accounts', $accessToken);
        dump($response);

        $page_body = json_decode($response->getBody());
        dump($page_body);

        //fetching messages
        foreach($page_body->data as $page_data) {

			$conversation_body = json_decode($fb->get('/'.$page_data->id.'/conversations', $page_data->access_token)->getBody());
            dump($conversation_body);

            foreach ( $conversation_body->data as $conversationData )
            {
                $message_body = json_decode($fb->get('/'.$conversationData->id.'/messages?fields=message,created_time,from,id,tags,to', $page_data->access_token)->getBody());
                dump($message_body);
                
                // foreach ( $message_body->data as $message )
                // {
                //     $message = json_decode($fb->get('/'.$message->id.'/message', $page_data->access_token)->getBody());
                //     dump($message);
                // }
            }

            // $fb->post('/me/messages',
            // ['messaging_type' => 'RESPONSE',
            // 'recipient' => ['id' => '3504449106297880'],
            // 'message' => ['text' => 'Hello World']]
            // ,$page_data->access_token);

        }
        

        dd();




        //////////////////


        $t3 = CustomPageCondition::where(function($q){

            $q->all();
    
        })
        ->orWhere(function($q){

            $q->any();
    
        })
        ->orderBy('operator', 'ASC')
        ->get();

        dd($t3);
        // $customPage = UserCustomPage::find(11);


        // dump($customPage->pageConditions);

        // $test = CustomPageCondition::where(function($q){

        //     $q->where('status', 1);

        // });

        // dump($test);

        dd();
        // dd( CustomPageCondition::find(null) );
        // $customPageCondition = CustomPageCondition::where('custom_page_id', 11)->whereNotIn('id',[7,11])->get();

        // dd($customPageCondition);

        dd();

        EbayAPI::syncEbayMessages();
        dd();
        
        $user = Auth::user();

        $userPages = $user->customPages()->get();

        foreach ( $userPages as $userPage )
        {
            dump($userPage->pageConditions);
        }

        dd(123);
        // $emailSupportAddresses = EmailSupportAddress::active()->first();
        // dd( $emailSupportAddresses->id );
        // dd();


        $service = new Services\TradingService([
		    'authToken'   => 'AgAAAA**AQAAAA**aAAAAA**lBayXw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYajCpWFoQ6dj6x9nY+seQ**F4MGAA**AAMAAA**bopzuZ7Kj/0PAH4p1T+p+Pva/nEpCJn8yYqin4zXJ9s0AsW4oTXUTCzU0IWufA7vYiwLiUxHxPuxe8RBitysImQc0SzAcDIbHI+24ke52YvsmgKITeqR7jMGMMv0ePsVYu8Q7pVNeERCu8mO1G2bAxOTeA9vmsr7uWSkaexOTrnHwAbQQ8RHNNqWccEPCeFI3z97F1c60It21ZvOwMyRMkxGPbcbEd73QzzSk1KD8JnI/glLK12tzqy3Z5IgnV4He/C3ATTenMji0qwISMyi0YgI1SIBXqIb0ZI9wRXZmjoFzZCUrydjyZY2NnWkuhdcoEOsHCWVJWWEAnJpso3wpiYdVq9BAum9R98J8vE7BN5/GMdkOP97ZcucupsbtDRCyMiCJn67KNyaO5IdWQ4/rFg1Ur79dzUvCo9yIx/Za4daYQPy8CE6fBBLKQgXbGgYu9n436YgJJ+2R/Jb6C4f27XCb9MfQoXKBiAzxf26YudqI8eJfgMHNDggP7OY/eleAp64gdTChOW1dFYCQI9+hWeYJRkok0X4REH4p+x+I2u5kGjDvazH5FvV59IRRuC3dbDSi6bewAsMCGtOafwJGegfzn9DzvzSNWvNzeBhkbyrKgpHMJnQ7qEsMXzSdTcb7em7PXXP83wROo1NsDfixFKUGzE6o/W+jIIH+7gdmjOoAFx4BruTLQwwvXeJ8bSCIejW8BuyTFdwgN7KVLyl0Vxei42eJ97TqfkGVMga1o8bph7+R2F1uqSaEsdWGVfk',
		    'credentials' => [
		    	'appId'  => 'Frankies-Intellig-PRD-8e6527e8b-e809e2fb',
		    	'certId' => 'b2d75e6f-fed5-49da-8d31-ae37661d91fa',
		    	'devId'  => 'PRD-e6527e8b8e38-f353-4341-b037-3371'],
		    'siteId' => Constants\SiteIds::GB
        ]);


        $request                             = new Types\GetMyMessagesRequestType();
        $request->DetailLevel                = ['ReturnHeaders'];
        $request->Pagination                 = new Types\PaginationType();
        $request->Pagination->EntriesPerPage = 1;
        $request->Pagination->PageNumber     = 10;
        $response                            = $service->getMyMessages($request);
        $messages                            = $response->Messages;
        dd($messages);

        //

        $str = base64_decode( Message::find(902)->message );

        $doc = new \DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($str);

        $selector = new \DOMXPath($doc);
        // foreach($selector->query('//table[ @id="MessageHistory1" ]') as $e ) {
        // foreach($selector->query('//table[contains(attribute::id, "MessageHistory")]') as $e ) {
        //     // $e->parentNode->removeChild($e);
        // }


        // foreach($selector->query('//a') as $a ) {

        //     if ( $a->nodeValue == 'Reply' )
        //     {
        //         // $b->parentNode->removeChild($b);
        //         $a->parentNode->removeChild($a);
        //     }
        // }

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

        dump( base64_encode($html) );

        dd();

        // EbayAPI::syncEbayMessages();
        // dd();
        
        // $service = new Services\TradingService([
		//     'authToken'   => 'AgAAAA**AQAAAA**aAAAAA**lBayXw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYajCpWFoQ6dj6x9nY+seQ**F4MGAA**AAMAAA**bopzuZ7Kj/0PAH4p1T+p+Pva/nEpCJn8yYqin4zXJ9s0AsW4oTXUTCzU0IWufA7vYiwLiUxHxPuxe8RBitysImQc0SzAcDIbHI+24ke52YvsmgKITeqR7jMGMMv0ePsVYu8Q7pVNeERCu8mO1G2bAxOTeA9vmsr7uWSkaexOTrnHwAbQQ8RHNNqWccEPCeFI3z97F1c60It21ZvOwMyRMkxGPbcbEd73QzzSk1KD8JnI/glLK12tzqy3Z5IgnV4He/C3ATTenMji0qwISMyi0YgI1SIBXqIb0ZI9wRXZmjoFzZCUrydjyZY2NnWkuhdcoEOsHCWVJWWEAnJpso3wpiYdVq9BAum9R98J8vE7BN5/GMdkOP97ZcucupsbtDRCyMiCJn67KNyaO5IdWQ4/rFg1Ur79dzUvCo9yIx/Za4daYQPy8CE6fBBLKQgXbGgYu9n436YgJJ+2R/Jb6C4f27XCb9MfQoXKBiAzxf26YudqI8eJfgMHNDggP7OY/eleAp64gdTChOW1dFYCQI9+hWeYJRkok0X4REH4p+x+I2u5kGjDvazH5FvV59IRRuC3dbDSi6bewAsMCGtOafwJGegfzn9DzvzSNWvNzeBhkbyrKgpHMJnQ7qEsMXzSdTcb7em7PXXP83wROo1NsDfixFKUGzE6o/W+jIIH+7gdmjOoAFx4BruTLQwwvXeJ8bSCIejW8BuyTFdwgN7KVLyl0Vxei42eJ97TqfkGVMga1o8bph7+R2F1uqSaEsdWGVfk',
		//     'credentials' => [
		//     	'appId'  => 'Frankies-Intellig-PRD-8e6527e8b-e809e2fb',
		//     	'certId' => 'b2d75e6f-fed5-49da-8d31-ae37661d91fa',
		//     	'devId'  => 'PRD-e6527e8b8e38-f353-4341-b037-3371'],
		//     'siteId' => Constants\SiteIds::GB
        // ]);

        // $request = new Types\GetMyMessagesRequestType();

		// $request->DetailLevel = ['ReturnHeaders'];
		// $request->Pagination  = new Types\PaginationType();
		// $request->Pagination->EntriesPerPage = 1500;
  		// $request->Pagination->PageNumber     = 1;
        // $response = $service->getMyMessages($request);
        // $messages = $response->Messages;

        // foreach( $messages->Message as $message )
        // {
        //     // 0 => "132946465852"
        //     // 1 => "132944997812"
        //     // 2 => "132891339382"
        //     if ( $message->Sender == 'theodorval-0' )
        //     {
        //         dd($message);
        //         // $msg = base64_encode($message->Text);
        //         // dump($msg);
        //     }
        //         // dd($message);
        // }
        
        // dd();

        // $ebayApi    = new EbayAPI;
        // $headers    = EbayAPI::getHeaders();
        // $messageIDs = EbayAPI::getMessageIds($headers);

        // $chunkedIDs = array_chunk($messageIDs, 10);
        
        // foreach ( $chunkedIDs as $chunkedID )
        // {

        //     $messages = EbayAPI::getMessages($chunkedID);
        //     // dd($messages);
        //     foreach( $messages->Messages->Message as $message )
        //     {
        //         // 0 => "132946465852"
        //         // 1 => "132944997812"
        //         // 2 => "132891339382"
        //         if ( $message->ExternalMessageID == '2205606188016' )
        //         {
        //             dump($message->ExternalMessageID);
        //             // dd($message);
        //             // $msg = rtrim(strtr(base64_encode($message->Text), '+/', '-_'), '=');
        //             // dump($message->Content);

        //             $msg = $message->Text;
        //             dump(base64_encode($message->Text));
        //             $doc = new \DOMDocument('1.0', 'UTF-8');
        //             @$doc->loadHTML($msg);

        //             $selector = new \DOMXPath($doc);
        //             // foreach($selector->query('//table[ @id="MessageHistory1" ]') as $e ) {
        //             foreach($selector->query('//table[contains(attribute::id, "MessageHistory")]') as $e ) {
        //                 $e->parentNode->removeChild($e);
        //             }

        //             $html = $doc->saveHTML($doc->documentElement);

        //             $links = $doc->getElementsByTagName("a"); // DOMNodeList Object

        //             foreach($links as $item) { // DOMElement Object

        //                 $href =  $item->getAttribute("href");
        //                 if ( $item->nodeValue == 'Reply' )
        //                 {
        //                     $item->parentNode->removeChild($item->parentNode);
        //                 }

        //             }

        //             // $doc->saveHTML();
        //             // dump(base64_encode($html));
        //             dd($doc);

        //             // $msg = base64_encode($message->Text);
        //             // dump($msg);
        //             // $msg = base64_encode($message->Content);
        //             // dump($msg);
        //             // $msg = base64_decode($msg);
        //             // preg_match('/<div id="UserInputtedText">(.*?)<\/div>/s', $message->Text, $match);

        //             // if ( !isset( $match[1] ) || empty( $match[1] ) )
        //             // {
        //             //     //another method to extract actual user input from message
        //             //     dump('empty match');
        //             // }
        //             // else
        //             // {
        //             //     dump('has match');
        //             //     dump($match[1]);
        //             // }
        //         }

        //     }

        // }

        // dd();
        // $service = new Services\OAuthService([
        //     // 'credentials' => $config['sandbox']['credentials'],
        //     'credentials' => [
        //         'appId' => 'rodneyca-frankies-PRD-86c5a7b3e-a0521337',
        //         'certId' => 'a42c7c85-1bef-4dc4-8049-15f9509a067a',
        //         'devId' => 'PRD-6c5a7b3e9188-481a-48ba-ae01-00e8'
        //     ],
        //     'ruName'      => 'rodney_caisip-rodneyca-franki-yaieky',
        //     'sandbox'     => false,
        // ]);
        // dump($service);
        // $response = $service->getAppToken();

        // dd($response);

        // $request = new Types\GetUserTokenRestRequest();
        // $authorization = 'Basic '.base64_encode('rodneyca-frankies-PRD-86c5a7b3e-a0521337:PRD-6c5a7b3e9188-481a-48ba-ae01-00e8');
        // $request->code = $authorization;
        /**
         * Send the request.
         */
        // $response = $service->getUserToken($request);
        // dd($response);

        // dd();

        $cookieLife = 30;
        $cookieValue = Str::random(32);
        $validUntil = \Carbon\Carbon::now()->addMinutes($cookieLife)->timestamp;
        \Cookie::queue(\Cookie::make('ebay_auth', $cookieValue, $cookieLife));
        cache(['ebay-auth-cookie' => "{$cookieValue}|{$validUntil}"], $cookieLife);
        // dump(\Cache::get('ebay-auth-cookie'));

        $config = [
            'client_id' => 'rodneyca-frankies-PRD-86c5a7b3e-a0521337',
            'client_secret' => 'PRD-6c5a7b3e9188-481a-48ba-ae01-00e8',
            // 'ru_name' => 'rodney_caisip-rodneyca-franki-yaieky',
            'ru_name' => 'rodney_caisip-rodneyca-franki-entsb',
            // 'login_url' => 'https://auth.ebay.com/oauth2/authorize',
            'login_url' => 'https://signin.ebay.com/ws/eBayISAPI.dll',
            'oauth_url' => 'https://api.ebay.com/identity/v1/oauth2/token',
            'api_scopes' => ['https://api.ebay.com/oauth/api_scope'],
        ];

        $service = new \DTS\eBaySDK\Trading\Services\TradingService([
		    'credentials' => [
		    	'appId'  => $config['client_id'],
		    	'certId' => $config['client_secret'],
		    	'devId'  => 'a42c7c85-1bef-4dc4-8049-15f9509a067a'],
		    'siteId' => Constants\SiteIds::GB
        ]);

        $request = new \DTS\eBaySDK\Trading\Types\GetSessionIDRequestType();
        $request->RuName = $config['ru_name'];

        $url = $config['login_url'];
        $url .= '?SignIn&runame='.$config['ru_name'];
        $url .= '&SessID='.$service->getSessionID($request)->SessionID;

        Session::put('SessionID', $service->getSessionID($request)->SessionID);

        // $url .= '?client_id='.$config['client_id'];
        // $url .= '&response_type=code';
        // $url .= '&redirect_uri='.urlencode($config['ru_name']);
        // $url .= '&scope='.implode(' ', $config['api_scopes']);

        return redirect($url)->withCookies(\Cookie::getQueuedCookies());
        // return redirect($url);
        dd($url);

        // $service = new \DTS\eBaySDK\Trading\Services\TradingService([
		    // 'authorization'   => 'AgAAAA**AQAAAA**aAAAAA**lBayXw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYajCpWFoQ6dj6x9nY+seQ**F4MGAA**AAMAAA**bopzuZ7Kj/0PAH4p1T+p+Pva/nEpCJn8yYqin4zXJ9s0AsW4oTXUTCzU0IWufA7vYiwLiUxHxPuxe8RBitysImQc0SzAcDIbHI+24ke52YvsmgKITeqR7jMGMMv0ePsVYu8Q7pVNeERCu8mO1G2bAxOTeA9vmsr7uWSkaexOTrnHwAbQQ8RHNNqWccEPCeFI3z97F1c60It21ZvOwMyRMkxGPbcbEd73QzzSk1KD8JnI/glLK12tzqy3Z5IgnV4He/C3ATTenMji0qwISMyi0YgI1SIBXqIb0ZI9wRXZmjoFzZCUrydjyZY2NnWkuhdcoEOsHCWVJWWEAnJpso3wpiYdVq9BAum9R98J8vE7BN5/GMdkOP97ZcucupsbtDRCyMiCJn67KNyaO5IdWQ4/rFg1Ur79dzUvCo9yIx/Za4daYQPy8CE6fBBLKQgXbGgYu9n436YgJJ+2R/Jb6C4f27XCb9MfQoXKBiAzxf26YudqI8eJfgMHNDggP7OY/eleAp64gdTChOW1dFYCQI9+hWeYJRkok0X4REH4p+x+I2u5kGjDvazH5FvV59IRRuC3dbDSi6bewAsMCGtOafwJGegfzn9DzvzSNWvNzeBhkbyrKgpHMJnQ7qEsMXzSdTcb7em7PXXP83wROo1NsDfixFKUGzE6o/W+jIIH+7gdmjOoAFx4BruTLQwwvXeJ8bSCIejW8BuyTFdwgN7KVLyl0Vxei42eJ97TqfkGVMga1o8bph7+R2F1uqSaEsdWGVfk',
		    // 'authorization'   => $response->access_token,
		    // 'credentials' => [
		    // 	'appId'  => 'Frankies-Intellig-PRD-8e6527e8b-e809e2fb',
		    // 	'certId' => 'b2d75e6f-fed5-49da-8d31-ae37661d91fa',
		    // 	'devId'  => 'PRD-e6527e8b8e38-f353-4341-b037-3371'],
		//     'credentials' => [
		//     	'appId'  => $config['client_id'],
		//     	'certId' => $config['client_secret'],
		//     	'devId'  => 'a42c7c85-1bef-4dc4-8049-15f9509a067a'],
		//     'siteId' => Constants\SiteIds::GB
        // ]);

        // $request = new \DTS\eBaySDK\Trading\Types\GetSessionIDRequestType();
        // $request->RuName = $config['ru_name'];
        // dump($service->getSessionID($request)->SessionID);
        // dd($service->getSessionID($request));

        // $request = new HttpRequest();
        // $request->setUrl('https://api.ebay.com/identity/v1/oauth2/token');
        // $request->setMethod(HTTP_METH_POST);

        // $request->setHeaders(array(
        // 'Postman-Token' => '784bae81-d5e6-402e-8d51-1c2c7d4220ad',
        // 'cache-control' => 'no-cache',
        // 'Authorization' => 'Basic xxxxxxxxxxxxxxxxxxxxxx==',
        // 'Accept' => 'application/json',
        // 'Content-Type' => 'application/x-www-form-urlencoded'
        // ));

        // $request->setContentType('application/x-www-form-urlencoded');
        // $request->setPostFields(array(
        // 'grant_type' => 'authorization_code',
        // 'code' => 'v^1.1xxxxxxxxxxxxxxxxxx',
        // 'redirect_uri' => 'MACxxxxxxxxxxxxxx',
        // 'undefined' => null
        // ));

        // try {
        // $response = $request->send();

        // echo $response->getBody();
        // } catch (HttpException $ex) {
        // echo $ex;
        // }

        // 'credentials' => [
        //     'appId' => 'rodneyca-frankies-PRD-86c5a7b3e-a0521337',
        //     'certId' => 'a42c7c85-1bef-4dc4-8049-15f9509a067a',
        //     'devId' => 'PRD-6c5a7b3e9188-481a-48ba-ae01-00e8'
        // ],

        // $ebayApi = new EbayAPI;

        // dd(EbayAPI::getHeaders());

        // phpinfo();
        // dd();

        // dd(Storage::disk('local')->url('credentials/credentials18.json'));

        // dd(asset('storage/credentials/credentials18.json'));
        // dd(Storage::disk('local')->url('credentials/credentials18.json'));
        // dd($exists = Storage::disk('local')->exists('credentials/credentials18.json'));

        // $path = 'credentials/credentials18.json';
        // $full_path = Storage::path($path);


        // dd($full_path);
        // $base64 = base64_encode(Storage::get($path));
        // $image_data = 'data:'.mime_content_type($full_path) . ';base64,' . $base64;

        // dd(Storage::url('credentials18.json'));
        // dd(Storage::files('credentials'));
        // dd(Storage::getVisibility('credentials/credentials18.json'));

        // $visibility = Storage::getVisibility('credentials/credentials18.json');

        // Storage::setVisibility('file.jpg', 'public');


        // cookie('test', 'testvar', 5);
        // dd($request->cookie::get('test'));


        // $this->set_connection();

        // $this->go();
        
        // $this->is_t = false;

        // dd($this->is_t);

        // dd(base_path());
        $service = new Services\TradingService([
		    'authToken'   => 'AgAAAA**AQAAAA**aAAAAA**kXuVXw**nY+sHZ2PrBmdj6wVnY+sEZ2PrA2dj6AFkYajCpWFoQ6dj6x9nY+seQ**F4MGAA**AAMAAA**bopzuZ7Kj/0PAH4p1T+p+Pva/nEpCJn8yYqin4zXJ9s0AsW4oTXUTCzU0IWufA7vYiwLiUxHxPuxe8RBitysImQc0SzAcDIbHI+24ke52YvsmgKITeqR7jMGMMv0ePsVYu8Q7pVNeERCu8mO1G2bAxOTeA9vmsr7uWSkaexOTrnHwAbQQ8RHNNqWccEPCeFI3z97F1c60It21ZvOwMyRMkxGPbcbEd73QzzSk1KD8JnI/glLK12tzqy3Z5IgnV4He/C3ATTenMji0qwISMyi0YgI1SIBXqIb0ZI9wRXZmjoFzZCUrydjyZY2NnWkuhdcoEOsHCWVJWWEAnJpso3wpiYdVq9BAum9R98J8vE7BN5/GMdkOP97ZcucupsbtDRCyMiCJn67KNyaO5IdWQ4/rFg1Ur79dzUvCo9yIx/Za4daYQPy8CE6fBBLKQgXbGgYu9n436YgJJ+2R/Jb6C4f27XCb9MfQoXKBiAzxf26YudqI8eJfgMHNDggP7OY/eleAp64gdTChOW1dFYCQI9+hWeYJRkok0X4REH4p+x+I2u5kGjDvazH5FvV59IRRuC3dbDSi6bewAsMCGtOafwJGegfzn9DzvzSNWvNzeBhkbyrKgpHMJnQ7qEsMXzSdTcb7em7PXXP83wROo1NsDfixFKUGzE6o/W+jIIH+7gdmjOoAFx4BruTLQwwvXeJ8bSCIejW8BuyTFdwgN7KVLyl0Vxei42eJ97TqfkGVMga1o8bph7+R2F1uqSaEsdWGVfk',
		    'credentials' => [
		    	'appId'  => 'Frankies-Intellig-PRD-8e6527e8b-e809e2fb',
		    	'certId' => 'b2d75e6f-fed5-49da-8d31-ae37661d91fa',
		    	'devId'  => 'PRD-e6527e8b8e38-f353-4341-b037-3371'],
		    'siteId' => Constants\SiteIds::GB
        ]);

        $request = new Types\GetMyMessagesRequestType();

		$request->DetailLevel = ['ReturnHeaders'];
		$request->Pagination  = new Types\PaginationType();
		$request->Pagination->EntriesPerPage = 10;
  		$request->Pagination->PageNumber     = 1;
        $response = $service->getMyMessages($request);
        $messages = $response->Messages;
        dd($messages);
        // foreach ( $messages->Message as $key => $message ) {
        //     if ( $message->Sender == 'theodorval-0' )
        //     {
        //         dd($key);
        //         $test = rtrim(strtr(base64_encode($message->Text), '+/', '-_'), '=');
        //         $test = base64_decode($test);
        //         dd($test);
        //     }
        // }
        // dd();

        $messages = $response->Messages;
		foreach($messages->Message as $message) {
            if ( $message->Sender == 'theodorval-0' )
            {
                $messageIDs[] = $message->MessageID;
            }
        }
        dd($messageIDs);

        $request = new Types\GetMyMessagesRequestType();
		$request->DetailLevel = ['ReturnMessages'];
		$request->MessageIDs  = new Types\MyMessagesMessageIDArrayType();
		$request->MessageIDs->MessageID = $messageIDs;
        $response = $service->getMyMessages($request);
        // dd($response);

        foreach ( $response->Messages->Message as $key => $message ) {
            if ( $message->Sender == 'theodorval-0' )
            {
                $test = rtrim(strtr(base64_encode($message->Text), '+/', '-_'), '=');
                // $test = base64_decode($test);
                dd($test);
            }
        }
        dd();
        
        // $request = new Types\AddMemberMessageRTQRequestType();
        // $request->MemberMessage = new Types\MemberMessageType();
        // $request->MemberMessage->ParentMessageID = 'THREAD_ID';
        // $request->MemberMessage->RecipientID = ['theodorval-0'];
        // $request->MemberMessage->Body = 'MESSAGE';
        // $response = $service->addMemberMessageRTQ($request);
        
        // dd($response);

        foreach ( $response->Messages->Message as $message ) {
            if ( $message->Sender == 'theodorval-0' )
            {
                dd($message->Content);
                $_content = rtrim(strtr(base64_encode($message->Content), '+/', '-_'), '=');

                if ( GmailApi::threadExists($message->ExternalMessageID) === false )
                {
                    $createTicket = Ticket::create([
                        'thread_id'         => $message->ExternalMessageID,
                        'history_id'        => 0,
                        'channel_id'        => TicketOrigin::ORIGIN_EBAY,                // temporarily for EBAY
                        'origin_id'         => TicketOrigin::ORIGIN_EBAY,
                        'subject'           => $message->Subject,
                        'snippet'           => $message->Subject,
                        'requester'         => $message->Sender,
                        'thread_started_at' => $message->ReceiveDate->format('Y-m-d h:i:s'),
                        'status_id'         => TicketStatus::STATUS_UNASSIGNED,
                        'priority_id'       => TicketPriority::PRIORITY_NORMAL, // default for now
                        'type_id'           => TicketType::TYPE_QUESTION, // default for now
                        'created_at'        => \Carbon\Carbon::now(),
                        'updated_at'        => \Carbon\Carbon::now(),
                    ]);

                    $_ticketId = $createTicket->id;
                }

                if ( GmailApi::messageExists($message->MessageID) === false )
                {
                    $storeMessage = Message::create([
                        // 'ticket_id'     => $createTicket->id,
                        'ticket_id'     => Ticket::where('thread_id', $message->ExternalMessageID)->first()->id,
                        'message_id'    => $message->MessageID,
                        'message'       => rtrim(strtr(base64_encode($message->Content), '+/', '-_'), '='),
                        'from'          => $message->Sender,
                        'internal_date' => $message->ReceiveDate->format('Y-m-d h:i:s'),
                        'created_at'    => \Carbon\Carbon::now(),
                        'updated_at'    => \Carbon\Carbon::now(),
                    ]);
                }

            }
        }

        dd();
        // GmailApi::createTickets();

        // $str = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Quisque sagittis purus sit amet volutpat consequat mauris nunc congue. Eros donec ac odio tempor orci dapibus ultrices. Netus et malesuada fames ac turpis egestas maecenas pharetra. Rhoncus dolor purus non enim praesent elementum facilisis leo vel. Enim praesent elementum facilisis leo vel fringilla est ullamcorper. Eget nulla facilisi etiam dignissim diam quis enim lobortis scelerisque. Placerat orci nulla pellentesque dignissim. Eu mi bibendum neque egestas congue quisque egestas. Gravida neque convallis a cras semper auctor neque vitae tempus. Adipiscing at in tellus integer feugiat scelerisque varius.

        // At urna condimentum mattis pellentesque id nibh tortor id aliquet. Feugiat in fermentum posuere urna nec tincidunt. Pharetra magna ac placerat vestibulum lectus mauris. Tristique sollicitudin nibh sit amet commodo nulla facilisi nullam. Nisl condimentum id venenatis a condimentum vitae. Orci dapibus ultrices in iaculis nunc sed augue. Cursus in hac habitasse platea dictumst quisque sagittis purus. Congue eu consequat ac felis donec et odio pellentesque diam. Neque aliquam vestibulum morbi blandit cursus. Ullamcorper dignissim cras tincidunt lobortis. Ut ornare lectus sit amet. Vestibulum lectus mauris ultrices eros in. Gravida rutrum quisque non tellus orci ac. Phasellus faucibus scelerisque eleifend donec pretium vulputate sapien nec sagittis. Id porta nibh venenatis cras sed felis eget.
        
        // Interdum velit euismod in pellentesque massa. Vitae justo eget magna fermentum iaculis eu. At lectus urna duis convallis convallis tellus id interdum. Scelerisque purus semper eget duis. Laoreet non curabitur gravida arcu ac tortor dignissim. Massa id neque aliquam vestibulum morbi blandit cursus risus at. Urna duis convallis convallis tellus id. Pharetra magna ac placerat vestibulum lectus mauris ultrices. Consectetur purus ut faucibus pulvinar. Arcu cursus euismod quis viverra nibh cras pulvinar mattis. Elementum eu facilisis sed odio morbi quis. Eget nullam non nisi est sit amet facilisis magna etiam. Scelerisque viverra mauris in aliquam sem fringilla ut morbi tincidunt. Eu facilisis sed odio morbi. Nunc sed velit dignissim sodales ut eu sem integer vitae. Ante in nibh mauris cursus mattis molestie. Tortor posuere ac ut consequat semper viverra nam libero justo. Eu tincidunt tortor aliquam nulla facilisi.
        
        // Suspendisse interdum consectetur libero id faucibus nisl tincidunt eget nullam. Accumsan lacus vel facilisis volutpat est velit. Ultrices eros in cursus turpis massa tincidunt dui ut ornare. Nec tincidunt praesent semper feugiat. Ac felis donec et odio pellentesque diam volutpat commodo sed. Ultrices neque ornare aenean euismod elementum nisi. Id semper risus in hendrerit gravida. Enim sed faucibus turpis in eu mi bibendum. Nunc sed augue lacus viverra vitae. Sapien nec sagittis aliquam malesuada bibendum arcu. In hac habitasse platea dictumst quisque.
        
        // Ut etiam sit amet nisl. Dignissim convallis aenean et tortor. In pellentesque massa placerat duis ultricies lacus sed turpis. Commodo quis imperdiet massa tincidunt nunc pulvinar. Amet consectetur adipiscing elit ut aliquam purus sit amet. Id volutpat lacus laoreet non curabitur gravida arcu ac tortor. Eget duis at tellus at urna condimentum mattis pellentesque. Nec nam aliquam sem et. Facilisi nullam vehicula ipsum a arcu cursus vitae. Viverra nibh cras pulvinar mattis nunc sed blandit libero volutpat. Dui ut ornare lectus sit amet est. Pretium fusce id velit ut tortor. Sapien nec sagittis aliquam malesuada bibendum arcu. Aliquam ultrices sagittis orci a scelerisque purus semper eget duis. Pulvinar neque laoreet suspendisse interdum consectetur libero id faucibus nisl. Imperdiet sed euismod nisi porta lorem mollis aliquam ut. Velit aliquet sagittis id consectetur purus ut faucibus pulvinar. Porttitor lacus luctus accumsan tortor posuere ac ut consequat. Mus mauris vitae ultricies leo integer.
        
        // Augue eget arcu dictum varius duis at. Habitasse platea dictumst quisque sagittis. Ullamcorper sit amet risus nullam eget felis eget nunc lobortis. Urna nunc id cursus metus aliquam eleifend. In ante metus dictum at tempor. Venenatis a condimentum vitae sapien pellentesque habitant morbi tristique senectus. Ultrices in iaculis nunc sed augue lacus viverra. Vel elit scelerisque mauris pellentesque pulvinar pellentesque. Diam phasellus vestibulum lorem sed risus. Aliquam etiam erat velit scelerisque in dictum non. At ultrices mi tempus imperdiet. At in tellus integer feugiat. Metus dictum at tempor commodo ullamcorper. Tincidunt id aliquet risus feugiat in ante metus. Cursus metus aliquam eleifend mi in nulla. Faucibus interdum posuere lorem ipsum dolor sit amet consectetur adipiscing. Eu sem integer vitae justo eget magna fermentum iaculis.
        
        // Sagittis orci a scelerisque purus semper eget. Viverra nam libero justo laoreet. Sollicitudin aliquam ultrices sagittis orci a scelerisque. Suspendisse sed nisi lacus sed viverra tellus in hac. Commodo odio aenean sed adipiscing diam donec adipiscing. Ultricies mi quis hendrerit dolor magna eget est lorem. Urna porttitor rhoncus dolor purus non enim praesent elementum facilisis. Amet purus gravida quis blandit. Massa id neque aliquam vestibulum morbi blandit. Venenatis tellus in metus vulputate eu scelerisque felis imperdiet. Mattis pellentesque id nibh tortor id aliquet lectus. Nunc sed id semper risus. Consectetur adipiscing elit ut aliquam. Lobortis scelerisque fermentum dui faucibus in ornare quam.
        
        // Amet commodo nulla facilisi nullam vehicula ipsum. Nec dui nunc mattis enim ut. In est ante in nibh. Ut morbi tincidunt augue interdum velit euismod in pellentesque massa. Nullam eget felis eget nunc lobortis mattis aliquam faucibus. Ac felis donec et odio pellentesque. Neque volutpat ac tincidunt vitae semper quis. Mauris ultrices eros in cursus turpis massa tincidunt dui ut. Elit at imperdiet dui accumsan sit amet nulla facilisi morbi. Urna porttitor rhoncus dolor purus non enim praesent. Enim blandit volutpat maecenas volutpat blandit aliquam. Ullamcorper malesuada proin libero nunc consequat interdum. A diam sollicitudin tempor id. Suspendisse in est ante in nibh mauris. Lacinia at quis risus sed vulputate odio ut enim. Donec pretium vulputate sapien nec sagittis aliquam malesuada bibendum. Feugiat nibh sed pulvinar proin. Nunc lobortis mattis aliquam faucibus purus. Faucibus a pellentesque sit amet porttitor.
        
        // Risus in hendrerit gravida rutrum quisque non tellus orci ac. Eleifend quam adipiscing vitae proin sagittis nisl rhoncus. Netus et malesuada fames ac turpis egestas integer eget. Augue neque gravida in fermentum et sollicitudin ac orci. Consectetur purus ut faucibus pulvinar elementum integer. Volutpat blandit aliquam etiam erat velit scelerisque in dictum. Amet purus gravida quis blandit. Suspendisse sed nisi lacus sed viverra tellus. Velit aliquet sagittis id consectetur purus ut faucibus. Feugiat in ante metus dictum at tempor commodo. Sagittis eu volutpat odio facilisis.
        
        // Purus semper eget duis at tellus at urna condimentum. Neque aliquam vestibulum morbi blandit cursus risus at. Nullam vehicula ipsum a arcu cursus. Tincidunt vitae semper quis lectus nulla at volutpat diam. Amet porttitor eget dolor morbi non. Phasellus faucibus scelerisque eleifend donec pretium vulputate sapien. Parturient montes nascetur ridiculus mus mauris vitae ultricies leo integer. Quis ipsum suspendisse ultrices gravida dictum fusce ut. Non odio euismod lacinia at quis risus sed vulputate. Ornare arcu odio ut sem nulla pharetra diam sit. Orci dapibus ultrices in iaculis nunc sed augue lacus. Elementum curabitur vitae nunc sed. In nulla posuere sollicitudin aliquam. Nisl suscipit adipiscing bibendum est ultricies integer quis. Id porta nibh venenatis cras sed felis eget velit. Iaculis at erat pellentesque adipiscing commodo elit at. At tellus at urna condimentum mattis pellentesque id nibh tortor. Arcu dictum varius duis at consectetur lorem donec massa sapien. Sed libero enim sed faucibus turpis. Neque egestas congue quisque egestas diam.
        
        // Porttitor rhoncus dolor purus non enim praesent elementum. Sapien et ligula ullamcorper malesuada proin libero nunc consequat. Pellentesque habitant morbi tristique senectus et netus et. Fusce ut placerat orci nulla pellentesque dignissim enim sit. Mi eget mauris pharetra et ultrices. In metus vulputate eu scelerisque felis imperdiet proin. Accumsan tortor posuere ac ut consequat semper viverra. Dignissim enim sit amet venenatis urna. Eleifend quam adipiscing vitae proin sagittis nisl rhoncus. Elit duis tristique sollicitudin nibh. Purus non enim praesent elementum facilisis leo. Eget sit amet tellus cras adipiscing enim eu. Gravida quis blandit turpis cursus in. In tellus integer feugiat scelerisque varius morbi enim. Pellentesque habitant morbi tristique senectus et. Eget nunc scelerisque viverra mauris in. Parturient montes nascetur ridiculus mus mauris vitae ultricies. Dictum fusce ut placerat orci nulla pellentesque.
        
        // Diam ut venenatis tellus in metus vulputate eu. Lacus sed viverra tellus in hac habitasse platea. Rhoncus urna neque viverra justo nec ultrices dui sapien. Interdum velit laoreet id donec ultrices tincidunt. Enim tortor at auctor urna nunc id cursus metus. Sed felis eget velit aliquet sagittis. Eleifend mi in nulla posuere sollicitudin aliquam ultrices sagittis. Luctus accumsan tortor posuere ac ut. Elementum tempus egestas sed sed risus pretium. Egestas diam in arcu cursus euismod. Fermentum iaculis eu non diam phasellus vestibulum lorem sed risus. Morbi tristique senectus et netus et malesuada fames. Quis vel eros donec ac odio tempor. Mi proin sed libero enim sed faucibus.
        
        // Quis eleifend quam adipiscing vitae proin sagittis nisl rhoncus. Pulvinar mattis nunc sed blandit libero volutpat sed. Ac turpis egestas integer eget aliquet. Pharetra diam sit amet nisl suscipit adipiscing bibendum est ultricies. Morbi leo urna molestie at. Enim nunc faucibus a pellentesque sit amet porttitor. Laoreet id donec ultrices tincidunt arcu non sodales neque sodales. Gravida arcu ac tortor dignissim convallis. Convallis a cras semper auctor neque vitae. Orci porta non pulvinar neque laoreet.
        
        // Adipiscing vitae proin sagittis nisl rhoncus mattis rhoncus urna. Pulvinar neque laoreet suspendisse interdum consectetur. Tellus at urna condimentum mattis pellentesque. Posuere ac ut consequat semper viverra nam libero justo laoreet. Velit dignissim sodales ut eu sem integer vitae. Diam volutpat commodo sed egestas. Libero nunc consequat interdum varius sit amet mattis vulputate enim. Quam id leo in vitae turpis massa sed elementum. Viverra nam libero justo laoreet sit amet cursus sit. Tincidunt eget nullam non nisi est sit amet. Nibh nisl condimentum id venenatis. Morbi tristique senectus et netus et malesuada fames.
        
        // Orci phasellus egestas tellus rutrum tellus. Ipsum dolor sit amet consectetur adipiscing elit duis. Ultrices vitae auctor eu augue ut lectus arcu bibendum. Et malesuada fames ac turpis. Phasellus egestas tellus rutrum tellus. Fermentum dui faucibus in ornare quam viverra orci sagittis eu. Turpis egestas sed tempus urna et pharetra. Magnis dis parturient montes nascetur ridiculus mus mauris vitae ultricies. Urna nec tincidunt praesent semper feugiat nibh sed. Fringilla phasellus faucibus scelerisque eleifend donec pretium vulputate. Vel eros donec ac odio tempor orci dapibus. Nunc mi ipsum faucibus vitae. Blandit aliquam etiam erat velit scelerisque. Arcu odio ut sem nulla pharetra. Lorem dolor sed viverra ipsum nunc aliquet bibendum enim facilisis. Viverra nibh cras pulvinar mattis nunc sed blandit libero volutpat.
        
        // Eget felis eget nunc lobortis mattis aliquam faucibus purus. Cursus vitae congue mauris rhoncus aenean vel elit scelerisque. Fusce id velit ut tortor pretium viverra suspendisse potenti nullam. Varius quam quisque id diam vel quam elementum pulvinar etiam. Euismod lacinia at quis risus sed vulputate odio ut enim. Sodales ut etiam sit amet nisl. Ut ornare lectus sit amet. Auctor augue mauris augue neque gravida in fermentum et. Mattis ullamcorper velit sed ullamcorper morbi tincidunt. Fermentum et sollicitudin ac orci phasellus egestas tellus rutrum tellus. Vitae et leo duis ut diam quam. Blandit aliquam etiam erat velit scelerisque in dictum non. Turpis egestas integer eget aliquet nibh praesent tristique. Faucibus interdum posuere lorem ipsum dolor sit. Habitasse platea dictumst vestibulum rhoncus est pellentesque. Phasellus vestibulum lorem sed risus. Varius quam quisque id diam vel quam elementum pulvinar. Nec feugiat nisl pretium fusce id velit ut tortor pretium. Malesuada bibendum arcu vitae elementum. Nisl pretium fusce id velit ut tortor pretium.
        
        // Eget mauris pharetra et ultrices neque ornare aenean. Vel eros donec ac odio tempor. Tincidunt lobortis feugiat vivamus at augue eget arcu. Feugiat pretium nibh ipsum consequat. Id neque aliquam vestibulum morbi. Laoreet non curabitur gravida arcu ac. Amet purus gravida quis blandit turpis cursus. Vel pretium lectus quam id leo. Pharetra magna ac placerat vestibulum. Adipiscing elit ut aliquam purus. Amet massa vitae tortor condimentum lacinia quis. Orci porta non pulvinar neque laoreet suspendisse.
        
        // A diam maecenas sed enim ut sem viverra aliquet. Eget duis at tellus at urna condimentum. Mattis nunc sed blandit libero. Volutpat sed cras ornare arcu dui vivamus arcu felis. Consectetur adipiscing elit duis tristique sollicitudin nibh sit. Sit amet consectetur adipiscing elit pellentesque habitant morbi tristique. Turpis egestas pretium aenean pharetra magna. Sed egestas egestas fringilla phasellus faucibus scelerisque eleifend. Sed risus ultricies tristique nulla. Egestas sed sed risus pretium quam vulputate. Ut venenatis tellus in metus vulputate eu. Vel elit scelerisque mauris pellentesque pulvinar pellentesque habitant morbi.
        
        // Eros in cursus turpis massa tincidunt dui ut ornare lectus. Et ligula ullamcorper malesuada proin libero nunc. Ultrices sagittis orci a scelerisque purus semper eget. Malesuada proin libero nunc consequat interdum varius. Euismod lacinia at quis risus sed vulputate odio. Laoreet non curabitur gravida arcu ac tortor dignissim convallis aenean. Eu sem integer vitae justo. Leo integer malesuada nunc vel risus commodo viverra maecenas accumsan. Id ornare arcu odio ut sem nulla pharetra diam. A iaculis at erat pellentesque. Tempor orci eu lobortis elementum nibh tellus molestie nunc. Suspendisse faucibus interdum posuere lorem ipsum dolor sit amet consectetur.
        
        // Elementum pulvinar etiam non quam lacus. Lacus viverra vitae congue eu consequat. Dictumst quisque sagittis purus sit amet volutpat consequat mauris. Diam sollicitudin tempor id eu nisl nunc mi. Tincidunt eget nullam non nisi est sit. Aliquet nec ullamcorper sit amet risus nullam. Ornare arcu odio ut sem. Donec enim diam vulputate ut pharetra sit amet aliquam id. Vel elit scelerisque mauris pellentesque pulvinar pellentesque habitant morbi tristique. Tellus elementum sagittis vitae et leo duis ut diam. Ultricies mi eget mauris pharetra et ultrices neque ornare aenean. Non consectetur a erat nam at lectus urna duis convallis. Nec ultrices dui sapien eget mi proin. Sit amet mattis vulputate enim nulla aliquet porttitor. Pellentesque elit eget gravida cum sociis natoque penatibus et. Sit amet commodo nulla facilisi nullam vehicula ipsum. Nunc mi ipsum faucibus vitae aliquet nec ullamcorper sit. Suspendisse interdum consectetur libero id faucibus nisl tincidunt. Eleifend donec pretium vulputate sapien nec.
        
        // Duis convallis convallis tellus id interdum. Orci porta non pulvinar neque laoreet suspendisse interdum consectetur libero. Purus sit amet luctus venenatis. Sit amet consectetur adipiscing elit pellentesque habitant morbi. Aliquet risus feugiat in ante metus dictum at tempor commodo. At auctor urna nunc id. Magnis dis parturient montes nascetur ridiculus mus. Semper feugiat nibh sed pulvinar proin. Sit amet aliquam id diam maecenas ultricies mi eget. Tortor vitae purus faucibus ornare. Non quam lacus suspendisse faucibus.
        
        // Id faucibus nisl tincidunt eget. Euismod nisi porta lorem mollis aliquam. Sit amet purus gravida quis blandit turpis cursus. Tellus in metus vulputate eu scelerisque felis. In tellus integer feugiat scelerisque varius morbi. Dignissim suspendisse in est ante in nibh. Sed felis eget velit aliquet sagittis id consectetur. Varius quam quisque id diam vel. Egestas diam in arcu cursus euismod quis viverra nibh. Nec ullamcorper sit amet risus nullam eget. Nulla posuere sollicitudin aliquam ultrices sagittis orci a scelerisque purus. Mattis pellentesque id nibh tortor. Arcu felis bibendum ut tristique et.
        
        // Et netus et malesuada fames ac turpis. Gravida quis blandit turpis cursus in hac. Odio ut sem nulla pharetra diam sit amet nisl. Mi sit amet mauris commodo quis imperdiet massa tincidunt nunc. Velit egestas dui id ornare arcu odio ut sem nulla. Arcu dictum varius duis at consectetur lorem. Vestibulum lectus mauris ultrices eros. In massa tempor nec feugiat nisl pretium fusce id velit. Sit amet justo donec enim diam vulputate ut pharetra. Egestas congue quisque egestas diam in arcu.
        
        // Sit amet cursus sit amet dictum sit amet. Id velit ut tortor pretium. Diam vel quam elementum pulvinar etiam non quam lacus suspendisse. Habitant morbi tristique senectus et. Cursus eget nunc scelerisque viverra mauris in. Risus viverra adipiscing at in tellus integer feugiat scelerisque varius. Sit amet porttitor eget dolor morbi non arcu risus. Iaculis at erat pellentesque adipiscing commodo elit at imperdiet. Phasellus egestas tellus rutrum tellus pellentesque eu tincidunt. Neque sodales ut etiam sit amet nisl purus in. Quam adipiscing vitae proin sagittis nisl rhoncus mattis rhoncus. Arcu cursus vitae congue mauris rhoncus.
        
        // Odio ut enim blandit volutpat. Aliquet bibendum enim facilisis gravida. Rutrum quisque non tellus orci ac auctor augue. Viverra vitae congue eu consequat ac felis donec et odio. Sed augue lacus viverra vitae congue eu consequat. Adipiscing at in tellus integer feugiat scelerisque varius morbi enim. Lorem sed risus ultricies tristique. Imperdiet dui accumsan sit amet nulla facilisi. Lacus laoreet non curabitur gravida arcu ac. Nec ultrices dui sapien eget. Etiam erat velit scelerisque in dictum non. Mattis aliquam faucibus purus in massa tempor nec. Sed elementum tempus egestas sed sed risus pretium.
        
        
        
        
        
        // ----
        // Sent using Guerrillamail.com
        // Block or report abuse: https://www.guerrillamail.com//abuse/?a=U05wCwgZY7IHiQC%2F%2BXsMcwHTQs%2Bc19RE1a1Bb5H1BAlwWHI%3D
        
        
        // ';
        
        // echo preg_replace("/[\r\n]/","<p>",$str);
        // dd();
        // $t = date('Y-m-d h:i:s', strtotime('Sat, 10 Oct 2020 15:42:52 +0000'));
        // dd($t);

        // $date = new DateTime('Sat, 10 Oct 2020 15:42:52 +0000', new DateTimeZone('Australia/Sydney'));
        // echo $date->format('Y-m-d h:i:s');

        // $tz = 'Sat, 10 Oct 2020 15:42:52 +0000';  // "2019-01-16 18:21:31" (UTC Time)
        // $date = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $tz, 'Australia/Sydney');
        // dd($date);
        // echo date('Y-m-d h:i:s');
        // $t = strtotime(date('2020-10-11 12:21:14'));
        // echo $this->get_time_ago($t);

        // $split_sentences = '/# Split sentences on whitespace between them.
        // (?<=                # Begin positive lookbehind.
        //   [.!?]             # Either an end of sentence punct,
        // | [.!?][\'"]        # or end of sentence punct and quote.
        // )                   # End positive lookbehind.
        // (?<!                # Begin negative lookbehind.
        //   Mr\.              # Skip either "Mr."
        // | Mrs\.             # or "Mrs.",
        // | Ms\.              # or "Ms.",
        // | Jr\.              # or "Jr.",
        // | Dr\.              # or "Dr.",
        // | Prof\.            # or "Prof.",
        // | Sr\.              # or "Sr.",
        // | \s[A-Z]\.              # or initials ex: "George W. Bush",
        //                     # or... (you get the idea).
        // )                   # End negative lookbehind.
        // \s+                 # Split on whitespace between sentences.
        // /ix';

        // $text = 'This is sentence one. Sentence two! Sentence thr'.
        //         'ee? Sentence "four". Sentence "five"! Sentence "'.
        //         'six"? Sentence "seven." Sentence \'eight!\' Dr. '.
        //         'Jones said: "Mrs. Smith you have a lovely daught'.
        //         'er!" The T.V.A. is a big project! '; // Note ws at end.

        // $sentences = preg_split($split_sentences, $text, -1, PREG_SPLIT_NO_EMPTY);
        // for ($i = 0; $i < count($sentences); ++$i) {
        //     printf("Sentence[%d] = [%s]\n", $i + 1, $sentences[$i]);
        //     echo '</br>';
        // }

        // $t = TicketOrigin::all();

        // foreach ($t as $val)
        // {
        //     $val->duration = 1;
        // }

        // dd($t);

        // dd(Auth::user()->roles->first()->id);
        // $ticketOrigin = TicketOrigin::where('name', 'yahozo');
        
        // if ( $ticketOrigin->count() )
        // {
        //     dd($ticketOrigin->first()->id);
        // }
        // else
        // {
        //     TicketOrigin::create([
        //         'name' => 'OUTLOOK',
        //         'status' => TicketOrigin::ACTIVE,
        //     ]);
        // }

        // $t = TicketOrigin::create([
        //     'name' => 'FRANKIESAUTOELECTRICS',
        //     'status' => TicketOrigin::ACTIVE,
        // ]);

        // dd($t->id);

        // dd();

        // $str = '<p class="paragraph ng-attr-widget" style="top: -40px; position: relative; margin: 0px; padding: 10px 20px 0px; color: #404040; font-family: "Museo Sans"; font-size: 14.6667px;"><span class="ng-directive ng-binding">Cupcake ipsum dolor sit. Amet jelly beans croissant toffee I love muffin. Cheesecake powder dessert cake caramels fruitcake.</span></p>
        // <p class="paragraph ng-attr-widget" style="top: -40px; position: relative; margin: 0px; padding: 10px 20px 0px; color: #404040; font-family: "Museo Sans"; font-size: 14.6667px;"><span class="ng-directive ng-binding">Chocolate cookie brownie tart powder cotton candy bear claw chocolate ice cream. Icing I love danish. Bonbon gingerbread I love wafer pudding topping. Wafer biscuit topping I love gingerbread bear claw bonbon I love.123</span></p>
        // <p class="paragraph ng-attr-widget" style="top: -40px; position: relative; margin: 0px; padding: 10px 20px 0px; color: #404040; font-family: "Museo Sans"; font-size: 14.6667px;">&nbsp;</p>
        // <p class="paragraph ng-attr-widget" style="top: -40px; position: relative; margin: 0px; padding: 10px 20px 0px; color: #404040; font-family: "Museo Sans"; font-size: 14.6667px;"><span class="ng-directive ng-binding"><img src="../images/lorem-signature.png" alt="Image 01" width="400" height="127" /></span></p>';

        
        // echo $str;

        // $signature = Signature::where('name', null);

        // dd($signature->count());

        // $user = Auth::user()->id;

        // $users = User::whereHas('roles', function($a){
        //     $a->where('id', Role::AGENT);
        // })->get();

        // dd($users);
        // GmailApi::createTickets();

        // $user = Auth::user();

        // dd($user->roles->first()->name);
    }

    // public function decodeMessage($message)
    // {

        // $_message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message)); 
        // $_message = quoted_printable_decode($_message);
        /*if (strpos($_message, '<div class="gmail_quote">'))
        {
            $_message = substr($_message, 0, strpos($_message, '<div class="gmail_quote">'));
        }*/

        // return $_message;

    // }

    public function test_sync_tickets()
    {
        // Log::info('Initiating Cron Job: Minute SyncEbayTickets');

        // $start = date('h:i:s');
        
        // EbayAPI::syncEbayMessages();
        
        // $end = date('h:i:s');

        // Log::info('Sync Ebay Tickets Start - End Time: '.$start.' - '.$end);


        Log::info('Initiating Cron Job: Minute SyncTickets');

        $start = date('h:i:s');
        
        GmailApi::createTickets();
        
        $end = date('h:i:s');

        Log::info('SyncTickets Start - End Time: '.$start.' - '.$end);
    }

    // public function test_syncFacebookConversations(Request $request)
    // {
    //     Facebook::facebookInstance();
    //     Facebook::syncConversations();
    // }
    
    // public function include() {
    //     require base_path() . '/vendor/autoload.php';
    //     require (__DIR__ . '/../../../vendor/autoload.php');
    // }
    
    public function go() {
    
        if ( $this->is_connected() )
        {
    
            // $gmail = new Gmail($this->get_client());
    
            return $this->readLabels();
    
        }
        else
        {
            return $this->get_unauthenticated_data();
        }
    
    }
    
    //connection
    
    public function set_connection() {
        $this->credentials = 'credentials.json';
        $this->client = $this->create_client();
    }
    
    public function get_client() {
        return $this->client;
    }
    
    public function get_credentials() {
        return $this->credentials;
    }
    
    public function is_connected() {
        return $this->is_connected;
    }
    
    public function get_unauthenticated_data() {
        // Request authorization from the user.
        $authUrl = $this->client->createAuthUrl();
    
        return '<a href='.$authUrl.'>Click here to link your account.</a>';
    }
    
    public function credentials_in_browser() {
    
        if ( $_GET['code'] )
        {
            return true;
        }
    
        return false;
    
    }
    
    public function create_client() {
    
        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes([
            Google_Service_Gmail::GMAIL_READONLY,
            Google_Service_Gmail::GMAIL_SETTINGS_BASIC,
            Google_Service_Gmail::MAIL_GOOGLE_COM,
            Google_Service_Gmail::GMAIL_COMPOSE,
            Google_Service_Gmail::GMAIL_MODIFY,
            Google_Service_Gmail::GMAIL_READONLY
        ]);
        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');
    
        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        // $tokenPath = 'token.json';
        $tokenPath = 'token2.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }
    
        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired())
        {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken())
            {
    
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
    
            }
            else if ( $this->credentials_in_browser() )
            {
                
                $authCode = $_GET['code'];
    
                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);
    
                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            else
            {
                $this->is_connected = false;
                return $client;
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath)))
            {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        else
        {
            echo '<p>not expired.</p>';
        }
    
        $this->is_connected = true;
    
        return $client;
    
    } 
    
    public function readLabels() {
    
        // Get the API client and construct the service object.
        $service = new Google_Service_Gmail($this->client);
    
        // Print the labels in the user's account.
        $user = 'me';
        $results = $service->users_labels->listUsersLabels($user);
    
        if (count($results->getLabels()) == 0)
        {
            print "No labels found.\n";
        }
        else
        {
            print "Labels:\n";
            foreach ($results->getLabels() as $label)
            {
                printf("- %s\n", $label->getName());
            }
        }
    
    }

    public function get_time_ago( $time )
    {
        print_r(time().' - '.$time.'</br>');
        $time_difference = time() - $time;
        
        
        if( $time_difference < 1 ) { return 'less than 1 second ago'; }
        $condition = array(
            12 * 30 * 24 * 60 * 60 => ' year',
            30 * 24 * 60 * 60      => ' month',
            24 * 60 * 60           => ' days',
            60 * 60                => ' hours',
            60                  => ' minutes',
            1                   => ' seconds'
        );
        
        foreach( $condition as $secs => $str )
        {
            $d = $time_difference / $secs;
            
            if( $d >= 1 )
            {
                $t = round( $d );
                return $t . $str;
            }
        }
        
    }

}
