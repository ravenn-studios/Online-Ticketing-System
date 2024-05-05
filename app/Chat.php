<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{

    public $table = 'chat';

    CONST STATUS_UNASSIGNED   = 1;
    CONST STATUS_PENDING      = 2;
    CONST STATUS_SOLVED       = 3;
    CONST STATUS_CLOSED       = 4;

    CONST START_CHAT_AGENT_RESPONDED = 1;
    CONST START_CHAT_AGENT_NO_RESPONSE = 2;

    protected $fillable = [
        'customer_id',
        'status_id',
        'reason',
        'name',
        'email',
        'ip_address',
        'agent_start_chat', // 0 not yet started, 1 started, 2 no response
    ];

    public function customer()
    {
        return $this->belongsTo('App\Customer','customer_id');
    }
    
    public function chatMessages()
    {
        return $this->hasMany('App\ChatMessage','chat_id')->orderBy('created_at', 'ASC');
    }

    public function agent()
    {
        return $this->chatMessages()->where('from', 'agent')->first();
    }

    public function status()
    {
        return $this->hasOne('App\TicketStatus', 'id', 'status_id');
    }

    public function chatLog()
    {
        return $this->hasOne('App\AgentChatLog', 'chat_id', 'id');
    }

    public static function chat_duration( $startDate, $endDate )
    {

        $time_difference = strtotime($endDate) - strtotime($startDate);

        
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

                    $dateIsCurrentWeek = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $startDate, 'Australia/Sydney')->isCurrentWeek();

                    if( $dateIsCurrentWeek )
                    {

                        $c = \Carbon\Carbon::parse(strtotime($startDate))->format('D h:i a');

                        return strtoupper($c);

                    }
                    else
                    {

                        $c = \Carbon\Carbon::parse(strtotime($startDate))->format('M d, y h:i a');
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

                    // return $t . ' ' .$str.' ago';
                    return $t . ' ' .$str;

                }

                
            }
        }
    }

}
