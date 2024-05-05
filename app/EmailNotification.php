<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Log;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class EmailNotification extends Model
{
    use SoftDeletes;
    // use LogsActivity;

    public    $table = 'email_notifications';
    protected $dates = ['deleted_at'];

	CONST STATUS_PENDING = 0;
    CONST STATUS_DONE    = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'for_user',
        'title',
        'description',
        'status_id',
        'is_notified',
        'created_at',
        'updated_at',
    ];

    /*protected static $ignoreChangedAttributes = ['updated_at'];

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

    protected static $logName = 'Email Notification';

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
        			 ->where('read', false)
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
    }*/

}
