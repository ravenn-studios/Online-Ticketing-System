<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketStatus extends Model
{
    public $table            = 'ticket_status';
    CONST  STATUS_UNASSIGNED = 1;
    CONST  STATUS_PENDING    = 2;
    CONST  STATUS_SOLVED     = 3;
    CONST  STATUS_CLOSED     = 4;
    
    CONST  STATUS_ACTIVE     = 1;
    
    // protected $fillable = ['product_id','current_stock','starting_stock','low_level_stock','minimum_order','has_pending_order','deleted_at'];

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function ticket()
    {
        return $this->belongsTo('App\Ticket');
    }

    public static function getStatusId($status_name)
    {

        if ( $status_name == null )
            return false;

        $status_name  = strtolower($status_name);
        $status_name  = ucfirst($status_name);
        $ticketStatus = self::where('name', $status_name);

        if ( $ticketStatus->count() )
        {
            return $ticketStatus->first()->id;
        }
        else
        {
            return false;
        }

    }

    public static function getStatusName($status_id)
    {

        if ( $status_id == null )
            return false;

        $ticketStatus = self::where('id', $status_id);

        if ( $ticketStatus->count() )
        {
            return $ticketStatus->first()->name;
        }
        else
        {
            return false;
        }

    }

}
