<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\EmailSupportAddress;
use Illuminate\Support\Facades\Auth;
use App\GmailApi;
use App\Emailer;

class checkAwaitingShipment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minute:checkAwaitingShipment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for orders that are in awaiting shipment for n of days';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {


        /**
         * Automation email to customers for order shipped over 72 hours
         *
         */

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://labelprocessing.blackedgedigital.com/api/checkOrders', [
            'form_params' => [
                'status' => 9,
                // 'status' => 'awaiting shipment',
            ]
        ]);
        //9

        if ( $response->getStatusCode() == 200 )
        {
            
            $data = json_decode($response->getBody(), true);
            $data = $data['data'];

            if ( count($data) )
            {

                $bcc = [
                    'rodneydcro3@gmail.com',
                    // 'info@email.com',
                    // 'info@email.com',
                    // 'info@email.com',
                ];

                /*$bcc = [
                    'rodney@frankiesautoelectrics.com.au',
                    'fortune@frankiesautoelectrics.com.au',
                    // 'anne@frankiesautoelectrics.com.au',
                    'anne@brandbeast.com.au',
                    'karina@frankiesautoelectrics.com.au',
                    'althey@frankiesautoelectrics.com.au',
                ];*/

                $ctr = 0;

                foreach( $data as $key => $val )
                {
                    $orderNumber   = $val['api_order_id'];
                    $dateCreated   = $val['date_created'];
                    $firstName     = $val['first_name'];
                    $lastName      = $val['last_name'];
                    $customerEmail = $val['email'];

                    // mailAwaitingShipment
                    if( $ctr < 3)
                    {                    
                        // $emailer = new Emailer;
                        // $emailer->mailAwaitingShipment($orderNumber, $customerEmail, $firstName .' '. $lastName, $bcc);
                    }

                    $ctr++;
                }

            }

        }

    }
}
