<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class ReminderInterval extends Model
{
    use SoftDeletes;
    use LogsActivity;

    public    $table = 'reminder_interval';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'day',
        'hour',
        'minute',
        'created_at',
        'updated_at',
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'id',
        'day',
        'hour',
        'minute',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'Reminder Interval';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} a Reminder Interval";
    }

    public function reminders()
    {
        return $this->belongsToMany('App\Reminder', 'id','reminder_interval_id');
    }

}
