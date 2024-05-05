<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Storage;
use Log;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class SpamFilter extends Model
{

    use SoftDeletes;
    use LogsActivity;

    public    $table = 'spam_filters';
    protected $dates = ['deleted_at'];

    CONST TYPE_EMAIL = 1;
    CONST TYPE_TEXT  = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'keyword',
        'type',
        'action_by',
        'created_at',
        'updated_at',
    ];

    protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'id',
        'keyword',
        'type',
        'action_by',
        'created_at',
        'updated_at',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'SpamFilter';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} a SpamFilter";
    }

    public function scopeExists($query, $keyword)
    {
        return $query->where('keyword', $keyword);
    }

    public function scopeEmails($query)
    {
        return $query->where('type', self::TYPE_EMAIL);
    }

    public function scopeKeywords($query)
    {
        return $query->where('type', self::TYPE_TEXT);
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'action_by', 'id');
    }

}
