<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\CustomerMessaged;

class CustomerMessagedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CustomerMessaged $event)
    {
        // if($event->a > $event->b)
        // {
        //      // call the second handler or return it as result
        //      return true;
        // }
        
        // echo 'b is greater';
        return $event;
    }
}
