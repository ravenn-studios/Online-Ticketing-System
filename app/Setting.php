<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

// use Spatie\Activitylog\Traits\LogsActivity;
// use Spatie\Activitylog\Contracts\Activity;

class Setting extends Model
{

    // use LogsActivity;
    
    public $table = 'settings';

    const STATUS_INACTIVE                 = 0;
    const STATUS_ACTIVE                   = 1;
    const AUTO_TICKET_DISTRIBUTION = 'auto_ticket_distribution';

    protected $fillable = ['name', 'status'];

    protected static $logAttributes = [
        'name',
        'status',
    ];
    
    // protected static $recordEvents = ['created', 'updated'];
    
    // protected static $logOnlyDirty = true;

    // protected static $logName = 'Settings';

    // public static function getDescriptionForEvent(string $eventName): string
    // {
    //     $eventName = ucfirst($eventName);

    //     return "{$eventName} settings";
    // }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

}
