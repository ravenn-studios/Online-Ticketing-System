<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class Signature extends Model
{
    
    use SoftDeletes;
    use LogsActivity;

    protected $dates   = ['deleted_at'];

    public    $table   = 'signatures';

    const     INACTIVE = 0;
    const     ACTIVE   = 1;

    protected $fillable = ['user_id', 'name', 'content', 'active'];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'user_id',
        'name',
        'content',
        'active'
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'Email Signature';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);
        
        return "{$eventName} an Email Signature";
    }

}
