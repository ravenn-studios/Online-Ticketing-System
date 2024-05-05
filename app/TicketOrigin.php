<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketOrigin extends Model
{
    
    public $table = 'ticket_origin';
    // protected $fillable = ['product_id','current_stock','starting_stock','low_level_stock','minimum_order','has_pending_order','deleted_at'];

    CONST STATUS_ACTIVE   = 1;
    CONST ORIGIN_GMAIL    = 1;
    CONST ORIGIN_EBAY     = 32;
    CONST ORIGIN_FACEBOOK = 73;
    CONST ORIGIN_CHAT = 177;
    CONST TICKETING_APP   = 78;
    CONST ACTIVE          = 1;
    CONST INACTIVE        = 0;

    protected $fillable = ['name', 'status'];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

}
