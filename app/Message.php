<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\UserPerformanceLog;
use Log;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;


class Message extends Model
{
    use SoftDeletes;
    use LogsActivity;

    public    $table = 'messages';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'ticket_id',
        'message_id',
        'message',
        'notes',
        'file_ids',
        'from',
        'to',
        'internal_date',
        'created_at',
        'updated_at',
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'id',
        'ticket_id',
        'message_id',
        'message',
        'notes',
        'file_ids',
        'from',
        'to',
        'internal_date',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'Message';

    public function tapActivity(Activity $activity, string $eventName)
    {

        if ($eventName === 'created')
        {
            
            $userPerformanceLoggedUserRepliedAt = \App\UserPerformanceLog::where('ticket_id', $activity->relations['subject']->ticket_id)
            ->where('user_replied_at', '<>', null)
            ->get()
            ->count();

            //check if user_replied_at exists in on of logged records of the ticket
            if ($userPerformanceLoggedUserRepliedAt == 0)
            {

                $userPerformanceLog = \App\UserPerformanceLog::where('ticket_id', $activity->relations['subject']->ticket_id)->get();

                $ticketMessages = \App\Message::where('ticket_id', $activity->relations['subject']->ticket_id)->get();
                // $ticketMessagesFromEmailSupport = \App\Message::where('ticket_id', $activity->relations['subject']->ticket_id)->where('from', \App\EmailSupportAddress::active()->first()->email)->get();
                $ticketMessagesFromEmailSupport = \App\Message::where('ticket_id', $activity->relations['subject']->ticket_id)
                                                    ->where(function($query){
                                                        $query->where('from', \App\EmailSupportAddress::active()->first()->email)
                                                              ->orWhere('from', 'Brandbeast');

                                                    })
                                                    ->get();
                //check if has record then add user_replied_at on first record else  create new record with user_replied_at, this will be checked by user_replied_at and ticket_id column
                if ( $userPerformanceLog->count() && $ticketMessages->count() > 1 && $ticketMessagesFromEmailSupport->count() )
                {
                    $_userPerformanceLog = \App\UserPerformanceLog::find( $userPerformanceLog->first()->id );
                    $_userPerformanceLog->user_replied_at   = $activity->relations['subject']->created_at;
                    $_userPerformanceLog->user_id           = (!isset(Auth::user()->id)) ? 0 : Auth::user()->id;
                    $_userPerformanceLog->save();
                }
                else if( $ticketMessages->count() > 1 && $ticketMessagesFromEmailSupport->count()  )
                {
                    $newUserPerformanceLog = \App\UserPerformanceLog::create([
                        'ticket_id'         => $activity->relations['subject']->ticket_id,
                        'name'              => 'User Replied',
                        'description'       => 'Logged First User Replied DateTime',
                        'user_replied_at'   => $activity->relations['subject']->created_at,
                        'user_id'           => (!isset(Auth::user()->id)) ? 0 : Auth::user()->id,
                    ]);
                }
                
            }

        }

    }

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} a Message";
    }

    public function assignedTo()
    {
        return $this->hasOne('App\AssignedTicket', 'ticket_id', 'ticket_id');
    }

    public function ticket()
    {
        return $this->belongsTo('App\Ticket', 'ticket_id');
    }

    public function activityLogs()
    {
        return $this->hasMany('App\ActivityLog', 'subject_id','id');
    }

}
