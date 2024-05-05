<?php

namespace App\Exports;

use Illuminate\Support\Facades\Auth;

use App\User;
use App\Role;
use App\Ticket;
use App\Message;
use App\TicketType;
use App\TicketPriority;
use App\TicketStatus;
use App\CustomVariable;
use App\EmailTemplate;
use App\AssignedTicket;
use App\UserCustomPage;
use App\CustomPageCondition;
use App\Category;
use App\UserPerformanceLog;
use App\EmailSupportAddress;
use DateTime;
use DateTimeZone;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AgentsMultisheetExport implements WithMultipleSheets
{

	use Exportable;

	private $userIds   = [];
	private $dateRange = [];
	private $viewReportData = [];
    private $user;

    // public function __construct(int $userIds)
    public function __construct(array $userIds, $dateRange = null, array $viewReportData)
    {
		$this->userIds        = $userIds;
		$this->dateRange      = $dateRange == null ? '' : $dateRange;
		$this->viewReportData = $viewReportData;
    }

    /*public function setData()
    {
    	$user                    = User::find($userId);
		$tickets                   = $this->getAgentTicketsData($userId);
		$agentTicketsCountByStatus = $this->getUserTicketsCountByStatus($userId);
		$ticketsPendingCount      = $agentTicketsCountByStatus['pending'];
		$ticketsSolvedCount       = $agentTicketsCountByStatus['solved'];
		$ticketsClosedCount       = $agentTicketsCountByStatus['closed'];
    }*/

    public function sheets(): array
    {
        logger('start AgentsMultisheetExport.......');

        $sheets = [];
        
        //foreach users, new agent performance export

        foreach ( $this->userIds as $userId ) {

        	logger('looping AgentsMultisheetExport.......');

			$user                       = User::find($userId);
			$this->user                 = $user;

			$t = microtime(true);
			$agentsTicketsCountByStatus = $this->getUsersTicketsCountByStatus([0 => (int)$userId], $this->dateRange);
			$t1 = microtime(true);
			logger('getUsersTicketsCountByStatus: '. ($t1 - $t) .'seconds');

			$t = microtime(true);
			// $agentsTicketsData          = $this->getAgentsTicketsData([0 => $userId], $this->dateRange, $agentsTicketsCountByStatus);
			$ticket = new Ticket;
			$agentsTicketsData          = $ticket->getAgentsTicketsData([0 => $userId], $this->dateRange, $agentsTicketsCountByStatus);
			$t1 = microtime(true);
			logger('getAgentsTicketsData: '. ($t1 - $t) .'seconds');

			$tickets                    = $agentsTicketsData['tickets'];
			// dd($tickets);
			$agentsTicketsCountByStatus = $agentsTicketsData['agentsTicketsCountByStatus'];
			// dd($this->dateRange);

			$sheets[$user->name . ' - ' . implode('-', $this->dateRange)] = new AgentsPerformanceExport($user, $tickets, $agentsTicketsCountByStatus, $this->dateRange, false, $this->viewReportData);

        }

        logger('end AgentsMultisheetExport.......');

        return $sheets;

    }

    /*public function sheets(): array
    {

        $sheets = [];

        //foreach users, new agent performance export

        $_userIds = [];
        foreach ( $this->userIds as $userId ) {

			$user                      = User::find($userId);
			$this->user 			   = $user;
			$tickets                   = $this->getAgentTicketsData($userId, $this->dateRange);
			$agentTicketsCountByStatus = $this->getUserTicketsCountByStatus($userId, $this->dateRange);
			// $agentTicketsCountByStatus = $this->getUserTicketsCountByStatus($_userIds, $this->dateRange);

			$sheets[$user->name . ' - ' . implode('-', $this->dateRange)] = new AgentsPerformanceExport($user, $tickets, $agentTicketsCountByStatus, $this->dateRange, false);

        }

        return $sheets;

    }*/

    public function getAgentTicketsData($userId, $dateRange)
    {

    	if ( !empty($userId) )
    	{

	        $_emailSupportAddresses = $this->emailSupportAddresses();

	        $user = User::find($userId);

	        // $tickets = Ticket::whereHas('assignedTo', function($a) use($userId){
	        //             $a->where('user_id', $userId) // change to var
	        //                 ->whereNested(function($a) {
	        //                     // $a->where('tickets.status_id', Ticket::STATUS_PENDING)
	        //                     $a->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	        //                         ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	        //                 });
	        //         })
	        //         ->get();
	        // dump($dateRange);
            if ( !empty($dateRange) )
	        {
	        	// dd($userId);
	        	$tickets = Ticket::whereHas('assignedTo', function($a) use($userId){
	                    $a->where('user_id', $userId) // change to var
	                        ->whereNested(function($a) {
	                            $a->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
	                              ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	                              ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	                        });
		                })
	        			->whereBetween('created_at', $dateRange)
	        			->orderBy('updated_at', 'DESC')
		                ->get();
	        }
	        else
	        {
	        	$tickets = Ticket::whereHas('assignedTo', function($a) use($userId){
	                    $a->where('user_id', $userId) // change to var
	                        ->whereNested(function($a) {
	                            $a->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
	                              ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	                              ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	                        });
		                })
	        			->orderBy('updated_at', 'DESC')
		                ->get();
	        }


			$count_tickets_solved_in_a_day = $count_tickets_solved_over_a_day = $count_tickets_closed_in_a_day = $count_tickets_closed_over_a_day = 0;
			$ticketOpenedToSolvedTime      = $ticketOpenedToClosedTime = $ticketSolvedToClosedTime      = [];
			// dd($tickets->toArray());
	        foreach($tickets as $_key => $ticket)
	        {

	            $ticket->messages;

	            $ticket->agent_reply_count = $ticket->messages->whereIn('from', $_emailSupportAddresses)->count();

	            $ticketMessagesTimeStamp = [];

	            // will be use as a flag if previous message is from customer or not, to avoid getting incorrect timestamps when theres 2 consecutive replies
	            $isLastMessageCustomer   = false; // default true, since we need to start on customer message
	            $_ctr                    = 0; // tmp counter will be used once for first customer message

	            //average response time on chat
	            foreach($ticket->messages as $key => $message)
	            {

	                /*
	                 *  GET MESSAGES TIMESTAMP for agents response time
	                 *  PENDING -> NEED TO FIND A WAY to COMPUTE CORRECTLY THE AVG RESPONSE TIME OF CUSTOMERS MESSAGES/REPLIES AFTER COB
	                */

	                /* if first iteration and message is from agent then skip
	                 * start only getting response times from the first message of customer to get response time of agent after that
	                */

	                // if ( $key == 0 && in_array($message->from, $_emailSupportAddresses) )
	                // {
	                //     continue;
	                // }
	                // else
	                // {
	                //     $_ctr++;

	                //     /* insert timestamp in array if true for first customer message
	                //      * else insert only if current loop message "from" is not the same as the last message "from"
	                //     */
	                //     if ( $_ctr == 1 && !in_array($message->from, $_emailSupportAddresses) )
	                //     {
	                //         $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
	                //     }
	                //     else
	                //     {

	                //     	//!!!!@@to follow, we need to get correct message timestamps starting from the customer reply / first message
	                //     	if ( empty($ticketMessagesTimeStamp) && in_array($message->from, $_emailSupportAddresses) )
	                //     	{
	                //     		continue;
	                //     	}


	                //         /* if $isLastMessageCustomer == true then this current loop should be from agent
	                //          * else should be from customer
	                //         */
	                //         if ( $isLastMessageCustomer )
	                //         {
	                //             if ( in_array($message->from, $_emailSupportAddresses) )
	                //             {
	                //                 $isLastMessageCustomer     = false;
	                //                 $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
	                //             }
	                //         }
	                //         else
	                //         {
	                //             if ( !in_array($message->from, $_emailSupportAddresses) )
	                //             {
	                //                 $isLastMessageCustomer     = true;
	                //                 $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
	                //             }
	                //         }

	                //     }

	                // }


	                /*
                     * start always with customers message then get the agent next etc.
                    */
                        
                    if ( !$isLastMessageCustomer && ( !in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                    // if ( !$isLastMessageCustomer && !in_array($message->from, $_emailSupportAddresses) && empty($ticketMessagesTimeStamp) )
                    {
                        $isLastMessageCustomer     = true;
                        $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
                    }
                    // elseif ( $isLastMessageCustomer && in_array($message->from, $_emailSupportAddresses) )
                    elseif ( $isLastMessageCustomer && ( in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                    {
                        $isLastMessageCustomer     = false;
                        $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
                    }

	            }

	            /* compute average response time of agents
	             * chunk by 2, to compare
	            */
	            $ticket->agent_average_response_time = '';
	            $ticketMessagesTimeStamp             = array_chunk($ticketMessagesTimeStamp, 2);
	            
	            // if ( $ticket->id == 2886 )
	            // 	dump($ticketMessagesTimeStamp);

	            if ( !empty($ticketMessagesTimeStamp) )
	            {

	                $noOfChunks           = 0;
	                $messagesResponseTime = [];
	                foreach($ticketMessagesTimeStamp as $timestamp)
	                {

	                    if( count($timestamp) > 1 )
	                    {

	                        $noOfChunks++;

	                        $start  = new \Carbon\Carbon($timestamp[0]);
	                        $end    = new \Carbon\Carbon($timestamp[1]);

	                        $messagesResponseTime[] = $start->diffInSeconds($end);

	                    }

	                }

	                if( !empty($messagesResponseTime) )
	                {
						$seconds                                      = array_sum($messagesResponseTime) / count($messagesResponseTime);
						$ticket->agent_average_response_time          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
						$ticket->agent_average_response_time_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
	                }

	            }

	            
	            /*
	             * get ticket statuses durations, agent response time through performance logs table.
	            */

	            $ticket->solved_duration = $ticket->closed_duration = $ticket->agent_first_response_duration = '';
	            if ( $ticket->performanceLogs->count() )
	            {

	                $ticketSolvedAt = '';
	                $ticketClosedAt = '';
	                foreach( $ticket->performanceLogs as $performanceLog )
	                {

	                    if ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_SOLVED && $ticket->status_id == Ticket::STATUS_SOLVED)
	                    {
	                        $ticketSolvedAt = $performanceLog->created_at;

	                        $openedTicket = $ticket->performanceLogs()->where('description', 'Opened a Ticket')->first();
	                        if ( $openedTicket )
	                        {
	                        	$ticketOpenedToSolvedTime[] = $openedTicket->created_at->diffInSeconds($ticketSolvedAt);
	                        }

	                        $closedTicket = $ticket->performanceLogs()->where('property', 'status_id')->where('property_value', Ticket::STATUS_CLOSED)->first();
	                        if ( $closedTicket )
	                        {
	                        	$ticketSolvedToClosedTime[] = $closedTicket->created_at->diffInSeconds($ticketSolvedAt);
	                        }

	                    }
	                    elseif ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_CLOSED && $ticket->status_id == Ticket::STATUS_CLOSED)
	                    {
	                        $ticketClosedAt = $performanceLog->created_at;

	                        $openedTicket = $ticket->performanceLogs()->where('description', 'Opened a Ticket')->first();
							if ( $openedTicket )
							{
								$ticketOpenedToClosedTime[] = $openedTicket->created_at->diffInSeconds($ticketClosedAt);
							}
	                    }

	                    // if ($performanceLog->user_replied_at)
	                    // {
	                    //     $ticket->agent_first_response_duration = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
	                    // }

	                    //test
	                    // if ($ticket->id == 2886)
	                    // {

	                    	// if ($performanceLog->user_replied_at && $ticket->id == 2882)
	                    	if ($performanceLog->user_replied_at)
		                    {

								$ticketCreatedAt   = new \Carbon\Carbon($ticket->created_at);
								$userRepliedAt     = new \Carbon\Carbon($performanceLog->user_replied_at);

								$ticketCreatedDate = $ticketCreatedAt->format('Y-m-d G:i:s');
								$userRepliedDate   = $userRepliedAt->format('Y-m-d G:i:s');

		                        
		                        $agentFirstResponseSameDay = ($ticketCreatedAt->format('Y-m-d') == $userRepliedAt->format('Y-m-d') ? true: false);

		                        // $test =  \Carbon\Carbon::parse('2019-06-13')->isSameAs('d', \Carbon\Carbon::parse('2019-12-13'));
		                        // dump($test);
		                        //use 00:00:00 on h:i:s to exactly get the last day
								$period                     = \Carbon\CarbonPeriod::create($ticketCreatedAt->format('Y-m-d 00:00:00'), $userRepliedAt->format('Y-m-d 00:00:00'));
								$agentFirstResponseDuration = 0; // get total time when agent first responded
								// Iterate over the period

						    	// dump(count($period));
								foreach ($period as $date) // loop through dates, count time duration in between work  and outside work hours
								{
									// dump($date->format('Y-m-d'));
									$startAgentShift = \Carbon\Carbon::parse( $date->format('Y-m-d') . ' 06:00:00' );
									$endAgentShift   = \Carbon\Carbon::parse( $date->format('Y-m-d') . ' 15:00:00' );

								    if ($agentFirstResponseSameDay)
								    {
								    	//should have condition to determine if ticket was created between or outside the shift...
								    	// $ticket->agent_first_response_duration = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
								    	if ( $ticketCreatedAt < $startAgentShift )
							    		{
											$ticket->agent_first_response_duration          = $this->getActivityDuration($startAgentShift, $performanceLog->user_replied_at);
											$ticket->agent_first_response_duration_detailed = $this->getDetailedActivityDuration($startAgentShift, $performanceLog->user_replied_at);
							    			break;
							    		}
							    		else
							    		{
											$ticket->agent_first_response_duration          = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
											$ticket->agent_first_response_duration_detailed = $this->getDetailedActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
							    			break;
							    		}
								    }
								    else
								    {
										
										//on first day
								    	if ( $ticketCreatedAt->format('Y-m-d') == $date->format('Y-m-d') )
								    	{
								    		// dump($ticketCreatedAt->format('Y-m-d') .' : '. $date->format('Y-m-d'));
								    		/* Get the correct agent_first_response_duration on first day
								    		 * on first loop, check if ticket created before the shift. then Start Shift - End Shift
								    		 * else Ticket created_at - end shift
								    		 */
								    		if ( $ticketCreatedAt < $startAgentShift )
								    		{
								    			$agentFirstResponseDuration += $startAgentShift->diffInSeconds($endAgentShift);
								    		}
								    		// else
								    		elseif ( $ticketCreatedAt > $startAgentShift && $ticketCreatedAt < $endAgentShift )
								    		{
								    			$agentFirstResponseDuration += $ticketCreatedAt->diffInSeconds($endAgentShift);
								    		}

								    	}
								    	else
								    	{
								    		//days in between , end date

								    		//end date
								    		if ( $userRepliedAt->format('Y-m-d') == $date->format('Y-m-d') )
								    		{
								    			$agentFirstResponseDuration += $startAgentShift->diffInSeconds($userRepliedAt);
								    		}
								    		else
								    		{
								    			$agentFirstResponseDuration += $startAgentShift->diffInSeconds($endAgentShift);
								    		}

								    	}

								    }

								}

								if ( !$agentFirstResponseSameDay )
								{
									$ticket->agent_first_response_duration          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans());
									$ticket->agent_first_response_duration_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans(['parts' => 2]));
								}

		                    }

	                    // }

	                }

	                // ticket duration before its solved/closed
	                if ( !empty($ticketSolvedAt) )
	                {
	                	// dump($ticket->id . ': ' . $ticketSolvedAt->diff($ticket->created_at)->format('%D:%H:%I:%S'));
						$ticket->solved_duration          = $this->getActivityDuration($ticket->created_at, $ticketSolvedAt);
						$ticket->detailed_solved_duration = $this->getDetailedActivityDuration($ticket->created_at, $ticketSolvedAt);

	                    if ( $ticket->created_at->diffInMinutes($ticketSolvedAt) <= 1440 ) //tickets solved within 24hrs else over a day
	                   	{
	                   		$count_tickets_solved_in_a_day++;
	                   	}
	                   	else
	                   	{
	                   		$count_tickets_solved_over_a_day++;
	                   		$ticket->solved_over_a_day = true;
	                   	}

	                }

	                if ( !empty($ticketClosedAt) )
	                {

	                	// dump($ticket->id . ': ' . $ticketClosedAt->diff($ticket->created_at)->format('%D:%H:%I:%S'));
						$ticket->closed_duration          = $this->getActivityDuration($ticket->created_at, $ticketClosedAt);
						$ticket->detailed_closed_duration = $this->getDetailedActivityDuration($ticket->created_at, $ticketClosedAt);

	                   	if ( $ticket->created_at->diffInMinutes($ticketClosedAt) <= 1440 ) //tickets closed within 24hrs else over a day
	                   	{
	                   		$count_tickets_closed_in_a_day++;
	                   	}
	                   	else
	                   	{
	                   		$count_tickets_closed_over_a_day++;
	                   	}

	                }

	                if ( $tickets->count() - 1 == $_key ) // add extra data to the last ticket record for now..
		            {
						$ticket->count_tickets_solved_in_a_day   = $count_tickets_solved_in_a_day;
						$ticket->count_tickets_solved_over_a_day = $count_tickets_solved_over_a_day;
						$ticket->count_tickets_closed_in_a_day   = $count_tickets_closed_in_a_day;
						$ticket->count_tickets_closed_over_a_day = $count_tickets_closed_over_a_day;

						// dd($ticketOpenedToSolvedTime);
						if( !empty($ticketOpenedToSolvedTime) )
		                {
							$seconds                                               = array_sum($ticketOpenedToSolvedTime) / count($ticketOpenedToSolvedTime);
							$ticket->average_time_ticket_opened_to_solved          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
							$ticket->average_time_ticket_opened_to_solved_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
		                }

		                if( !empty($ticketSolvedToClosedTime) )
		                {
							$seconds                                              = array_sum($ticketSolvedToClosedTime) / count($ticketSolvedToClosedTime);
							$ticket->average_time_ticket_solved_to_closed          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
							$ticket->average_time_ticket_solved_to_closed_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
		                }

		                if( !empty($ticketOpenedToClosedTime) )
		                {
							$seconds                                               = array_sum($ticketOpenedToClosedTime) / count($ticketOpenedToClosedTime);
							$ticket->average_time_ticket_opened_to_closed          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
							$ticket->average_time_ticket_opened_to_closed_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
		                }

		                // dd($ticket);

		            }

	            }

	        }

	        // dd($tickets);

	        return $tickets;

    	}
    	else
    	{
    		return false;
    	}

    }

    public function getAgentsTicketsData($userIds, $dateRange, $agentsTicketsCountByStatus)
    {
    	logger('getAgentsTicketsData');
    	if ( !empty($userIds) )
    	{

	        $_emailSupportAddresses = $this->emailSupportAddresses();

	        $users = User::whereIn('id', $userIds)->get();

	        // $tickets = Ticket::whereHas('assignedTo', function($a) use($userId){
	        //             $a->where('user_id', $userId) // change to var
	        //                 ->whereNested(function($a) {
	        //                     // $a->where('tickets.status_id', Ticket::STATUS_PENDING)
	        //                     $a->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	        //                         ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	        //                 });
	        //         })
	        //         ->get();
	        // dump($dateRange);
            $time_start = microtime(true); 

            if ( !empty($dateRange) )
	        {
	        	$tickets = Ticket::with('messages')->whereHas('assignedTo', function($a) use($userIds){
	                    $a->whereIn('user_id', $userIds) // change to var
	                        ->whereNested(function($b) {
	                            $b->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
	                              ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	                              ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	                        });
		                })
	        			->whereBetween('created_at', $dateRange)
	        			->orderBy('updated_at', 'DESC')
		                ->get(['id','thread_id','subject','snippet','requester','receiver','thread_started_at','status_id','priority_id','type_id','created_at','updated_at']);
	        }
	        else
	        {
	        	$tickets = Ticket::with('messages')->whereHas('assignedTo', function($a) use($userIds){
	                    $a->whereIn('user_id', $userIds) // change to var
	                        ->whereNested(function($b) {
	                            $b->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
	                              ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	                              ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	                        });
		                })
	        			->orderBy('updated_at', 'DESC')
		                ->get(['id','thread_id','subject','snippet','requester','receiver','thread_started_at','status_id','priority_id','type_id','created_at','updated_at']);
	        }

            // dump('after daterange query: ' . (microtime(true) - $time_start) ); 

			$count_tickets_solved_in_a_day = $count_tickets_solved_over_a_day = $count_tickets_closed_in_a_day = $count_tickets_closed_over_a_day = 0;
			$ticketOpenedToSolvedTime      = $ticketOpenedToClosedTime = $ticketSolvedToClosedTime      = [];
			// dump($tickets->toArray());
	        foreach($tickets as $_key => $ticket)
	        {
                // dump('---------');
                $time_start = microtime(true);
	        	// dd($ticket->assignedTo->user_id);
	            // $ticket->messages;

                $ticket->agent_reply_count = $ticket->messages->whereIn('from', $_emailSupportAddresses)->count();
                
                $customerReplyCount        = $ticket->messages->whereIn('from', $ticket->requester)->count();

	            $ticketMessagesTimeStamp = [];

                $ticket->solved_duration = $ticket->closed_duration = $ticket->agent_first_response_duration = ''; //ticket statuses durations, agent response time through performance logs table.
                $ticket->agent_average_response_time = ''; //avg response time for agents

	            // will be use as a flag if previous message is from customer or not, to avoid getting incorrect timestamps when theres 2 consecutive replies
	            $isLastMessageCustomer   = false; // default true, since we need to start on customer message
	            $_ctr                    = 0; // tmp counter will be used once for first customer message

                //should have interaction between cs and agents by checking of their message exists in a ticket
                if ( $ticket->agent_reply_count && $customerReplyCount )
                {

    	            //average response time on chat
    	            foreach($ticket->messages as $key => $message)
    	            {

    	                /*
                         * start always with customers message then get the agent next etc.
                        */
                            
                        if ( !$isLastMessageCustomer && ( !in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                        // if ( !$isLastMessageCustomer && !in_array($message->from, $_emailSupportAddresses) && empty($ticketMessagesTimeStamp) )
                        {
                            $isLastMessageCustomer     = true;
                            $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
                        }
                        // elseif ( $isLastMessageCustomer && in_array($message->from, $_emailSupportAddresses) )
                        elseif ( $isLastMessageCustomer && ( in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                        {
                            $isLastMessageCustomer     = false;
                            $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
                        }

    	            }

                    // dump('after ticket->messages query: ' . (microtime(true) - $time_start) . 'sec' );

    	            /* compute average response time of agents
    	             * chunk by 2, to compare
    	            */
    	            $ticketMessagesTimeStamp = array_chunk($ticketMessagesTimeStamp, 2);

    	            if ( !empty($ticketMessagesTimeStamp) )
    	            {

    	                $noOfChunks           = 0;
    	                $messagesResponseTime = $messagesResponseTimeInMinutes = [];
    	                foreach($ticketMessagesTimeStamp as $timestamp)
    	                {

    	                    if( count($timestamp) > 1 )
    	                    {

    	                        $noOfChunks++;

    	                        $start  = new \Carbon\Carbon($timestamp[0]);
    	                        $end    = new \Carbon\Carbon($timestamp[1]);

                                $messagesResponseTime[]          = $start->diffInSeconds($end);
                                $messagesResponseTimeInMinutes[] = $start->diffInMinutes($end);

    	                    }

    	                }

    	                if( !empty($messagesResponseTime) )
    	                {
                            $_minutes                                       = array_sum($messagesResponseTimeInMinutes) / count($messagesResponseTimeInMinutes);
                            $seconds                                        = array_sum($messagesResponseTime) / count($messagesResponseTime);
                            $ticket->agent_average_response_time            = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
                            $ticket->agent_average_response_time_detailed   = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
                            $ticket->agent_average_response_time_in_minutes = $_minutes;
    	                }

    	            }

                    // dump('after ticketMessagesTimeStamp query: ' . (microtime(true) - $time_start) . 'sec' );

    	            
    	            /*
    	             * get ticket statuses durations, agent response time through performance logs table.
    	            */

    	            if ( $ticket->performanceLogs()->count() )
    	            {

    	                $ticketSolvedAt = '';
    	                $ticketClosedAt = '';
    	                foreach( $ticket->performanceLogs() as $performanceLog )
    	                {

    	                    if ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_SOLVED  && $ticket->status_id == Ticket::STATUS_SOLVED)
    	                    {
    	                        $ticketSolvedAt = $performanceLog->created_at;

    	                        $openedTicket = $ticket->performanceLogs()->where('description', 'Opened a Ticket')->first();
    	                        if ( $openedTicket )
    	                        {
    	                        	$ticketOpenedToSolvedTime[] = $openedTicket->created_at->diffInSeconds($ticketSolvedAt);


    	                        	$tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

    		                   		if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToSolvedTime'] ) )
    		                   		{
    		                   			$agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToSolvedTime'] = [];
    		                   		}

    		                   		$agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToSolvedTime'][] = $openedTicket->created_at->diffInSeconds($ticketSolvedAt);

    	                        }

    	                        $closedTicket = $ticket->performanceLogs()->where('property', 'status_id')->where('property_value', Ticket::STATUS_CLOSED)->first();
    	                        if ( $closedTicket )
    	                        {
    	                        	$ticketSolvedToClosedTime[] = $closedTicket->created_at->diffInSeconds($ticketSolvedAt);


    	                        	$tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

    		                   		if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['ticketSolvedToClosedTime'] ) )
    		                   		{
    		                   			$agentsTicketsCountByStatus[$tmpKey]['ticketSolvedToClosedTime'] = [];
    		                   		}

    		                   		$agentsTicketsCountByStatus[$tmpKey]['ticketSolvedToClosedTime'][] = $closedTicket->created_at->diffInSeconds($ticketSolvedAt);
    	                        }

    	                    }
    	                    elseif ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_CLOSED  && $ticket->status_id == Ticket::STATUS_CLOSED)
    	                    {
    	                        $ticketClosedAt = $performanceLog->created_at;

    	                        $openedTicket = $ticket->performanceLogs()->where('description', 'Opened a Ticket')->first();
    							if ( $openedTicket )
    							{
    								$tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

    		                   		if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToClosedTime'] ) )
    		                   		{
    		                   			$agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToClosedTime'] = [];
    		                   		}

    		                   		$agentsTicketsCountByStatus[$tmpKey]['ticketOpenedToClosedTime'][] = $openedTicket->created_at->diffInSeconds($ticketClosedAt);
    							}
    	                    }

                        	// if ($performanceLog->user_replied_at && $ticket->id == 2882)
                        	if ($performanceLog->user_replied_at)
    	                    {

    							$ticketCreatedAt   = new \Carbon\Carbon($ticket->created_at);
    							$userRepliedAt     = new \Carbon\Carbon($performanceLog->user_replied_at);

    							$ticketCreatedDate = $ticketCreatedAt->format('Y-m-d G:i:s');
    							$userRepliedDate   = $userRepliedAt->format('Y-m-d G:i:s');

    	                        
    	                        $agentFirstResponseSameDay = ($ticketCreatedAt->format('Y-m-d') == $userRepliedAt->format('Y-m-d') ? true: false);

    	                        // $test =  \Carbon\Carbon::parse('2019-06-13')->isSameAs('d', \Carbon\Carbon::parse('2019-12-13'));
    	                        // dump($test);
    	                        //use 00:00:00 on h:i:s to exactly get the last day
    							$period                     = \Carbon\CarbonPeriod::create($ticketCreatedAt->format('Y-m-d 00:00:00'), $userRepliedAt->format('Y-m-d 00:00:00'));
    							$agentFirstResponseDuration = 0; // get total time when agent first responded
    							// Iterate over the period

    					    	// dump(count($period));
    							foreach ($period as $date) // loop through dates, count time duration in between work  and outside work hours
    							{
    								// dump($date->format('Y-m-d'));
    								$startAgentShift = \Carbon\Carbon::parse( $date->format('Y-m-d') . ' 06:00:00' );
    								$endAgentShift   = \Carbon\Carbon::parse( $date->format('Y-m-d') . ' 15:00:00' );

    							    if ($agentFirstResponseSameDay)
    							    {
    							    	//should have condition to determine if ticket was created between or outside the shift...
    							    	// $ticket->agent_first_response_duration = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
    							    	if ( $ticketCreatedAt < $startAgentShift )
    						    		{
    										$ticket->agent_first_response_duration          = $this->getActivityDuration($startAgentShift, $performanceLog->user_replied_at);
    										$ticket->agent_first_response_duration_detailed = $this->getDetailedActivityDuration($startAgentShift, $performanceLog->user_replied_at);
    						    			break;
    						    		}
    						    		else
    						    		{

    						    			/*if ($ticket->id == 29849)
    						    			{
    						    				dump($ticket->created_at);
    						    				dump($performanceLog->user_replied_at);
    						    				dd($this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at));
    						    			}*/

    										$ticket->agent_first_response_duration          = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
    										$ticket->agent_first_response_duration_detailed = $this->getDetailedActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
    						    			break;
    						    		}
    							    }
    							    else
    							    {
    									// dump(1);
    									//on first day
    							    	if ( $ticketCreatedAt->format('Y-m-d') == $date->format('Y-m-d') )
    							    	{
    							    		// dump($ticketCreatedAt->format('Y-m-d') .' : '. $date->format('Y-m-d'));
    							    		/* Get the correct agent_first_response_duration on first day
    							    		 * on first loop, check if ticket created before the shift. then Start Shift - End Shift
    							    		 * else Ticket created_at - end shift
    							    		 */
    							    		// dump(2);
    							    		if ( $ticketCreatedAt < $startAgentShift )
    							    		{
    							    			$agentFirstResponseDuration += $startAgentShift->diffInSeconds($endAgentShift);
    							    			// dump(3);
    							    		}
    							    		// else
    							    		elseif ( $ticketCreatedAt > $startAgentShift && $ticketCreatedAt < $endAgentShift )
    							    		{
    							    			$agentFirstResponseDuration += $ticketCreatedAt->diffInSeconds($endAgentShift);
    							    			// dump(4);
    							    		}

    							    	}
    							    	else
    							    	{
    							    		//days in between , end date

    							    		//end date
    							    		// dump(5);
    							    		if ( $userRepliedAt->format('Y-m-d') == $date->format('Y-m-d') )
    							    		{
    							    			$agentFirstResponseDuration += $startAgentShift->diffInSeconds($userRepliedAt);
    							    			// dump(6);
    							    		}
    							    		else
    							    		{
    							    			$agentFirstResponseDuration += $startAgentShift->diffInSeconds($endAgentShift);
    							    			// dump(7);
    							    		}

    							    	}

    							    }

    							}

    							if ( !$agentFirstResponseSameDay )
    							{
    								$ticket->agent_first_response_duration          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans());
    								$ticket->agent_first_response_duration_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans(['parts' => 2]));
    							}

    	                    }

    	                }

    	                // ticket duration before its solved/closed
    	                if ( !empty($ticketSolvedAt) )
    	                {
    	                	// dump($ticket->id . ': ' . $ticketSolvedAt->diff($ticket->created_at)->format('%D:%H:%I:%S'));
    						$ticket->solved_duration          = $this->getActivityDuration($ticket->created_at, $ticketSolvedAt);
    						$ticket->detailed_solved_duration = $this->getDetailedActivityDuration($ticket->created_at, $ticketSolvedAt);

    	                    if ( $ticket->created_at->diffInMinutes($ticketSolvedAt) <= 1440 ) //tickets solved within 24hrs else over a day
    	                   	{
    	                   		$count_tickets_solved_in_a_day++;

    	                   		$tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

    	                   		if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_in_a_day'] ) )
    	                   		{
    	                   			$agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_in_a_day'] = 0;
    	                   		}

    	                   		$agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_in_a_day'] += 1;
    	                   	}
    	                   	else
    	                   	{
    	                   		$count_tickets_solved_over_a_day++;

    	                   		$tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

    	                   		if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_over_a_day'] ) )
    	                   		{
    	                   			$agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_over_a_day'] = 0;
    	                   		}

    	                   		$agentsTicketsCountByStatus[$tmpKey]['count_tickets_solved_over_a_day'] += 1;
    	                   	}

    	                }

    	                if ( !empty($ticketClosedAt) )
    	                {

    	                	// dump($ticket->id . ': ' . $ticketClosedAt->diff($ticket->created_at)->format('%D:%H:%I:%S'));
    						$ticket->closed_duration          = $this->getActivityDuration($ticket->created_at, $ticketClosedAt);
    						$ticket->detailed_closed_duration = $this->getDetailedActivityDuration($ticket->created_at, $ticketClosedAt);

    	                   	if ( $ticket->created_at->diffInMinutes($ticketClosedAt) <= 1440 ) //tickets closed within 24hrs else over a day
    	                   	{
    	                   		$count_tickets_closed_in_a_day++;

    	                   		$tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

    	                   		if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_in_a_day'] ) )
    	                   		{
    	                   			$agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_in_a_day'] = 0;
    	                   		}

    	                   		$agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_in_a_day'] += 1;
    	                   	}
    	                   	else
    	                   	{
    	                   		$count_tickets_closed_over_a_day++;

    	                   		$tmpKey = array_search($ticket->assignedTo->user_id, array_column($agentsTicketsCountByStatus, 'id'));

    	                   		if ( !isset( $agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_over_a_day'] ) )
    	                   		{
    	                   			$agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_over_a_day'] = 0;
    	                   		}

    	                   		$agentsTicketsCountByStatus[$tmpKey]['count_tickets_closed_over_a_day'] += 1;
    	                   	}

    	                }

    	                if ( $tickets->count() - 1 == $_key ) // add extra data to the last ticket record for now..
    		            {
    						$ticket->count_tickets_solved_in_a_day   = $count_tickets_solved_in_a_day;
    						$ticket->count_tickets_solved_over_a_day = $count_tickets_solved_over_a_day;
    						$ticket->count_tickets_closed_in_a_day   = $count_tickets_closed_in_a_day;
    						$ticket->count_tickets_closed_over_a_day = $count_tickets_closed_over_a_day;

    						// dd($ticketOpenedToSolvedTime);
    						if( !empty($ticketOpenedToSolvedTime) )
    		                {
    							$seconds                                               = array_sum($ticketOpenedToSolvedTime) / count($ticketOpenedToSolvedTime);
    							$ticket->average_time_ticket_opened_to_solved          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
    							$ticket->average_time_ticket_opened_to_solved_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
    		                }

    		                if( !empty($ticketSolvedToClosedTime) )
    		                {
    							$seconds                                              = array_sum($ticketSolvedToClosedTime) / count($ticketSolvedToClosedTime);
    							$ticket->average_time_ticket_solved_to_closed          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
    							$ticket->average_time_ticket_solved_to_closed_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
    		                }

    		                // dd($ticket);

    		            }

    	            }

                    // dump('after ticket->performanceLogs query: ' . (microtime(true) - $time_start) . 'sec' );

                }

	        }

            // dump('@@@@@after tickets loop: ' . (microtime(true) - $time_start) );$time_start = microtime(true); 

	        //ticketOpenedToSolvedTime, ticketSolvedToClosedTime
	        foreach( $agentsTicketsCountByStatus as $tmpKey => $val )
	        {

	        	if ( isset($val['ticketOpenedToSolvedTime']) )
	        	{
					$tmpSeconds                                           = array_sum($val['ticketOpenedToSolvedTime']) / count($val['ticketOpenedToSolvedTime']);
					$agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_solved']          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans());
					$agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_solved_detailed'] = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans(['parts' => 2]));
				}

				if ( isset($val['ticketSolvedToClosedTime']) )
				{
					$tmpSeconds                                           = array_sum($val['ticketSolvedToClosedTime']) / count($val['ticketSolvedToClosedTime']);
					$agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_solved_to_closed']          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans());
					$agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_solved_to_closed_detailed'] = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans(['parts' => 2]));
				}

				if ( isset($val['ticketOpenedToClosedTime']) )
				{
					$tmpSeconds                                           = array_sum($val['ticketOpenedToClosedTime']) / count($val['ticketOpenedToClosedTime']);
					$agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_closed']          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans());
					$agentsTicketsCountByStatus[$tmpKey]['average_time_ticket_opened_to_closed_detailed'] = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($tmpSeconds)->diffForHumans(['parts' => 2]));
				}

	        }

            // dump('after agentsTicketsCountByStatus query: ' . (microtime(true) - $time_start) );$time_start = microtime(true); 

	        // dd('after agentsTicketsCountByStatus query: ' . (microtime(true) - $time_start) . 'sec' );

	        // or maybe not use this, just limit the foreach loop in blade template..
	        // foreach($tickets as $key => $ticket)
	        // {
	        // 	if($key > 20) // limit display of ticket under data tab on view report
	        // 	{
	        // 		$tickets->forget($key);
	        // 	}
	        // }

            // dump('after tickets forget query: ' . (microtime(true) - $time_start) );

	        // dump($tickets);
            // dd($tickets);
	        // dd($tickets->paginate(20));

	        $data = [
	        	'tickets' => $tickets,
	        	'agentsTicketsCountByStatus' => $agentsTicketsCountByStatus
	        ];

	        return $data;
	        // return $tickets;

    	}
    	else
    	{
    		return false;
    	}

    }

    // public function getAgentTicketsData($userId)
    // {

    // 	if ( !empty($userId) )
    // 	{

	   //      $_emailSupportAddresses = $this->emailSupportAddresses();

	   //      $user = User::find($userId);

	   //      $tickets = Ticket::whereHas('assignedTo', function($a) use($userId){
	   //                  $a->where('user_id', $userId) // change to var
	   //                      ->whereNested(function($a) {
	   //                          // $a->where('tickets.status_id', Ticket::STATUS_PENDING)
	   //                          $a->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	   //                              ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	   //                      });
	   //              })
	   //              ->get();


	   //      foreach($tickets as $ticket)
	   //      {

	   //          $ticket->messages;

	   //          $ticket->agent_reply_count = $ticket->messages->whereIn('from', $_emailSupportAddresses)->count();

	   //          $ticketMessagesTimeStamp = [];

	   //          // will be use as a flag if previous message is from customer or not, to avoid getting incorrect timestamps when theres 2 consecutive replies
	   //          $isLastMessageCustomer   = true; // default true, since we need to start on customer message
	   //          $_ctr                    = 0; // tmp counter will be used once for first customer message

	   //          //average response time on chat
	   //          foreach($ticket->messages as $key => $message)
	   //          {

	   //              /*
	   //               *  GET MESSAGES TIMESTAMP for agents response time
	   //               *  PENDING -> NEED TO FIND A WAY to COMPUTE CORRECTLY THE AVG RESPONSE TIME OF CUSTOMERS MESSAGES/REPLIES AFTER COB
	   //              */

	   //              /* if first iteration and message is from agent then skip
	   //               * start only getting response times from the first message of customer to get response time of agent after that
	   //              */

	   //              if ( $key == 0 && in_array($message->from, $_emailSupportAddresses) )
	   //              {
	   //                  continue;
	   //              }
	   //              else
	   //              {
	   //                  $_ctr++;

	   //                  /* insert timestamp in array if true for first customer message
	   //                   * else insert only if current loop message "from" is not the same as the last message "from"
	   //                  */
	   //                  if ( $_ctr == 1 )
	   //                  {
	   //                      $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
	   //                  }
	   //                  else
	   //                  {
	   //                      /* if $isLastMessageCustomer == true then this current loop should be from agent
	   //                       * else should be from customer
	   //                      */
	   //                      if ( $isLastMessageCustomer )
	   //                      {
	   //                          if ( in_array($message->from, $_emailSupportAddresses) )
	   //                          {
	   //                              $isLastMessageCustomer     = false;
	   //                              $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
	   //                          }
	   //                      }
	   //                      else
	   //                      {
	   //                          if ( !in_array($message->from, $_emailSupportAddresses) )
	   //                          {
	   //                              $isLastMessageCustomer     = true;
	   //                              $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d h:i:s');
	   //                          }
	   //                      }

	   //                  }

	   //              }

	   //          }

	   //          /* compute average response time of agents
	   //           * chunk by 2, to compare
	   //          */
	   //          $ticket->agent_average_response_time = '';
	   //          $ticketMessagesTimeStamp             = array_chunk($ticketMessagesTimeStamp, 2);
	   //          // dump($ticketMessagesTimeStamp);

	   //          if ( !empty($ticketMessagesTimeStamp) )
	   //          {
	   //              $noOfChunks           = 0;
	   //              $messagesResponseTime = [];
	   //              foreach($ticketMessagesTimeStamp as $timestamp)
	   //              {

	   //                  if( count($timestamp) > 1 )
	   //                  {
	   //                      $noOfChunks++;
	   //                      /*$timestamp0 = strtotime($timestamp[0]);
	   //                      $timestamp1 = strtotime($timestamp[1]);
	   //                      $messagesResponseTime[] = abs($timestamp1 - $timestamp0);*/

	   //                      $start  = new \Carbon\Carbon($timestamp[0]);
	   //                      $end    = new \Carbon\Carbon($timestamp[1]);

	   //                      $messagesResponseTime[] = $start->diffInSeconds($end);

	   //                  }

	   //              }

	   //              if( !empty($messagesResponseTime) )
	   //              {
	   //                  $seconds                             = array_sum($messagesResponseTime) / count($messagesResponseTime);
	                    
	   //                  $timeAgo                             = \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans();
	   //                  $timeAgo                             = str_replace(' ago', '', $timeAgo);
	   //                  $ticket->agent_average_response_time = $timeAgo;
	   //              }

	   //          }

	            
	   //          /*
	   //           * get ticket statuses durations, agent response time through performance logs table.
	   //          */

	   //          $ticket->solved_duration = $ticket->closed_duration = $ticket->agent_first_response_duration = '';
	   //          if ( $ticket->performanceLogs->count() )
	   //          {

	   //              $ticketSolvedAt = '';
	   //              $ticketClosedAt = '';
	   //              foreach( $ticket->performanceLogs as $performanceLog )
	   //              {

	   //                  if ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_SOLVED)
	   //                  {
	   //                      $ticketSolvedAt = $performanceLog->created_at;
	   //                  }
	   //                  elseif ($performanceLog->property == 'status_id' && $performanceLog->property_value == Ticket::STATUS_CLOSED)
	   //                  {
	   //                      $ticketClosedAt = $performanceLog->created_at;
	   //                  }

	   //                  if ($performanceLog->user_replied_at)
	   //                  {
	   //                      $ticket->agent_first_response_duration = $this->getActivityDuration($ticket->created_at, $performanceLog->user_replied_at);
	   //                  }

	   //              }

	   //              // ticket duration before its solved/closed
	   //              if ( !empty($ticketSolvedAt) )
	   //              {
	   //                  $ticket->solved_duration = $this->getActivityDuration($ticket->created_at, $ticketSolvedAt);
	   //              }

	   //              if ( !empty($ticketClosedAt) )
	   //              {
	   //                  $ticket->closed_duration = $this->getActivityDuration($ticket->created_at, $ticketClosedAt);
	   //              }

	   //          }

	   //      }

	   //      return $tickets;

    // 	}
    // 	else
    // 	{
    // 		return false;
    // 	}

    // }

    public function getUsersTicketsCountByStatus($userIds, $dateRange)
    {
		logger('getUsersTicketsCountByStatus');
		$users    = User::whereIn('id', $userIds)->get();

		$usersData = [];
		foreach($users as $key => $user)
		{

			$ctrPending = $ctrSolved = $ctrClosed = 0;

			// $userTickets = $user->tickets->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]));
			$tickets     = $user->tickets();
			$userTickets = $tickets->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]))->get();

			foreach($userTickets as $userTicket)
			{
				if ( $userTicket->status_id == Ticket::STATUS_PENDING )
					$ctrPending++;

				if ( $userTicket->status_id == Ticket::STATUS_SOLVED )
					$ctrSolved++;

				if ( $userTicket->status_id == Ticket::STATUS_CLOSED )
					$ctrClosed++;
			}

			$usersData[$key]['id']                    = $user->id;
			$usersData[$key]['name']                  = $user->name;
			$usersData[$key]['tickets_pending'] = $ctrPending;
			$usersData[$key]['tickets_solved']  = $ctrSolved;
			$usersData[$key]['tickets_closed']  = $ctrClosed;

		}

		return $usersData;

    }

    public function getUserTicketsCountByStatus($userId, $dateRange)
    {

		$user    = User::find($userId);
		$tickets = $user->tickets;


    	return [
			'pending' => $tickets->where('status_id', Ticket::STATUS_PENDING)->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]))->count(),
			'solved'  => $tickets->where('status_id', Ticket::STATUS_SOLVED)->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]))->count(),
			'closed'  => $tickets->where('status_id', Ticket::STATUS_CLOSED)->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]))->count(),
    	];
    }

    public function getActivityDuration($start, $end)
    {
    	$start  = new \Carbon\Carbon($start);
        $end    = new \Carbon\Carbon($end);

        return str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $start->diffInSeconds($end) )->diffForHumans());
    }

    public function getDetailedActivityDuration($start, $end)
    {
    	$start  = new \Carbon\Carbon($start);
        $end    = new \Carbon\Carbon($end);

        return str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $start->diffInSeconds($end) )->diffForHumans(['parts' => 2]));
    }

    public function emailSupportAddresses()
    {
    	$emails = EmailSupportAddress::withTrashed()->get('email');
        $_emails = [];

        foreach($emails as $email)
        {
            $_emails[] = $email->email;
        }

        array_push($_emails, 'Brandbeast');

        return $_emails;
    }

}