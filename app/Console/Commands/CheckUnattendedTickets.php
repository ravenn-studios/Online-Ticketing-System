<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyUnattendedTickets;
use App\Ticket;
use App\TicketOrigin;
use App\SpamFilter;

class CheckUnattendedTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'minute:checkUnattendedTickets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify users via email for unattended tickets over an hour.';

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

        $emailsToExclude = ['support@frankiesautoelectrics.com.au', 'sales@frankiesautoelectrics.com.au','eBay','csfeedback@go.ebay.com'];
        $spamFilters = SpamFilter::where('type', SpamFilter::TYPE_EMAIL)->get('keyword')->toArray();
        $spamFilters = array_column($spamFilters, 'keyword');
        $spamFilters = array_merge($spamFilters, $emailsToExclude);

        //pending tickets that are over 2hours and less than 24hours with no response from agent yet..
        $tickets = Ticket::excludeFacebook()
                    ->where('read', false)
                    ->whereHas('messages', function($q) use($spamFilters){
                        $q->latest()->whereNotIn('from', $spamFilters)
                          ->orderBy('created_at', 'DESC');
                    })
                    // ->where('created_at', '>', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                    ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(1)->toDateTimeString(), \Carbon\Carbon::now()->subHours(2)->toDateTimeString()])
                    ->orderBy('updated_at', 'DESC')->get();

        dump($tickets->count());
        foreach($tickets as $ticket)
        {

            dump('Request: ' . $ticket->messages->first()->from);
            dump('Assigned To: ' . $ticket->assignedTo->user->email);

            $tmpTicket      = new Ticket;
            $lastMessage = $ticket->messages->first()->message;

            $decodedMessage = ($ticket->origin_id == TicketOrigin::ORIGIN_EBAY) ? $tmpTicket->setOutgoingLinksToTarget($lastMessage) : $tmpTicket->decodeMessage($lastMessage);
            $decodedMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $decodedMessage );
            // $decodedMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?\>/si', ' ', $decodedMessage );
            $decodedMessage = str_replace("progress-bar", "", $decodedMessage);
            $decodedMessage = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');


            /*if ( $ticket->origin_id != TicketOrigin::ORIGIN_EBAY )
            {
                //temporary fix for plain text, for some reason on some messages the gmail only returns text/plain and not text/html
                //if message has no html, automatically add <p> on every white/breaklines
                if ( $decodedMessage == strip_tags($decodedMessage)  )
                {
                    $decodedMessage = preg_replace("/[\r\n]/","<p></br>",$decodedMessage);
                }

                $decodedMessage = strip_tags($decodedMessage);
                $decodedMessage = str_replace(array("\n", "\r"), '', $decodedMessage);
            }*/

            /*$emailTo = 'rodney@frankiesautoelectrics.com.au';
            $result = Mail::to($emailTo)
                            ->bcc('rodneydcro3@gmail.com')
                            ->send(new NotifyUnattendedTickets(
                                    $ticket->id,
                                    $ticket->subject,
                                    $decodedMessage,
                                    $ticket->messages->first()->from,
                                    $ticket->messages->first()->created_at->format('M d, Y H:i')
                                )
                            );*/

            /*$emailTo = $ticket->assignedTo->user->email;
            if ( $emailTo == 'anne@frankiesautoelectrics.com.au' )
            {
                $emailTo = 'anne@brandbeast.com.au';
            }*/

            $emailTo = 'rodney@frankiesautoelectrics.com.au';

            $result = Mail::to($emailTo)
                            // ->bcc('rodney@frankiesautoelectrics.com.au')
                            ->send(new NotifyUnattendedTickets(
                                    $ticket->id,
                                    $ticket->subject,
                                    $decodedMessage,
                                    $ticket->messages->first()->from,
                                    $ticket->messages->first()->created_at->format('M d, Y H:i')
                                )
                            );

        }

    }
}
