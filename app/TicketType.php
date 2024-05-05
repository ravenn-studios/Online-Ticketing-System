<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    public $table = 'ticket_types';
    
    const TYPE_QUESTION             = 1;
    const TYPE_PROBLEM              = 2;
    const TYPE_SPAM                 = 3;
    const TYPE_AWAITING_FULFILLMENT = 4;
    const TYPE_AWAITING_SHIPMENT    = 5;
    
    const STATUS_ACTIVE = 1;
    // protected $fillable = ['product_id','current_stock','starting_stock','low_level_stock','minimum_order','has_pending_order','deleted_at'];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function ticket()
    {
        return $this->belongsTo('App\Ticket');
    }
}
