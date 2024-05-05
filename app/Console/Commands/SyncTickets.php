<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Filter;
use Google_Service_Gmail_FilterCriteria;
use Google_Service_Gmail_FilterAction;
use App\GmailApi;
use App\EbayAPI;
use Log;
use DB;
use App\EmailSupportAddress;
use \DTS\eBaySDK\Constants;
use \DTS\eBaySDK\Trading\Types;
use \DTS\eBaySDK\Trading\Enums;
use \DTS\eBaySDK\Trading\Services;
use App\Ticket;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\TicketOrigin;

class SyncTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minute:syncTickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Tickets to the app';

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
        
        Log::info('Initiating Cron Job: Minute SyncTickets');

        $start = date('h:i:s');
        
        GmailApi::createTickets();
        
        // EbayAPI::syncEbayMessages();
        
        $end = date('h:i:s');

        Log::info('Sync Tickets Start Time: '.$start);
        Log::info('Sync Tickets End Time: '.$end);

    }
}
