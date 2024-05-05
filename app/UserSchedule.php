<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class UserSchedule extends Model
{
    
    use SoftDeletes;
    
    public    $table = 'user_schedule';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
     protected $fillable = [
     'id',
     'user_id',
     'mon',
     'tue',
     'wed',
     'thu',
     'fri',
     'sat',
     'sun',
     'created_at',
     'updated_at',
     'deleted_at',
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'id',
        'user_id',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
        'sat',
        'sun',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'User Schedule';


    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} a User Schedule";
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

        }

    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

}
