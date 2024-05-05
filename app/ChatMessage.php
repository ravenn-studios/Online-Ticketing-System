<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    public $table = 'chat_messages';

    protected $fillable = [
        'chat_id',
        'message',
        'user_id',
        'from',
        'file_id',
        'read',
    ];

    public function chat()
    {
        return $this->belongsTo('App\Chat','chat_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id');
    }

}
