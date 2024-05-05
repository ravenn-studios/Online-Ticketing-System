<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Log;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class Reminder extends Model
{
    use SoftDeletes;
    use LogsActivity;

    public    $table = 'reminders';
    protected $dates = ['deleted_at'];

	CONST TYPE_USER_GENERATED   = 1;
	CONST TYPE_SYSTEM_GENERATED = 2;

    CONST TITLE_PENDING_TICKET         = 'Pending Tickets';
    CONST TITLE_TICKET_LEFT_UNATTENDED = 'Tickets Left Unattended';
    CONST TITLE_UNASSIGNED_TICKET      = 'Unassigned Tickets';

    CONST STATUS_PENDING  = 0;
    CONST STATUS_DONE     = 1;
    CONST STATUS_INACTIVE = 2;

	CONST RE_NOTIFY_UNREAD_STATUS_DONE_AFTER_HOUR = 5; // tmp

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'ticket_id',
        'reminder_interval_id',
        'title',
        'description',
        'for_user',
        'type',
        'notify_at',
        'status_id',
        'read',
        're_notify',
        'created_at',
        'updated_at',
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'id',
        'ticket_id',
        'reminder_interval_id',
        'title',
        'description',
        'for_user',
        'type',
        'notify_at',
        'status_id',
        'read',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'Reminder';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} a Reminder";
    }


    public function scopeSystemReminders($query)
    {
        return $query->where('type', self::TYPE_SYSTEM_GENERATED); 
    }

    public function scopeAuthUserReminders($query)
    {
        return $query->where('type', self::TYPE_USER_GENERATED)
        			 ->where('for_user', Auth::id())
        			 // ->where('read', false)
        			 ->where('status_id', self::STATUS_PENDING);
    }

    public function scopeAuthUserUnreadReminders($query)
    {
        return $query->where('type', self::TYPE_USER_GENERATED)
                     ->where('for_user', Auth::id())
                     ->where('read', false)
                     ->where('status_id', self::STATUS_DONE);
    }

    public function interval()
    {
        return $this->hasMany('App\ReminderInterval', 'id', 'reminder_interval_id');
    }

    public function intervalRecords()
    {
        return $this->hasMany('App\ReminderIntervalRecord', 'id', 'reminder_id');
    }

}
