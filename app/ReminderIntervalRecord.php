<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReminderIntervalRecord extends Model
{
    use SoftDeletes;

    public    $table = 'reminder_interval_records';
    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'reminder_id',
        'user_id',
        'created_at',
        'updated_at',
    ];

    public function reminders()
    {
        return $this->belongsToMany('App\Reminder', 'id','reminder_id');
    }

}
