<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketLimit extends Model
{

	public $table = 'ticket_limit';

    protected $fillable = ['user_id', 'limit'];

    public function user()
    {
        return $this->belongsTo('App\User');
    }
}