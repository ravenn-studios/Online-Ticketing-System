<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketCategories extends Model
{
    public $table = 'ticket_categories';

    public $timestamps = false;

    protected $fillable = ['ticket_id', 'category_id'];
}