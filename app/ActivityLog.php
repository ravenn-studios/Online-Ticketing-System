<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $table = 'activity_log';

    public function scopeDescending($query)
    {
            return $query->orderBy('id','DESC');
    }

    // public static function getLogNameByDescription($description)
    // {
    //     $logNames = Array(
    //         'You have received an order'  => 'Order Received',
    //         'You have cancelled an order' => 'Order Cancelled',
    //         'You have created an order'   => 'Order Created',
    //         'You have deleted an order'   => 'Order Deleted',
    //         'You have updated an order'   => 'Order Updated',
    //         'You have restored an order'  => 'Order Restored',
    //         'You have updated a user'  => 'User Updated',
    //         'You have created a user'  => 'User Created',
    //         'You have deleted a user'  => 'User Deleted',
    //         'You have restored a user'  => 'User Restored',
    //         'You have sent a reset password link'  => 'Reset User Password',`
    //         'A user had reset his/her password.'  => 'Reset User Password',
    //         'You have update a user role'  => 'User Role Update',
    //         'You have updated an order item'  => 'Order Item Update',
    //         'You have created an order item'  => 'Order Item Created',
    //         'You have deleted an order item'  => 'Order Item Deleted',
    //     );

    //     if( !isset($logNames[$description]) ) {
    //         return 'Log Name for Description not found.';
    //     }
        
    //     return $logNames[$description];
    // }

}
