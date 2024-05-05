<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\EmailSupportAddress;
use Illuminate\Support\Facades\Auth;
use App\GmailApi;
use App\Emailer;

class CheckAwaitingFulfillment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minute:checkAwaitingFulfillment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for orders that are in awaiting fulfillment for n of days';

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
                'status' => 11,
                // 'status' => 'awaiting fulfillment',
            ]
        ]);
        // 11

        if ( $response->getStatusCode() == 200 )
        {

            logger('awaiting fulfillment status 200');

            $data = json_decode($response->getBody(), true);
            $data = $data['data'];

            if ( count($data) )
            {

                $bcc = [
                    'rodney@frankiesautoelectrics.com.au',
                    'fortune@frankiesautoelectrics.com.au',
                    // 'anne@frankiesautoelectrics.com.au',
                    'anne@brandbeast.com.au',
                    // 'karina@frankiesautoelectrics.com.au',
                    'althey@frankiesautoelectrics.com.au',
                ];

                foreach( $data as $key => $val )
                {
                    $orderNumber   = $val['api_order_id'];
                    $dateCreated   = $val['date_created'];
                    $firstName     = $val['first_name'];
                    $lastName      = $val['last_name'];
                    $customerEmail = $val['email'];

                    // mailAwaitingFulfillment
                    $emailer = new Emailer;
                    if ( isset($val['platform_id']) && $val['platform_id'] == 2 ) // if its ebay or not
                    {
                        $emailer->mailAwaitingFulfillment($orderNumber, $customerEmail, $firstName .' '. $lastName, $bcc, true);
                    }
                    else
                    {
                        $emailer->mailAwaitingFulfillment($orderNumber, $customerEmail, $firstName .' '. $lastName, $bcc, false);
                    }
                }

            }

        }

    }
}
