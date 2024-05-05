<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class EmailSupportAddress extends Model
{
    use SoftDeletes;
    use LogsActivity;

    public $table = 'email_support_addresses';

    CONST STATUS_INACTIVE = 0;
    CONST STATUS_ACTIVE   = 1;

    protected $fillable = [
        'email',
        'name',
        'status',
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'email',
        'name',
        'status',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'Email Support Address';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} an Email Support Address";
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

}
