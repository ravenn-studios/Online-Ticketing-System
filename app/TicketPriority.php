<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketPriority extends Model
{
    public $table = 'ticket_priorities';
    // protected $fillable = ['product_id','current_stock','starting_stock','low_level_stock','minimum_order','has_pending_order','deleted_at'];

    CONST STATUS_ACTIVE   = 1;
    CONST PRIORITY_LOW    = 1;
    CONST PRIORITY_NORMAL = 2;
    CONST PRIORITY_HIGH   = 3;
    CONST PRIORITY_URGENT = 4;

    CONST PRIORITY_LIST = Array(
        self::PRIORITY_LOW => Array(
            'badge_class' => 'badge-secondary',
        ),
        self::PRIORITY_NORMAL => Array(
            'badge_class' => 'badge-primary',
        ),
        self::PRIORITY_HIGH => Array(
            'badge_class' => 'badge-warning',
        ),
        self::PRIORITY_URGENT => Array(
            'badge_class' => 'badge-danger',
        ),
    );

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function ticket()
    {
        return $this->belongsTo('App\Ticket');
    }

}
