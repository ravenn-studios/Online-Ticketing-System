<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\AssignedTicket;
use App\TicketStatus;
use App\TicketOrigin;
use App\Role;
use App\EmailSupportAddress;
use App\UserCustomPage;
use App\UserPerformanceLog;
use Storage;
use Log;
use Illuminate\Support\Str;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class Ticket extends Model
{
    use SoftDeletes;
    use LogsActivity;

    public    $table = 'tickets';
    protected $dates = ['deleted_at'];


    CONST STATUS_UNASSIGNED   = 1;
    CONST STATUS_PENDING      = 2;
    CONST STATUS_SOLVED       = 3;
    CONST STATUS_CLOSED       = 4;
    CONST UNASSIGNED_DURATION = 1;  //hour, temporary on variable, will improve fuction for admin to adjust this in front end

    CONST BACKGROUND_PROCESS_SEND_MESSAGE = false;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'thread_id',
        'order_number',
        'page_id',
        'history_id',
        'channel_id',
        'subject',
        'snippet',
        'reply_to',
        'requester',
        'receiver',
        'origin_id',
        'thread_started_at',
        'status_id',
        'priority_id',
        'type_id',
        'read',
        'created_at',
        'updated_at',
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'id',
        'thread_id',
        'order_number',
        'page_id',
        'history_id',
        'channel_id',
        'subject',
        'snippet',
        'reply_to',
        'requester',
        'receiver',
        'origin_id',
        'thread_started_at',
        'status_id',
        'priority_id',
        'type_id',
        'read',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'Ticket';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} a Ticket";
    }

    public function tapActivity(Activity $activity, string $eventName)
    {

        if ($eventName === 'created')
        {
            
            // if ( $activity->relations['subject']->isDirty('status_id') )
            // {
                $userPerformanceLog = new \App\UserPerformanceLog;
                $userPerformanceLog->name = 'Ticket';
                $userPerformanceLog->description = 'Created a Ticket';
                $userPerformanceLog->user_id = (!isset(Auth::user()->id)) ? 0 : Auth::user()->id;
                $userPerformanceLog->ticket_id = $activity->relations['subject']->id;

                $userPerformanceLog->save();
            // }
            
        }

        if ($eventName === 'updated')
        {

            if ( $activity->relations['subject']->isDirty('status_id') )
            {
                // Log::info($activity->relations['subject']);
                $userPerformanceLog = new \App\UserPerformanceLog;
                $userPerformanceLog->name = 'Ticket';
                $userPerformanceLog->description = 'Updated a Ticket';
                $userPerformanceLog->user_id = (!isset(Auth::user()->id)) ? 0 : Auth::user()->id;
                $userPerformanceLog->property = 'status_id';
                $userPerformanceLog->property_value = $activity->relations['subject']->status_id;
                $userPerformanceLog->ticket_id = $activity->relations['subject']->id;

                $userPerformanceLog->save();
            }

            if ( $activity->relations['subject']->isDirty('priority_id') )
            {
                $userPerformanceLog = new \App\UserPerformanceLog;
                $userPerformanceLog->name = 'Ticket';
                $userPerformanceLog->description = 'Updated a Ticket';
                $userPerformanceLog->user_id = (!isset(Auth::user()->id)) ? 0 : Auth::user()->id;
                $userPerformanceLog->property = 'priority_id';
                $userPerformanceLog->property_value = $activity->relations['subject']->priority_id;
                $userPerformanceLog->ticket_id = $activity->relations['subject']->id;

                $userPerformanceLog->save();
            }

            if ( $activity->relations['subject']->isDirty('type_id') )
            {
                $userPerformanceLog = new \App\UserPerformanceLog;
                $userPerformanceLog->name = 'Ticket';
                $userPerformanceLog->description = 'Updated a Ticket';
                $userPerformanceLog->user_id = (!isset(Auth::user()->id)) ? 0 : Auth::user()->id;
                $userPerformanceLog->property = 'type_id';
                $userPerformanceLog->property_value = $activity->relations['subject']->type_id;
                $userPerformanceLog->ticket_id = $activity->relations['subject']->id;

                $userPerformanceLog->save();
            }


            // log the removed order number - pending update
            if ( $activity->relations['subject']->isDirty('order_number') )
            {
                $userPerformanceLog = new \App\UserPerformanceLog;
                $userPerformanceLog->name = 'Ticket';
                $userPerformanceLog->description = 'Updated a Ticket';
                $userPerformanceLog->user_id = (!isset(Auth::user()->id)) ? 0 : Auth::user()->id;
                $userPerformanceLog->property = 'order_number';
                $userPerformanceLog->property_value = $activity->relations['subject']->order_number;
                $userPerformanceLog->ticket_id = $activity->relations['subject']->id;

                $userPerformanceLog->save();
            }

        }

    }

    public function createMessage($message, $result)
    {

        $emailSupport = \App\EmailSupportAddress::active()->first()->email;

        $this->thread_id = $result->threadId;
        $this->save();

        $storeMessage = new \App\Message([
                    'ticket_id'     => $this->id,
                    'message_id'    => $result->id, //tmp
                    'message'       => base64_encode($message),
                    // 'message'       => $message['message'],
                    'from'          => $emailSupport,
                    'to'            => $this->requester,
                    // 'internal_date' => self::formatInternalDate($message['internal_date']),
                    'internal_date' => \Carbon\Carbon::now(),
                ]);

        $storeMessage->save();
        
    }

    public function categories()
    {
        return $this->belongsToMany('App\Category','ticket_categories','ticket_id','category_id');
    }
    
    public function tags()
    {
        return $this->belongsToMany('App\Tag','tickets_tags','ticket_id','tag_id');
    }

    public function assignedTo()
    {
        return $this->hasOne('App\AssignedTicket', 'ticket_id', 'id');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status_id', '!=', TicketStatus::STATUS_UNASSIGNED);
    }

    public function scopeExcludeFacebook($query)
    {
        return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_FACEBOOK);
    }

    public function scopeExcludeEbay($query)
    {
        
        // if ( !Auth::user()->rolesByIdExists([Role::AGENT_EBAY, Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) || Auth::user()->rolesByIdExists([Role::CUSTOMER_SERVICE_SUPPORT]) )
        if ( !Auth::user()->rolesByIdExists([Role::AGENT_EBAY, Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::AGENT]) || Auth::user()->rolesByIdExists([Role::CUSTOMER_SERVICE_SUPPORT]) )
        // if ( Auth::user()->rolesByIdExists([Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) ) // multiple rolesbyidexists calls returns function null errors
        {
            return $query->where('origin_id', '!=', TicketOrigin::ORIGIN_EBAY);
        }
        /*else
        {
            return;
        }*/

    }

    /* scope for email notifications trigger */
    public function scopePendingTicketsToNotify($query)
    {
        return $query->where('status_id', '=', self::STATUS_PENDING)
                     ->where('created_at', '<=', \Carbon\Carbon::now()->subHours(12)->toDateTimeString())
                     ->limit(20);
    }

    public function scopeSolvedTicketsToNotify($query)
    {
        return $query->where('status_id', '=', self::STATUS_SOLVED)
                     ->where('created_at', '<=', \Carbon\Carbon::now()->subWeeks()->toDateTimeString())
                     ->limit(20);
    }

    public function scopeUnassignedTicketsToNotify($query)
    {
        return $query->where('status_id', '=', self::STATUS_UNASSIGNED)
                     ->where('created_at', '<=', \Carbon\Carbon::now()->subHours(3)->toDateTimeString())
                     ->limit(20);
    }
    /* end scope for email notifications trigger */

    public function performanceLogs()
    {
        return $this->hasMany('App\UserPerformanceLog', 'ticket_id');
    }

    public function messages()
    {
        return $this->hasMany('App\Message');
    }

    public function facebookMessages()
    {
        // return $this->hasMany('App\Message')->orderBy('id', 'DESC');
        return $this->hasMany('App\Message')->orderBy('internal_date', 'ASC');
    }

    public function status()
    {
        return $this->hasOne('App\TicketStatus', 'id', 'status_id');
    }

    public function priority()
    {
        return $this->hasOne('App\TicketPriority', 'id', 'priority_id');
    }

    public function type()
    {
        return $this->hasOne('App\TicketType', 'id', 'type_id');
    }
    
    public function origin()
    {
        return $this->hasOne('App\TicketOrigin', 'id', 'origin_id');
    }

    public function facebookPage()
    {
        return $this->belongsTo('App\FacebookPage', 'page_id', 'page_id');
    }

    public function setOutgoingLinksToTarget($message)
    {

        // $html     = base64_decode($message);
        $html     = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message));
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

        $result =  $doc->saveHTML();

        return str_replace(['&Acirc;','&acirc;'], '', $result);

    }

    public function decodeMessage($message)
    {

        $_message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message)); 
        $_message = quoted_printable_decode($_message);
        /*if (strpos($_message, '<div class="gmail_quote">'))
        {
            $_message = substr($_message, 0, strpos($_message, '<div class="gmail_quote">'));
        }*/

        return $_message;

    }

    //if the ticket was already opened/read by an agent
    public function is_read()
    {
        if ( $this->read )
        {
            return true;
        }

        return false;
    }

    public function status_badge()
    {
        if ( $this->has_new_message() )
        {
            return '<span class="badge badge-warning custom-badge mr-1" data-toggle="tooltip" data-placement="top" title="New Message">N</span>';
        }

        return '';
    }

    public function getLastMessage()
    {
        return $this->messages()->orderBy('id', 'DESC')->limit(1)->first();
    }

    public function has_new_message()
    {
        
        $firstMessage             = $this->messages()->orderBy('internal_date', 'ASC')->limit(1)->first();
        $lastMessage              = $this->messages()->orderBy('internal_date', 'DESC')->limit(1)->first();
        $firstMessageReceivedFrom = (!isset($firstMessage)) ? '' : $firstMessage->from;
        $lastMessageReceivedFrom  = (!isset($lastMessage)) ? '' : $lastMessage->from;
        // dump($firstMessageReceivedFrom);
        // dump($lastMessageReceivedFrom);
        // dd();
        // if ( strpos($lastMessageReceivedFrom, EmailSupportAddress::active()->first()->email) == false ) // in the future.. for multiple emails use get all recors toArray then check if in arra
        // if ( strpos($lastMessageReceivedFrom, EmailSupportAddress::active()->first()->email) == false && ( $firstMessageReceivedFrom == $lastMessageReceivedFrom ) ) // in the future.. for multiple emails use get all recors toArray then check if in arra
        if ( $lastMessageReceivedFrom != EmailSupportAddress::active()->first()->email && $this->origin_id != TicketOrigin::ORIGIN_EBAY ) 
        {
            return false;
        }
        else if( $this->origin_id != TicketOrigin::ORIGIN_EBAY )
        {
            return false;
        }

        return true;
    }

    public static function getDurationUnassigned($thread_started_at)
    {

        $startDate       = strtotime( $thread_started_at );
        $currentDate     = strtotime( date('Y-m-d h:i:s') );
        $diff            = $currentDate - $startDate;
        $hoursUnassigned = number_format($diff / 3600, 1);
        $hoursUnassigned = (float)$hoursUnassigned;
        
        return $hoursUnassigned;
        
    }

    public static function get_time_ago( $time )
    {
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

                if ( $t == 1 && substr($str, -1) == 's' )
                {
                    $str = substr_replace($str , '', -1);
                }

                return $t . $str;

            }
        }
    }

    public static function chat_get_time_ago( $date )
    {

        $time_difference = time() - strtotime($date);

        
        if( $time_difference < 1 ) { return 'less than 1 second ago'; }
        $condition = array(
                            12 * 30 * 24 * 60 * 60 => 'year',
                            30 * 24 * 60 * 60      => 'month',
                            24 * 60 * 60           => 'days',
                            60 * 60                => 'hours',
                            60                  => 'minutes',
                            1                   => 'seconds'
                        );
        
        foreach( $condition as $secs => $str )
        {

            $d = $time_difference / $secs;

            if( $d >= 1 )
            {
                

                if ( $str == 'days' || $str == 'month' || $str == 'year' ) //if more than a day check if within a week or earlier than that
                {

                    $dateIsCurrentWeek = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date, 'Australia/Sydney')->isCurrentWeek();

                    if( $dateIsCurrentWeek )
                    {

                        $c = \Carbon\Carbon::parse(strtotime($date))->format('D h:i a');

                        return strtoupper($c);

                    }
                    else
                    {

                        $c = \Carbon\Carbon::parse(strtotime($date))->format('M d, y h:i a');
                        // dump(strtoupper($c));
                        return strtoupper($c);

                    }

                }
                else
                {
                    
                    $t = round( $d );

                    if ( $t == 1 && substr($str, -1) == 's' )
                    {
                        // $str = str_replace('s', '', $str);
                        $str = substr_replace('', $str, -1);
                    }

                    return $t . ' ' .$str.' ago';

                }

                
            }
        }
    }

    public static function chat_get_time_ago2( $date )
    {

        $time_difference = time() - strtotime($date);

        
        if( $time_difference < 1 ) { return 'less than 1 second ago'; }
        $condition = array(
                            12 * 30 * 24 * 60 * 60 => 'year',
                            30 * 24 * 60 * 60      => 'month',
                            24 * 60 * 60           => 'days',
                            60 * 60                => 'hours',
                            60                  => 'minutes',
                            1                   => 'seconds'
                        );
        
        foreach( $condition as $secs => $str )
        {

            $d = $time_difference / $secs;

            if( $d >= 1 )
            {
                

                if ( $str == 'days' || $str == 'month' || $str == 'year' ) //if more than a day check if within a week or earlier than that
                {

                    $dateIsCurrentWeek = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date, 'Australia/Sydney')->isCurrentWeek();

                    if( $dateIsCurrentWeek )
                    {

                        $c = \Carbon\Carbon::parse(strtotime($date))->format('D h:i a');

                        return strtoupper($c);

                    }
                    else
                    {

                        $c = \Carbon\Carbon::parse(strtotime($date))->format('M d, y h:i a');
                        // dump(strtoupper($c));
                        return strtoupper($c);

                    }

                }
                else
                {
                    
                    $t = round( $d );

                    if ( $t == 1 && substr($str, -1) == 's' )
                    {
                        // $str = str_replace('s', '', $str);
                        $str = substr_replace($str, '', -1);
                    }

                    return $t . ' ' .$str.' ago';

                }

                
            }
        }
    }

    public function assignTickets($random = 0, $tickets, Array $users)
    {
        
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

                $this->assignTicket($ticket, $user);
                $user = next($users);

                if(!$user)
                {
                    $users = $allUsers;
                    $user  = current($users);
                }
                
            }

        }
        else
        {

            foreach ( $tickets as $ticket )
            {
                $this->assignTicket($ticket, $user);
            }

        }

    }

    // public function assignTickets($random = 0, $tickets, Array $users)
    // {

    //     if ( !isset($users[0]) ) // if not array of key values then create one for the current(), and next() function to work
    //     {
    //         $data[0]     = $users;
    //         $users       = $data;
    //     }

    //     if ( $random )
    //     {

    //         $allUsers = $users;
    //         $user     = current($users);

    //         foreach ( $tickets as $ticket )
    //         {

    //             /*
    //              *  before assigning ticket, check if user reached limit
    //              * if (user limit reached)
    //              *  fetch all users that has not yet reached the limit
    //              */

    //             if ( $this->is_user_ticket_limit_reached( $user['id'] )  )
    //             {
    //                 /* what if two consecutive agents has reached limit?
    //                  *  - what if all not available?
    //                  *      - then have checked if everyone reached limit then set unassigned if no one is available else
    //                   *       use the current user from the $usersLimitNotReached that can still cater tickets
    //                  */

    //                 $usersLimitNotReached = $this->get_users_limit_not_reached();
    //                 if ( empty( $usersLimitNotReached ) )
    //                 {
    //                     //set ticket unassigned since no agent is available / all agents ticket limit reached
    //                     $_ticket         = Ticket::find($ticket['id']);
    //                     $_ticket->status = Ticket::STATUS_UNASSIGNED;
    //                     $_ticket->save();
    //                 }
    //                 else
    //                 {
    //                     $allUsers = $users = $usersLimitNotReached;
    //                     $user = current($users);
    //                     $this->assignTicket($ticket, $user);
    //                 }

    //             }
    //             else
    //             {
    //                 $this->assignTicket($ticket, $user);
    //                 $user = next($users);
    //             }

    //             if(!$user)
    //             {
    //                 $users = $allUsers;
    //                 $user  = current($users);
    //             }
                
    //         }

    //     }
    //     else
    //     {

    //         foreach ( $tickets as $ticket )
    //         {
    //             $this->assignTicket($ticket, $user);
    //         }

    //     }

    // }

    public function assignTicket($ticket, Array $user)
    {
        // AssignedTicket::updateOrCreate(
        //     [
        //         'user_id'   => $user['id'],
        //         'ticket_id' => $ticket['id'],
        //     ],
        //     [
        //         'user_id'   => $user['id'],
        //         'ticket_id' => $ticket['id'],
        //     ]
        // );

        $assignTicket = AssignedTicket::where('ticket_id', $ticket['id'])->get();
        if ( !$assignTicket->count() )
        {
            AssignedTicket::create([
                'user_id'   => $user['id'],
                'ticket_id' => $ticket['id'],
            ]);
        }

        Log::info('Log assign tickets => Ticket Id: ' . $ticket['id'] . ', ' . 'User Id: ' . $user['id']);

    }

    public function count_awaiting_fulfillment_tickets()
    {
        return $this->excludeFacebook()->excludeEbay()->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)->count();
    }

    public function count_awaiting_shipment_tickets()
    {
        return $this->excludeFacebook()->excludeEbay()->where('type_id', TicketType::TYPE_AWAITING_SHIPMENT)->count();
    }

    public function count_tickets_needs_urgent_attention()
    {
        //use to identify if any admin/manager/developer, show all current ticket count depending on view
        // else show only the auth user tickets data
        // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
        // {
            return $this->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                        ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
                        // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                        ->where('created_at', '<', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                        ->whereDoesntHave('messages', function($q){
                            $q->where('from', EmailSupportAddress::active()->first()->email);
                        })
                        ->count();
        // }

        // return Auth::user()->tickets()->excludeFacebook()->excludeEbay()
        //             ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
        //             ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
        //             // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
        //             ->where('created_at', '<', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
        //             ->whereDoesntHave('messages', function($q){
        //                 $q->where('from', EmailSupportAddress::active()->first()->email);
        //             })
        //             ->count();
    }

    public function count_tickets_over_four_hours()
    {
        // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
        // {
            return $this->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                    ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
                    // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                    ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(1)->toDateTimeString(), \Carbon\Carbon::now()->subHours(4)->toDateTimeString()])
                    ->whereDoesntHave('messages', function($q){
                        $q->where('from', EmailSupportAddress::active()->first()->email);
                    })
                    ->count();
        // }

        // return Auth::user()->tickets()->excludeFacebook()->excludeEbay()
        //             ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
        //             ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
        //             // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
        //             ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(1)->toDateTimeString(), \Carbon\Carbon::now()->subHours(4)->toDateTimeString()])
        //             ->whereDoesntHave('messages', function($q){
        //                 $q->where('from', EmailSupportAddress::active()->first()->email);
        //             })
        //             ->count();
    }

    public function count_tickets_under_four_hours()
    {
        // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
        // {
            return $this->excludeFacebook()->excludeEbay()
                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                        ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
                        ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(4)->toDateTimeString())
                        ->whereDoesntHave('messages', function($q){
                            $q->where('from', EmailSupportAddress::active()->first()->email);
                        })
                        ->count();
        // }

        // return Auth::user()->tickets()->excludeFacebook()->excludeEbay()
        //             ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
        //             ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(4)->toDateTimeString())
        //             ->whereDoesntHave('messages', function($q){
        //                 $q->where('from', EmailSupportAddress::active()->first()->email);
        //             })
        //             ->count();
    }

    public function count_recently_solved_tickets()
    {
        // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
        // {
            return $this->excludeFacebook()->excludeEbay()->where('status_id', TicketStatus::STATUS_SOLVED)->whereBetween('updated_at', 

                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                        )->count();
        // }

        // return Auth::user()->tickets()->excludeFacebook()->excludeEbay()->where('status_id', TicketStatus::STATUS_SOLVED)->count();
    }

    public function count_recently_closed_tickets()
    {
        // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
        // {
            return $this->excludeFacebook()->excludeEbay()->where('status_id', TicketStatus::STATUS_CLOSED)->whereBetween('updated_at', 

                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                        )->count();
        // }

        // return Auth::user()->tickets()->excludeFacebook()->excludeEbay()->where('status_id', TicketStatus::STATUS_CLOSED)->count();
    }

    public function get_users_limit_not_reached()
    {

        $users = User::faeAgents()->withCount('tickets')->orderBy('tickets_count', 'asc')->get();

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

    public function count_user_all_tickets($userId)
    {
        return User::find($userId)->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->count();
    }

    public function count_user_tickets_today($userId)
    {
        return User::find($userId)->tickets()->excludeFacebook()->where('created_at', '>=', \Carbon\Carbon::today())->count();
    }

    public function count_my_tickets()
    {
        return Auth::user()->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->count();
    }

    public function count_tickets_unassigned()
    {
        //use to identify if any admin/manager/developer, show all current ticket count depending on view
        // else show only the auth user tickets data
        // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
        // {
            return $this->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_UNASSIGNED])
                        ->count();
        // }

        // return Auth::user()->tickets()->excludeFacebook()->excludeEbay()
        //             ->whereIn('status_id', [TicketStatus::STATUS_UNASSIGNED])
        //             ->count();
    }

    public function count_custom_page_tickets($slug)
    {

        if ( !empty($slug) )
        {

            $tickets     = '';
            $customPages = UserCustomPage::where('slug', $slug)->where('user_id', Auth::id());

            foreach ( $customPages->get() as $customPage )
            {

                if ( $customPage->pageConditions->count() )
                {

                    // dd( $customPage->pageConditions );
                    // $tickets        = '';
                    $pageConditions = $customPage->pageConditions()->orderBy('operator', 'ASC')->get();

                    foreach ( $pageConditions as $key => $pageCondition )
                    {

                        //setup eloquent on first iteration
                        if ( $key === 0 )
                        {

                            if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                            {
                                $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                            }
                            else
                            {
                                // $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where($pageCondition->filter.'_id', $pageCondition->filter_id);

                                if ( $pageCondition->filter == 'origin' && $pageCondition->filter_id == 8 ) // 8 = FAE
                                {
                                    // $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                    $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where('origin_id', '!=', TicketOrigin::ORIGIN_EBAY)->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                                else
                                {
                                    $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }

                            }

                            /*if ( $slug == 'ebay' )
                            {
                                $tickets->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com']);
                            }*/

                            //tmp solution for ebay mixing solved tickets to pending,unassigned
                            if ( strpos(strtolower($customPage->name), 'ebay') !== false )
                            {

                                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT, Role::AGENT_EBAY]) )
                                {
                                    $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where('origin_id', TicketOrigin::ORIGIN_EBAY);
                                }
                                // elseif ( Auth::user()->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
                                // {
                                //     $tickets = Auth::user()->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where('origin_id', TicketOrigin::ORIGIN_EBAY);
                                // }

                            }

                        }
                        else
                        {

                            if ( strpos(strtolower($customPage->name), 'ebay') === false )
                            {
                                if ( $pageCondition->operator == 'AND' )
                                {
                                    $tickets->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                                else
                                {
                                    $tickets->orWhere($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                            }

                        }
                        
                    }

                    // $tickets = $tickets->orderBy('thread_started_at', 'DESC')->get()->count();
                
                }

            }

            if ( $slug == 'from-ebay' )
            {
                // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
                // }
                // else
                // {
                //     $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
                // }
            }

            $tickets = ( !empty($tickets) ) ? $tickets->orderBy('thread_started_at', 'DESC')->get()->count() : $tickets;

            return $tickets;

        }
        else
        {
            return 0;
        }

    }

    public function saveAttachment(Array $file)
    {
        //$file[data, name]

        if ( !isset($file['data']) || !isset($file['name']) )
        {
            return false;
        }

        $randString = random_bytes(5);
        $randString = bin2hex($randString);
        $_fileName  = explode('.', $file['name']);
        $fileName   = current($_fileName).'-'.$randString.'.'.end($_fileName);  //documentName.extension
        // $fileName   = current($_fileName).'.'.end($_fileName);  //documentName.extension

        $storagePath = Storage::path('public/attachments/').$fileName;
        $fileData = base64_decode($file['data']);
        // $file     = fopen('../storage/app/attachments/'.$fileName,'w+');
        $file     = fopen($storagePath,'w+');
        fwrite($file, $fileData); //17849378ff9aab4b or 1785daa02e483056
        fclose($file);

        return ['path' => $storagePath, 'filename' => $fileName];

    }

    public function getAgentsTicketsData($userIds, $dateRange, $agentsTicketsCountByStatus)
    {

        if ( !empty($userIds) )
        {

            $_emailSupportAddresses = $this->emailSupportAddresses();

            $users = User::whereIn('id', $userIds)->get();

            // $tickets = Ticket::whereHas('assignedTo', function($a) use($userId){
            //             $a->where('user_id', $userId) // change to var
            //                 ->whereNested(function($a) {
            //                     // $a->where('tickets.status_id', Ticket::STATUS_PENDING)
            //                     $a->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
            //                         ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
            //                 });
            //         })
            //         ->get();
            // dump($dateRange);
            $time_start = microtime(true); 

            if ( !empty($dateRange) )
            {
                // $tickets = Ticket::assigned()->whereHas('assignedTo', function($a) use($userIds){
                //         $a->whereIn('user_id', $userIds); // change to var
                //     })
                // ->whereBetween('created_at', $dateRange)
                // ->orderBy('updated_at', 'DESC')
                // ->get();

                // dump('after daterange query: ' . (microtime(true) - $time_start) );
                // $time_start = microtime(true);

                $tickets = $this->with(['messages'])->whereHas('assignedTo', function($a) use($userIds){
                        $a->whereIn('user_id', $userIds) // change to var
                            ->whereNested(function($b) {
                                $b->where('tickets.status_id', self::STATUS_PENDING) // add for now to test, comment on production
                                  ->orWhere('tickets.status_id', self::STATUS_SOLVED)
                                  ->orWhere('tickets.status_id', self::STATUS_CLOSED);
                            });
                        })
                        ->whereBetween('created_at', $dateRange)
                        ->orderBy('updated_at', 'DESC')
                        ->get(['id','thread_id','subject','snippet','requester','receiver','thread_started_at','status_id','priority_id','type_id','created_at','updated_at']);

                // dump('after daterange query: ' . (microtime(true) - $time_start) ); 
            }
            else
            {
                $tickets = $this->with(['messages'])->whereHas('assignedTo', function($a) use($userIds){
                        $a->whereIn('user_id', $userIds) // change to var
                            ->whereNested(function($b) {
                                $b->where('tickets.status_id', self::STATUS_PENDING) // add for now to test, comment on production
                                  ->orWhere('tickets.status_id', self::STATUS_SOLVED)
                                  ->orWhere('tickets.status_id', self::STATUS_CLOSED);
                            });
                        })
                        ->orderBy('updated_at', 'DESC')
                        ->get(['id','thread_id','subject','snippet','requester','receiver','thread_started_at','status_id','priority_id','type_id','created_at','updated_at']);
            }

            // dd('after daterange query: ' . (microtime(true) - $time_start) ); 

            $count_tickets_solved_in_a_day = $count_tickets_solved_over_a_day = $count_tickets_closed_in_a_day = $count_tickets_closed_over_a_day = 0;
            $ticketOpenedToSolvedTime      = $ticketOpenedToClosedTime = $ticketSolvedToClosedTime      = [];
            // dump($tickets->toArray());
            foreach($tickets as $_key => $ticket)
            {
                // dump('---------');
                $time_start = microtime(true);
                // dd($ticket->assignedTo->user_id);
                // $ticket->messages;

                $ticket->agent_reply_count = $ticket->messages->whereIn('from', $_emailSupportAddresses)->count();
                
                $customerReplyCount        = $ticket->messages->whereIn('from', $ticket->requester)->count();

                $ticketMessagesTimeStamp = [];

                $ticket->solved_duration = $ticket->closed_duration = $ticket->agent_first_response_duration = ''; //ticket statuses durations, agent response time through performance logs table.
                $ticket->agent_average_response_time = ''; //avg response time for agents

                // will be use as a flag if previous message is from customer or not, to avoid getting incorrect timestamps when theres 2 consecutive replies
                $isLastMessageCustomer   = false; // default true, since we need to start on customer message
                $_ctr                    = 0; // tmp counter will be used once for first customer message

                //should have interaction between cs and agents by checking of their message exists in a ticket
                if ( $ticket->agent_reply_count && $customerReplyCount )
                {

                    //average response time on chat
                    foreach($ticket->messages as $key => $message)
                    {

                        /*
                         * start always with customers message then get the agent next etc.
                        */
                            
                        if ( !$isLastMessageCustomer && ( !in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                        // if ( !$isLastMessageCustomer && !in_array($message->from, $_emailSupportAddresses) && empty($ticketMessagesTimeStamp) )
                        {
                            $isLastMessageCustomer     = true;
                            $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
                        }
                        // elseif ( $isLastMessageCustomer && in_array($message->from, $_emailSupportAddresses) )
                        elseif ( $isLastMessageCustomer && ( in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                        {
                            $isLastMessageCustomer     = false;
                            $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
                        }

                    }

                    // if($ticket->id == 35096) {
                    //     dd($ticketMessagesTimeStamp);
                    // }
                    // dump('after ticket->messages query: ' . (microtime(true) - $time_start) . 'sec' );

                    /* compute average response time of agents
                     * chunk by 2, to compare
                    */
                    $ticketMessagesTimeStamp = array_chunk($ticketMessagesTimeStamp, 2);

                    if ( !empty($ticketMessagesTimeStamp) )
                    {

                        $noOfChunks           = 0;
                        $messagesResponseTime = $messagesResponseTimeInMinutes = [];
                        foreach($ticketMessagesTimeStamp as $timestamp)
                        {

                            if( count($timestamp) > 1 )
                            {

                                $noOfChunks++;

                                // for incoming tickets that are outside work schedule.
                                $tmpStart = $timestamp[0];

                                if ( $tmpStart > \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 15:00:00') )
                                {
                                    $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->addDay()->format('Y-m-d 06:00:00');
                                }
                                elseif ( $tmpStart < \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00') )
                                {
                                    $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00');
                                }

                                $start  = new \Carbon\Carbon($timestamp[0]);
                                $end    = new \Carbon\Carbon($timestamp[1]);

                                $messagesResponseTime[]          = $start->diffInSeconds($end);
                                $messagesResponseTimeInMinutes[] = $start->diffInMinutes($end);

                            }

                        }

                        if( !empty($messagesResponseTime) )
                        {
                            $_minutes                                       = array_sum($messagesResponseTimeInMinutes) / count($messagesResponseTimeInMinutes);
                            $seconds                                        = array_sum($messagesResponseTime) / count($messagesResponseTime);
                            $ticket->agent_average_response_time            = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
                            $ticket->agent_average_response_time_detailed   = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
                            $ticket->agent_average_response_time_in_minutes = $_minutes;
                        }

                    }

                    // dump('after ticketMessagesTimeStamp query: ' . (microtime(true) - $time_start) . 'sec' );

                    
                    /*
                     * get ticket statuses durations, agent response time through performance logs table.
                    */

                    if ( $ticket->performanceLogs->count() )
                    {

                        $ticketSolvedAt = '';
                        $ticketClosedAt = '';
                        foreach( $ticket->performanceLogs as $performanceLog )
                        {

                            if ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_SOLVED  && $ticket->status_id == Ticket::STATUS_SOLVED)
                            {
                                $ticketSolvedAt = $performanceLog->created_at;

                                $openedTicket = $ticket->performanceLogs->where('description', 'Opened a Ticket')->first();
                                if ( $openedTicket )
                                {
                                    $ticketOpenedToSolvedTime[] = $openedTicket->created_at->diffInSeconds($ticketSolvedAt);


                                    $tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

                                    if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToSolvedTime'] ) )
                                    {
                                        $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToSolvedTime'] = [];
                                    }

                                    $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToSolvedTime'][] = $openedTicket->created_at->diffInSeconds($ticketSolvedAt);

                                }

                                $closedTicket = $ticket->performanceLogs->where('property', 'status_id')->where('property_value', Ticket::STATUS_CLOSED)->first();
                                if ( $closedTicket )
                                {
                                    $ticketSolvedToClosedTime[] = $closedTicket->created_at->diffInSeconds($ticketSolvedAt);


                                    $tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

                                    if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['ticketSolvedToClosedTime'] ) )
                                    {
                                        $agentsTicketsCountByStatus[$tmpKey]['ticketSolvedToClosedTime'] = [];
                                    }

                                    $agentsTicketsCountByStatus[$tmpKey]['ticketSolvedToClosedTime'][] = $closedTicket->created_at->diffInSeconds($ticketSolvedAt);
                                }

                            }
                            elseif ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_CLOSED  && $ticket->status_id == Ticket::STATUS_CLOSED)
                            {
                                $ticketClosedAt = $performanceLog->created_at;

                                $openedTicket = $ticket->performanceLogs->where('description', 'Opened a Ticket')->first();
                                if ( $openedTicket )
                                {
                                    $tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

                                    if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToClosedTime'] ) )
                                    {
                                        $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToClosedTime'] = [];
                                    }

                                    $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToClosedTime'][] = $openedTicket->created_at->diffInSeconds($ticketClosedAt);
                                }
                            }

                            // if ($performanceLog->user_replied_at && $ticket->id == 2882)
                            if ($performanceLog->user_replied_at)
                            {

                                $ticketCreatedAt   = new \Carbon\Carbon($ticket->created_at);
                                $userRepliedAt     = new \Carbon\Carbon($performanceLog->user_replied_at);

                                $ticketCreatedDate = $ticketCreatedAt->format('Y-m-d G:i:s');
                                $userRepliedDate   = $userRepliedAt->format('Y-m-d G:i:s');

                                
                                $agentFirstResponseSameDay = ($ticketCreatedAt->format('Y-m-d') == $userRepliedAt->format('Y-m-d') ? true: false);

                                // $test =  \Carbon\Carbon::parse('2019-06-13')->isSameAs('d', \Carbon\Carbon::parse('2019-12-13'));
                                // dump($test);
                                //use 00:00:00 on h:i:s to exactly get the last day
                                $period                     = \Carbon\CarbonPeriod::create($ticketCreatedAt->format('Y-m-d 00:00:00'), $userRepliedAt->format('Y-m-d 00:00:00'));
                                $agentFirstResponseDuration = 0; // get total time when agent first responded
                                // Iterate over the period

                                // dump(count($period));
                                foreach ($period as $date) // loop through dates, count time duration in between work  and outside work hours
                                {
                                    // dump($date->format('Y-m-d'));
                                    $startAgentShift = \Carbon\Carbon::parse( $date->format('Y-m-d') . ' 06:00:00' );
                                    $endAgentShift   = \Carbon\Carbon::parse( $date->format('Y-m-d') . ' 15:00:00' );

                                    if ($agentFirstResponseSameDay)
                                    {
                                        //should have condition to determine if ticket was created between or outside the shift...
                                        // $ticket->agent_first_response_duration = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
                                        if ( $ticketCreatedAt < $startAgentShift )
                                        {
                                            $ticket->agent_first_response_duration          = $this->getActivityDuration($startAgentShift, $performanceLog->user_replied_at);
                                            $ticket->agent_first_response_duration_detailed = $this->getDetailedActivityDuration($startAgentShift, $performanceLog->user_replied_at);
                                            break;
                                        }
                                        else
                                        {

                                            /*if ($ticket->id == 29849)
                                            {
                                                dump($ticket->created_at);
                                                dump($performanceLog->user_replied_at);
                                                dd($this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at));
                                            }*/

                                            $ticket->agent_first_response_duration          = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
                                            $ticket->agent_first_response_duration_detailed = $this->getDetailedActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
                                            break;
                                        }
                                    }
                                    else
                                    {
                                        // dump(1);
                                        //on first day
                                        if ( $ticketCreatedAt->format('Y-m-d') == $date->format('Y-m-d') )
                                        {
                                            // dump($ticketCreatedAt->format('Y-m-d') .' : '. $date->format('Y-m-d'));
                                            /* Get the correct agent_first_response_duration on first day
                                             * on first loop, check if ticket created before the shift. then Start Shift - End Shift
                                             * else Ticket created_at - end shift
                                             */
                                            // dump(2);
                                            if ( $ticketCreatedAt < $startAgentShift )
                                            {
                                                $agentFirstResponseDuration += $startAgentShift->diffInSeconds($endAgentShift);
                                                // dump(3);
                                            }
                                            // else
                                            elseif ( $ticketCreatedAt > $startAgentShift && $ticketCreatedAt < $endAgentShift )
                                            {
                                                $agentFirstResponseDuration += $ticketCreatedAt->diffInSeconds($endAgentShift);
                                                // dump(4);
                                            }

                                        }
                                        else
                                        {
                                            //days in between , end date

                                            //end date
                                            // dump(5);
                                            if ( $userRepliedAt->format('Y-m-d') == $date->format('Y-m-d') )
                                            {
                                                $agentFirstResponseDuration += $startAgentShift->diffInSeconds($userRepliedAt);
                                                // dump(6);
                                            }
                                            else
                                            {
                                                $agentFirstResponseDuration += $startAgentShift->diffInSeconds($endAgentShift);
                                                // dump(7);
                                            }

                                        }

                                    }

                                }

                                if ( !$agentFirstResponseSameDay )
                                {
                                    $ticket->agent_first_response_duration          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans());
                                    $ticket->agent_first_response_duration_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans(['parts' => 2]));
                                }

                            }

                        }

                        // ticket duration before its solved/closed
                        if ( !empty($ticketSolvedAt) )
                        {
                            // dump($ticket->id . ': ' . $ticketSolvedAt->diff($ticket->created_at)->format('%D:%H:%I:%S'));
                            $ticket->solved_duration          = $this->getActivityDuration($ticket->created_at, $ticketSolvedAt);
                            $ticket->detailed_solved_duration = $this->getDetailedActivityDuration($ticket->created_at, $ticketSolvedAt);

                            if ( $ticket->created_at->diffInMinutes($ticketSolvedAt) <= 1440 ) //tickets solved within 24hrs else over a day
                            {
                                $count_tickets_solved_in_a_day++;

                                $tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

                                if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_in_a_day'] ) )
                                {
                                    $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_in_a_day'] = 0;
                                }

                                $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_in_a_day'] += 1;
                            }
                            else
                            {
                                $count_tickets_solved_over_a_day++;

                                $tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

                                if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_over_a_day'] ) )
                                {
                                    $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_over_a_day'] = 0;
                                }

                                $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_over_a_day'] += 1;
                            }

                        }

                        if ( !empty($ticketClosedAt) )
                        {

                            // dump($ticket->id . ': ' . $ticketClosedAt->diff($ticket->created_at)->format('%D:%H:%I:%S'));
                            $ticket->closed_duration          = $this->getActivityDuration($ticket->created_at, $ticketClosedAt);
                            $ticket->detailed_closed_duration = $this->getDetailedActivityDuration($ticket->created_at, $ticketClosedAt);

                            if ( $ticket->created_at->diffInMinutes($ticketClosedAt) <= 1440 ) //tickets closed within 24hrs else over a day
                            {
                                $count_tickets_closed_in_a_day++;

                                $tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

                                if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_in_a_day'] ) )
                                {
                                    $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_in_a_day'] = 0;
                                }

                                $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_in_a_day'] += 1;
                            }
                            else
                            {
                                $count_tickets_closed_over_a_day++;

                                $tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

                                if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_over_a_day'] ) )
                                {
                                    $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_over_a_day'] = 0;
                                }

                                $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_over_a_day'] += 1;
                            }

                        }

                        if ( $tickets->count() - 1 == $_key ) // add extra data to the last ticket record for now..
                        {
                            $ticket->count_tickets_solved_in_a_day   = $count_tickets_solved_in_a_day;
                            $ticket->count_tickets_solved_over_a_day = $count_tickets_solved_over_a_day;
                            $ticket->count_tickets_closed_in_a_day   = $count_tickets_closed_in_a_day;
                            $ticket->count_tickets_closed_over_a_day = $count_tickets_closed_over_a_day;

                            // dd($ticketOpenedToSolvedTime);
                            if( !empty($ticketOpenedToSolvedTime) )
                            {
                                $seconds                                               = array_sum($ticketOpenedToSolvedTime) / count($ticketOpenedToSolvedTime);
                                $ticket->average_time_ticket_opened_to_solved          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
                                $ticket->average_time_ticket_opened_to_solved_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
                            }

                            if( !empty($ticketSolvedToClosedTime) )
                            {
                                $seconds                                               = array_sum($ticketSolvedToClosedTime) / count($ticketSolvedToClosedTime);
                                $ticket->average_time_ticket_solved_to_closed          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
                                $ticket->average_time_ticket_solved_to_closed_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
                            }

                            // dd($ticket);

                        }

                    }

                    // dump('after ticket->performanceLogs query: ' . (microtime(true) - $time_start) . 'sec' );

                }

            }

            // dump('@@@@@after tickets loop: ' . (microtime(true) - $time_start) );$time_start = microtime(true); 

            //ticketOpenedToSolvedTime, ticketSolvedToClosedTime
            foreach( $agentsTicketsCountByStatus as $tmpKey => $val )
            {

                if ( isset($val['ticketOpenedToSolvedTime']) )
                {
                    $tmpSeconds                                           = array_sum($val['ticketOpenedToSolvedTime']) / count($val['ticketOpenedToSolvedTime']);
                    $agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_solved']          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans());
                    $agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_solved_detailed'] = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans(['parts' => 2]));
                }

                if ( isset($val['ticketSolvedToClosedTime']) )
                {
                    $tmpSeconds                                           = array_sum($val['ticketSolvedToClosedTime']) / count($val['ticketSolvedToClosedTime']);
                    $agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_solved_to_closed']          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans());
                    $agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_solved_to_closed_detailed'] = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans(['parts' => 2]));
                }

                if ( isset($val['ticketOpenedToClosedTime']) )
                {
                    $tmpSeconds                                           = array_sum($val['ticketOpenedToClosedTime']) / count($val['ticketOpenedToClosedTime']);
                    $agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_closed']          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans());
                    $agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_closed_detailed'] = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans(['parts' => 2]));
                }

            }

            // dump('after agentsTicketsCountByStatus query: ' . (microtime(true) - $time_start) );$time_start = microtime(true); 

            // dd('after agentsTicketsCountByStatus query: ' . (microtime(true) - $time_start) . 'sec' );


            // or maybe not use this, just limit the foreach loop in blade template..
            // foreach($tickets as $key => $ticket)
            // {
            //     if($key > 20) // limit display of ticket under data tab on view report
            //     {
            //         $tickets->forget($key);
            //     }
            // }

            // dump('after tickets forget query: ' . (microtime(true) - $time_start) );

            // dump($tickets);
            // dd($tickets);
            // dd($tickets->paginate(20));

            $data = [
                'tickets' => $tickets,
                'agentsTicketsCountByStatus' => $agentsTicketsCountByStatus
            ];

            return $data;
            // return $tickets;

        }
        else
        {
            return false;
        }

    }

    public function getActivityDuration($start, $end)
    {
        $start  = new \Carbon\Carbon($start);
        $end    = new \Carbon\Carbon($end);

        return str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $start->diffInSeconds($end) )->diffForHumans());
    }

    public function getDetailedActivityDuration($start, $end)
    {
        $start  = new \Carbon\Carbon($start);
        $end    = new \Carbon\Carbon($end);

        return str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $start->diffInSeconds($end) )->diffForHumans(['parts' => 2]));
    }

    public function emailSupportAddresses()
    {
        $emails = \App\EmailSupportAddress::withTrashed()->get('email');
        $_emails = [];

        foreach($emails as $email)
        {
            $_emails[] = $email->email;
        }

        array_push($_emails, 'Brandbeast');

        return $_emails;
    }

}
