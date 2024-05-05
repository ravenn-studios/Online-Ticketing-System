<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\EmailSupportAddress;
use Illuminate\Support\Facades\Auth;
use App\GmailApi;
use App\Emailer;

class CheckOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minute:checkOrders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Orders';

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
         * Automation email to customers for order checked over 48 hours
         *
         */

            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://labelprocessing.blackedgedigital.com/api/checkOrders', [
                'form_params' => [
                    'status' => 'awaiting fulfillment',
                ]
            ]);

            if ( $response->getStatusCode() == 200 )
            {

                // $data = json_decode($response->getBody(), true);
                // $data = $data['data'];
                // foreach( $data as $key => $val )
                // {
                //     $orderNumber = $val['api_order_id'];
                //     $dateCreated = $val['date_created'];
                //     // mailAwaitingFulfillment
                // }

                $customerName  = 'John Doe';
                $customerEmail = 'johndoe@gmail.com';
                $orderNumber   = 134552781;

                $bcc = [
                    'rodneydcro3@gmail.com',
                    // 'info@email.com',
                    // 'info@email.com',
                    // 'info@email.com',
                ];
                $emailer = new Emailer;
                $emailer->mailAwaitingFulfillment($orderNumber, $customerEmail, $customerName, $bcc);

            }


        /**
         * Automation email to customers for order shipped over 72 hours
         *
         */

            /*$client = new \GuzzleHttp\Client();
            $response = $client->request('POST', 'https://labelprocessing.blackedgedigital.com/api/checkOrders', [
                'form_params' => [
                    'status' => 'awaiting shipment',
                ]
            ]);

            if ( $response->getStatusCode() == 200 )
            {

                // $data = json_decode($response->getBody(), true);
                // $data = $data['data'];
                // foreach( $data as $key => $val )
                // {
                //     $orderNumber = $val['api_order_id'];
                //     $dateCreated = $val['date_created'];
                //     // mailAwaitingFulfillment
                // }

                $customerName  = 'John Doe';
                $customerEmail = 'johndoe@gmail.com';
                $orderNumber   = 134552781;

                // $bcc = [
                //     'info@email.com',
                //     'info@email.com',
                //     'info@email.com',
                //     'info@email.com',
                // ];
                // $emailer = new Emailer;
                // $emailer->mailAwaitingFulfillment($orderNumber, $customerEmail, $customerName);

            }*/


        /**
         * Emailer
         *
         */

    }
}