<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;

class UserCustomPage extends Model
{
    
    use SoftDeletes;
    use LogsActivity;

    public    $table = 'user_custom_pages';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id',
        'name',
        'slug',
    ];

    // protected static $ignoreChangedAttributes = ['updated_at'];

    protected static $logAttributes = [
        'user_id',
        'name',
        'slug',
    ];
    
    protected static $recordEvents = ['created', 'updated', 'deleted', 'restored'];
    
    protected static $logOnlyDirty = true;

    protected static $logName = 'Custom Page';

    public static function getDescriptionForEvent(string $eventName): string
    {
        $eventName = ucfirst($eventName);

        return "{$eventName} a Custom Page";
    }


    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function pageConditions()
    {
        return $this->hasMany('App\CustomPageCondition', 'custom_page_id', 'id');
    }

}
