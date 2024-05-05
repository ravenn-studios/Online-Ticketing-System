<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketsTags extends Model
{
    public $table = 'tickets_tags';

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'tag_id'];
}