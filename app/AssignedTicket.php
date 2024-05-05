<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Log;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class AssignedTicket extends Model
{

    use LogsActivity;

    public $table = 'assigned_tickets';

    public $timestamps = false;

    protected $fillable = ['id', 'user_id', 'ticket_id'];

    protected static $logAttributes = ['id', 'user_id', 'ticket_id'];
    
    // protected static $recordEvents = ['created', 'updated'];

    // public $incrementing = true;
    
    // protected static $logOnlyDirty = true;

    protected static $logName = 'Assigned Ticket';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        // if ($eventName == 'created')
        // {
        //     return 'Assigned a Ticket';
        // }
        // else if($eventName == 'updated')
        // {
        //     return 'Re-Assigned a Ticket';
        // }

        return "{$eventName} Assigned Ticket";
    }

    // public static function getDescriptionForEvent(string $eventName): string
    // {
    //     $eventName = ucfirst($eventName);

    //     return "{$eventName} a Ticket";
    // }

    // public function tapActivity(Activity $activity, string $eventName)
    // {
    //     App\Log::info(0000);
    // }
    //com


    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id')->withTrashed();
    }

}
