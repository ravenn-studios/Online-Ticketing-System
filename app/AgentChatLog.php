<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentChatLog extends Model
{
    //chat logs are being created after a customer sent us a message[chat_id, created_at]
    //chat logs [user_id(agent_id), user_replied_at] will added(as an update on the existing log)
    // for the first instance that the user replied to the chat

    use SoftDeletes;

    public $table = 'agent_chat_logs';

    protected $fillable = [
        'user_id',
        'chat_id',
        'ended_at',
        'user_replied_at'
    ];

    // public function chat()
    // {
    //     return $this->has('App\Chat', 'chat_id', 'id');
    // }

    public function chats()
    {
        return $this->belongsToMany('App\Chat','chat_id','id');
    }

}
