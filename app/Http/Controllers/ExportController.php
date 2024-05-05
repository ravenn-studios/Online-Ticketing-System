<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect,Response,Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Events\CheckExportStatus;
use Illuminate\Support\Facades\Mail;

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
use Carbon\Carbon;
use Storage;

use App\Exports\AgentPerformanceExport;
use App\Exports\AgentsPerformanceExport;
use App\Exports\AgentsMultisheetExport;
use App\Exports\AgentsPerformanceSummaryExport;
use App\Exports\AgentsMultisheetSummaryExport;
use App\Exports\AgentsMultisheetCategorizedExport;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{

    public function export_categorized_report(Request $request)
    {
        // dd($request->all());
        $parentCategoriesId   = json_decode($request->parentCategoriesId);
        // $parentCategoriesData = json_decode($request->data, true);

        $dateRange            = ( isset($request->dateRange) && !empty($request->dateRange) ? explode(' - ', $request->dateRange) : '' );
        $userIds              = explode(',', $request->userIds);
        // $request->initialLoad = filter_var($request->initialLoad, FILTER_VALIDATE_BOOLEAN);

        $dateRange[0] = Carbon::parse(str_replace('-','/',$dateRange[0]))->startOfDay()->format('Y-m-d H:i:s');
        $dateRange[1] = Carbon::parse(str_replace('-','/',$dateRange[1]))->endOfDay()->format('Y-m-d H:i:s');

        $initialCategories = [1,2,3,4,5,6]; //WMS, Pre-Ship, Post-Order

        //list first three categories, this can be also used if theres multiple categ
        $usersCategorizedReportData = $this->get_categorized_initial_report_data($userIds, $dateRange, $initialCategories, 5); // 2 = pending, 3 = solved, 4 = closed, 5 = all
        // dd($usersCategorizedReportData);
        //export here
        // $r = Excel::download(new AgentsMultisheetCategorizedExport($dateRange, $usersCategorizedReportData, $parentCategoriesData), 'Categorized Reports - '.time().'.xls');
        $r = Excel::download(new AgentsMultisheetCategorizedExport($dateRange, $usersCategorizedReportData, $parentCategoriesId), 'Categorized Reports - '.time().'.xls');
        ob_end_clean();

        return $r;

    }

    public function view_categorized_report(Request $request)
    {

        $dateRange            = ( isset($request->dateRange) && !empty($request->dateRange) ? explode(' - ', $request->dateRange) : '' );
        $userIds              = explode(',', $request->userIds);
        // $request->initialLoad = filter_var($request->initialLoad, FILTER_VALIDATE_BOOLEAN);

        $dateRange[0] = Carbon::parse(str_replace('-','/',$dateRange[0]))->startOfDay()->format('Y-m-d H:i:s');
        $dateRange[1] = Carbon::parse(str_replace('-','/',$dateRange[1]))->endOfDay()->format('Y-m-d H:i:s');


        // if ($request->initialLoad)
        // {
            // $initialCategories = [2,3,4]; //WMS, Pre-Ship, Post-Order
            $initialCategories = [1,2,3,4,5,6]; //WMS, Pre-Ship, Post-Order

            //list first three categories, this can be also used if theres multiple categ
            $usersCategorizedReportData = $this->get_categorized_initial_report_data($userIds, $dateRange, $initialCategories, 5); // 2 = pending, 3 = solved, 4 = closed, 5 = all
            /*dump($usersCategorizedReportData);


            foreach($usersCategorizedReportData as $key => $data)
            {
                foreach($data['categories'] as $_key => $_data)
                {
                    dump($_data);

                    foreach($_data as $__key => $__data)
                    {
                        dd($__data);
                    }
                }
            }*/

            return  view('exports.categorized-report-data', compact([
                    'usersCategorizedReportData',
                ]))->render();
        // }
        // else
        // {
        //     //list first three categories, this can be also used if theres multiple categ
        //     $usersCategorizedReportData = $this->get_categorized_report_data($userIds, $dateRange, 18, 5); // 2 = pending, 3 = solved, 4 = closed, 5 = all
        // }

    }

    public function get_categorized_report_data($userIds, $dateRange, $categoryId, $statusId = 5) // 2 = pending, 3 = solved, 4 = closed, 5 = all
    {

        $users    = User::whereIn('id', $userIds)->get(['id', 'name']);
        $category = Category::find($categoryId);

        $usersData = [];
        foreach($users as $key => $user)
        {

            $userId = $user->id;
            $categoryTicketsCount = Ticket::whereHas('assignedTo', function($a) use($userId, $statusId){
                                                $a->where('user_id', $userId) // change to var
                                                ->whereNested(function($b) use($statusId) {

                                                    if ( $statusId == 5 )// all status
                                                    {
                                                        $b->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
                                                          ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
                                                          ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
                                                    }
                                                    else
                                                    {
                                                         $b->where('tickets.status_id', $statusId);
                                                    }

                                                });
                                            })
                                            ->whereHas('categories', function($b) use($categoryId) {
                                                $b->where('category_id', $categoryId); //wms
                                            })
                                            ->whereBetween('created_at', $dateRange)
                                            ->orderBy('updated_at', 'DESC')
                                            ->count();

            $usersData[$key]['id']                     = $user->id;
            $usersData[$key]['name']                   = $user->name;
            $usersData[$key]['category_id']            = $category->id;
            $usersData[$key]['category_name']          = $category->name;
            $usersData[$key]['category_tickets_count'] = $categoryTicketsCount;

            //get parent categories
            $_category = Category::find($categoryId);
            $parentCategoryId = str_split($_category->parent_category_id);

            $ctr = 0;
            while( count($parentCategoryId) != 1 )
            {
                array_pop($parentCategoryId);

                $tmpParentCategoryId = implode('', $parentCategoryId);
                // dump($tmpParentCategoryId);

                $tmpCategory   = Category::where('parent_category_id', $tmpParentCategoryId)->first();
                $tmpCategoryId = $tmpCategory->id;

                //foreach loop
                $categoryTicketsCount = Ticket::whereHas('assignedTo', function($a) use($userId, $statusId){
                                                $a->where('user_id', $userId) // change to var
                                                ->whereNested(function($b) use($statusId) {

                                                    if ( $statusId == 5 )// all status
                                                    {
                                                        $b->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
                                                          ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
                                                          ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
                                                    }
                                                    else
                                                    {
                                                         $b->where('tickets.status_id', $statusId);
                                                    }

                                                });
                                            })
                                            ->whereHas('categories', function($b) use($tmpCategoryId) {
                                                $b->where('category_id', $tmpCategoryId); //wms
                                            })
                                            ->whereBetween('created_at', $dateRange)
                                            ->orderBy('updated_at', 'DESC')
                                            ->count();

                $usersData[$key]['parent_categories'][$ctr]['category_name']          = $tmpCategory->name;
                $usersData[$key]['parent_categories'][$ctr]['category_tickets_count'] = $categoryTicketsCount;

                /*
                 * to get the correct total tickets count of parent category since we updated that we add all parent categories related when you select sub categ.
                */
                if ( count($parentCategoryId) == 1 )
                {
                    $usersData[$key]['total_tickets_count'] = $categoryTicketsCount;
                }


                $ctr++;

            }

            // $tmpUsersData                           = array_column($usersData[$key]['parent_categories'], 'category_tickets_count');
            // $usersData[$key]['total_tickets_count'] = array_sum($tmpUsersData) + $usersData[$key]['category_tickets_count'];

            /*
                WMS => Shipped => In Transit
                2 => 22 => 221
                12 => 17 => 18

            */

        }

        dd($usersData);

        return $usersData;

    }

    /*
     * get all users tickets counts based on the initial categories
     * categories and sub categories are related using parent_category_id
    */
    public function get_categorized_initial_report_data($userIds, $dateRange, $initialCategories, $statusId = 5) // 2 = pending, 3 = solved, 4 = closed, 5 = all
    {

        $users    = User::whereIn('id', $userIds)->get(['id', 'name']);
        $initialParentCategories = Category::whereIn('parent_category_id', $initialCategories)->get(['id','name','parent_category_id']);

        $usersData = [];
        foreach($users as $key => $user)
        {
            $userId = $user->id;
            // users > categories > ticket counts
            foreach($initialParentCategories as $_key => $_category)
            {

                $parentToSubCategories = Category::where('parent_category_id', 'like', $_category->parent_category_id . '%')->get(['id','name']);

                foreach($parentToSubCategories as $__key => $parentToSubCategory)
                {

                    $categoryTicketsCount = Ticket::whereHas('assignedTo', function($a) use($userId, $statusId){
                                                    $a->where('user_id', $userId) // change to var
                                                    ->whereNested(function($b) use($statusId) {

                                                        if ( $statusId == 5 )// all status
                                                        {
                                                            $b->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
                                                              ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
                                                              ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
                                                        }
                                                        else
                                                        {
                                                             $b->where('tickets.status_id', $statusId);
                                                        }

                                                    });
                                                })
                                                ->whereHas('categories', function($b) use($parentToSubCategory) {
                                                    $b->where('category_id', $parentToSubCategory->id); //wms
                                                })
                                                ->whereBetween('created_at', $dateRange)
                                                ->orderBy('updated_at', 'DESC')
                                                ->count();

                    $usersData[$key]['id']                                                  = $user->id;
                    $usersData[$key]['name']                                                = $user->name;
                    $usersData[$key]['categories'][$_key][$__key]['category_id']            = $parentToSubCategory->id;
                    $usersData[$key]['categories'][$_key][$__key]['category_name']          = $parentToSubCategory->name;
                    $usersData[$key]['categories'][$_key][$__key]['category_tickets_count'] = $categoryTicketsCount;

                }

            }


            /*
                WMS => Shipped => In Transit
                2 => 22 => 221
                12 => 17 => 18

            */

        }

        // dd($usersData);

        return $usersData;

    }

    public function get_ticket_count_by_category($userIds, $dateRange, $categoryId, $statusId = 5) // 2 = pending, 3 = solved, 4 = closed, 5 = all
    {

    }

	public function agentTicketsPaginated(Request $request)
	{
		// dd($request->all());


        $responseTimeTarget         = !empty($request->responseTimeTarget) ? $request->responseTimeTarget : 15;
        $dateRange                  = explode(' - ', $request->dateRange);
        $dateRange[0]               = \Carbon\Carbon::parse($dateRange[0])->startOfDay()->format('Y-m-d H:i:s');
        $dateRange[1]               = \Carbon\Carbon::parse($dateRange[1])->endOfDay()->format('Y-m-d H:i:s');
        $userIds                    = explode(',', $request->userIds);

        $agentsTicketsCountByStatus = $this->getUsersTicketsCountByStatus($userIds, $dateRange);

		$tickets = Ticket::with(['messages'])->whereHas('assignedTo', function($a) use($userIds) {
	                    $a->whereIn('user_id', $userIds) // change to var
	                        ->whereNested(function($b) {
	                            $b->where('tickets.status_id', Ticket::STATUS_PENDING) // add for now to test, comment on production
	                              ->orWhere('tickets.status_id', Ticket::STATUS_SOLVED)
	                              ->orWhere('tickets.status_id', Ticket::STATUS_CLOSED);
	                        });
		                })
	        			->whereBetween('created_at', $dateRange)
	        			->orderBy('updated_at', 'DESC')
		                ->paginate(20);


		$_emailSupportAddresses = $this->emailSupportAddresses();

		$count_tickets_solved_in_a_day = $count_tickets_solved_over_a_day = $count_tickets_closed_in_a_day = $count_tickets_closed_over_a_day = 0;
		$ticketOpenedToSolvedTime      = $ticketOpenedToClosedTime = $ticketSolvedToClosedTime      = [];
		// dump($tickets->toArray());
        foreach($tickets as $_key => $ticket)
        {
        	// dd($ticket->assignedTo->user_id);
            $ticket->messages;

            $ticket->agent_reply_count = $ticket->messages->whereIn('from', $_emailSupportAddresses)->count();

            $customerReplyCount        = $ticket->messages->whereIn('from', $ticket->requester)->count();

            $ticketMessagesTimeStamp = [];

            $ticket->agent_average_response_time = ''; //avg response time for agents

            // will be use as a flag if previous message is from customer or not, to avoid getting incorrect timestamps when theres 2 consecutive replies
            $isLastMessageCustomer   = false; // default true, since we need to start on customer message
            $_ctr                    = 0; // tmp counter will be used once for first customer message


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
                        $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
                    }
                    // elseif ( $isLastMessageCustomer && in_array($message->from, $_emailSupportAddresses) )
                    elseif ( $isLastMessageCustomer && ( in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                    {
                        $isLastMessageCustomer     = false;
                        $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
                    }

                }

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

                            // for incoming tickets that are outside work schedule.
                            $tmpStart = $timestamp[0];

                            if ( $tmpStart > \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 15:00:00') )
                            {
                                $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->addDay()->format('Y-m-d 06:00:00');
                            }
                            elseif ( $tmpStart < \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00') )
                            {
                                $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00');
                            }

                            $start  = new \Carbon\Carbon($timestamp[0]);
                            $end    = new \Carbon\Carbon($timestamp[1]);

                            $messagesResponseTime[] = $start->diffInSeconds($end);
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
                                $seconds                                               = array_sum($ticketSolvedToClosedTime) / count($ticketSolvedToClosedTime);
                                $ticket->average_time_ticket_solved_to_closed          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans());
                                $ticket->average_time_ticket_solved_to_closed_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds($seconds)->diffForHumans(['parts' => 2]));
                            }

                            // dd($ticket);

                        }

                    }

            }

        }

        if ( $tickets->count() )
		{
			return view('exports.load-more-data-on-scroll', compact(['tickets', 'responseTimeTarget']))->render();
		}
		else
		{
			return response()->json(['success' => false, 'message' => 'No more tickets found.']);
		}

	}

	public function export_agents_view()
    {
    	if ( Auth::id() == 5 || Auth::id() == 1 ) // Ms H.R
        {
            $users = User::allAgentsAndManagers()->paginate(10);
        }
    	elseif ( Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) )
    	{
			// $users = User::where('id', '!=', Auth::id())->paginate(10);
			// $users = User::where('id', '=', Auth::id())->paginate(10);
			$users = User::paginate(10);
    	}
    	else
    	{
			$users = User::where('id', Auth::id())->paginate(10);
    	}

		return view('exports.index', compact(['users']));

    }

	public function agent_performance($userIds = null, $dateRange = null, $sendEmail = null, Request $request)
    {
    	// dump($request->all());
        // dd($request->data);
    	// dd(json_decode($request->data, true));

    	//export on view
		$viewReportData = [];
    	if ( isset($request->data) )
    	{
			$viewReportData     = json_decode($request->data, true);
            // dd($viewReportData);
			foreach( $viewReportData['rowDataAverages'] as $key => $rowDataAverage)
	        {
	            if (!is_array($rowDataAverage)) // unset null key value which was generated from js array's, this was caused by not starting array index 0
	            {
	                unset($viewReportData['rowDataAverages'][$key]);
	            }
	        }

    	}
        // dd($viewReportData);

		$userIds   = $request->userIds;
		$dateRange = $request->dateRange;
		$sendEmail = ( isset($request->sendEmail) && !empty($request->sendEmail) ? true : false );
		$dateRange = ( isset($request->dateRange) && !empty($request->dateRange) ? explode(' - ', $request->dateRange) : '' );
		$userIds   = explode(',', $userIds);

    	$dateRange[0] = \Carbon\Carbon::parse(str_replace('-','/',$dateRange[0]))->startOfDay()->format('Y-m-d H:i:s');
        $dateRange[1] = \Carbon\Carbon::parse(str_replace('-','/',$dateRange[1]))->endOfDay()->format('Y-m-d H:i:s');

        // return Excel::download(new AgentsMultisheetExport($userIds, $dateRange), 'Agents Performance - '.time().'.xlsx');
        $r = Excel::download(new AgentsMultisheetExport($userIds, $dateRange, $viewReportData), 'Agents Performance - '.time().'.xls');
        ob_end_clean();
        return $r;

        /*$data =  Excel::download(new AgentsMultisheetExport($userIds, $dateRange), 'Agents Performance - '.time().'.xlsx');
        dd($data);*/
        // $file = $this->get_export_file( $data->getFile()->getFilename() );
        $filename = $data->getFile()->getFilename();
        // if ( $sendEmail )
        // {
        // 	$this->sendEmail($filename);
        // }

    }

    public function agents_performance_summary($userIds = null, $dateRange = null, $sendEmail = true, Request $request)
    {
        // dd($request->all());
        $userIds      = explode(',', $request->userIds);
        $dateRange    = ( isset($request->dateRange) && !empty($request->dateRange) ? explode(' - ', $request->dateRange) : '' );
        $dateRange[0] = \Carbon\Carbon::parse($dateRange[0])->startOfDay()->format('Y-m-d H:i:s');
        $dateRange[1] = \Carbon\Carbon::parse($dateRange[1])->endOfDay()->format('Y-m-d H:i:s');

        $usersTicketsSummaryData = [];
        $period                  = \Carbon\CarbonPeriod::create($dateRange[0], $dateRange[1]);

        foreach($userIds as $key => $userId)
        {

            $user                    = User::find($userId);
            $usersTicketsSummaryData[$key]['id']   = $userId;
            $usersTicketsSummaryData[$key]['name'] = $user->name;

            foreach($period as $_key => $date)
            {
                // dump($date->format('Y-m-d'));

                $_dateRange = [0 => $date->startOfDay()->format('Y-m-d H:i:s'), 1 => $date->endOfDay()->format('Y-m-d H:i:s')];

                $usersTicketsSummaryData[$key]['tickets_count'][$date->format('M d')] = $this->getUsersTicketsSummaryCountByStatus($userId, $_dateRange);
            }

        }

        // return Excel::download(new AgentsMultisheetExport($userIds, $dateRange), 'Agents Performance - '.time().'.xlsx');
        $r = Excel::download(new AgentsMultisheetSummaryExport($dateRange, $usersTicketsSummaryData), 'Agents Performance - '.time().'.xls');
        ob_end_clean();

        return $r;

        // $filename = $r->getFile()->getFilename();
        // $this->sendEmail($filename);
        // if ( $sendEmail )
        // {
        //     $this->sendEmail($filename);
        // }

    }

    public function get_export_file($filename)
    {

    	if ( !empty($filename) )
    	{
    		return \Storage::disk('baseStorage')->get('framework/laravel-excel/' . $filename);
    	}

    	return false;

    }

    public function sendEmail($filename)
    {

    	$fileData = $this->get_export_file($filename);
    	
    	$user = Auth::user();
        $emailSupportAddresses = EmailSupportAddress::active()->first();
        $userName              = $emailSupportAddresses->name;
        $userEmail             = '<'.$emailSupportAddresses->email.'>';
        $userNameEmail         = $userName.' '.$userEmail;

        $emailContent     = $request->emailContent;
        $emailContent     = $emailContent.$getUserSignature;

        //attachment
        $subjectCharset = $charset = 'utf-8';


        $boundary   = uniqid(rand(), true);
        
        $_ticket    = Ticket::where('thread_id', $request->thread_id)->first();
        $isNew      = ($_ticket->messages->last()->message_id == 0) ? true : false;
        $_messageId = $_ticket->messages->last()->message_id;
        
        $strRawMessage = "From: $userNameEmail\r\n"; // needs to get from user details
        // $strRawMessage .= "To: $ticket->requester\r\n";
        $strRawMessage .= "To: rodney@frankiesautoelectrics.com.au\r\n";
        $strRawMessage .= "Subject: $ticket->subject\r\n";
        $strRawMessage .= "Message-ID: $_messageId\r\n";
        $strRawMessage .= "In-Reply-To: $userEmail\r\n";
        $strRawMessage .= "MIME-Version: 1.0\r\n";
        $strRawMessage .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";


        //attachments
        $files = $this->getFiles($request->file_ids);

        $fileSize = Storage::size("public/attachments/".$file->name);
        $fileData = base64_encode( Storage::get("public/attachments/".$file->name) );
        $mimeType = Storage::mimeType("public/attachments/".$file->name);

        $strRawMessage .= "\r\n--{$boundary}\r\n";
        $strRawMessage .= 'Content-Type: '. $mimeType .'; name="'. $file->name .'";' . "\r\n";
        $strRawMessage .= 'Content-ID: <' . $userNameEmail . '>' . "\r\n"; 
        $strRawMessage .= 'Content-Description: ' . $file->name . ';' . "\r\n";
        $strRawMessage .= 'Content-Disposition: attachment; filename="' . $file->name . '"; size=' . $fileSize . ';' . "\r\n";
        $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
        $strRawMessage .= chunk_split($fileData, 76, "\n") . "\r\n";
        $strRawMessage .= '--' . $boundary . "\r\n";

        $strRawMessage .= "\r\n--{$boundary}\r\n";
        $strRawMessage .= 'Content-Type: '. $mimeType .'; name="'. $file->name .'";' . "\r\n";
        $strRawMessage .= 'Content-ID: <' . $userNameEmail . '>' . "\r\n"; 
        $strRawMessage .= 'Content-Description: ' . $file->name . ';' . "\r\n";
        $strRawMessage .= 'Content-Disposition: attachment; filename="' . $file->name . '"; size=' . $fileSize . ';' . "\r\n";
        $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
        $strRawMessage .= chunk_split($fileData, 76, "\n") . "\r\n";
        $strRawMessage .= '--' . $boundary . "\r\n";
        //end attachments

    }

    public function exportViewReport(Request $request)
    {

        // dd($request->all());

        //export on view
        $viewReportData = [];
        if ( isset($request->data) )
        {
            $viewReportData     = json_decode($request->data, true);

            foreach( $viewReportData['rowDataAverages'] as $key => $rowDataAverage)
            {
                if (!is_array($rowDataAverage)) // unset null key value which was generated from js array's, this was caused by not starting array index 0
                {
                    unset($viewReportData['rowDataAverages'][$key]);
                }
            }
        }

        $userIds      = (is_array($request->userIds) && count($request->userIds)) ? $request->userIds : explode(',', $request->userIds);
        $dateRange    = $request->dateRange;
        $tmpDateRange = str_replace('/', '-', $request->dateRange);
        // $sendEmail = ( isset($request->sendEmail) && !empty($request->sendEmail) ? true : false );
        $dateRange = ( isset($request->dateRange) && !empty($request->dateRange) ? explode(' - ', $request->dateRange) : '' );
        // $userIds   = explode(',', $userIds);

        $dateRange[0] = \Carbon\Carbon::parse(str_replace('-','/',$dateRange[0]))->startOfDay()->format('Y-m-d H:i:s');
        $dateRange[1] = \Carbon\Carbon::parse(str_replace('-','/',$dateRange[1]))->endOfDay()->format('Y-m-d H:i:s');

        logger('exportViewReport....');
        // return Excel::download(new AgentsMultisheetExport($userIds, $dateRange), 'Agents Performance - '.time().'.xlsx');
        // $r = Excel::download(new AgentsMultisheetExport($userIds, $dateRange, $viewReportData), 'Agents Performance - '.time().'.xls');

        // if (!ob_get_level()) {
        //     ob_start();
        // }
        // else
        // {
        //     while(ob_get_level() > 0) {
        //       ob_end_clean(); // Close the output buffer and erase its content
        //     }
        // }

        $filename = 'Agents Performance - '.time().'.xlsx';
        // $result = Excel::download(new AgentsMultisheetExport($userIds, $dateRange, $viewReportData), 'Agents Performance - '.time().'.xlsx');
        $result = Excel::store(new AgentsMultisheetExport($userIds, $dateRange, $viewReportData), 'app/laravel-excel/'.$filename, 'baseStorage', \Maatwebsite\Excel\Excel::XLSX);
        ob_end_clean();

        // if (ob_get_level()) {
        //     ob_end_clean();
        // }

        if($result)
        {
            $subject     = 'Performance Report - ' . $tmpDateRange;
            $eventResult = event(new CheckExportStatus($subject, $filename, Auth::user()->email));

            // \App\Events\Illuminate\Auth\Events\CheckExportStatus::dispatch();
            // Event::dispatch(new CheckExportStatus());

            return ['success' => true, 'message' => 'The report will be sent to your email shortly.', 'filename' => $filename];
        }
        else
        {
            return ['success' => false];
        }

    }

    public function download_report_by_filename(Request $request)
    {
        $filePath = 'app/laravel-excel/' . $request->filename;
        
        // $fileExists = Storage::download('laravel-excel/Agents Performance - 1658991053.xls');
        $fileExists = Storage::disk('baseStorage')->exists($filePath);
        // dd($fileExists);
        
        if( $fileExists )
        {
            return Storage::disk('baseStorage')->download($filePath);
        }
        // else
        // {
        //     return ['success' => false, 'message']
        // }

    }

    public function view_agents_performance(Request $request)
    {
    	// dump($request->all());
    	$_userIds = $request->userIds;
		$userIds  = explode(',', $_userIds);
		// dd($userIds);
    	$dateRange = '';
    	if ( $request->dateRange != null )
    	{
	    	$dateRange = explode(' - ', $request->dateRange);
	    	// dump($dateRange);
	    	$dateRange[0] = \Carbon\Carbon::parse($dateRange[0])->startOfDay()->format('Y-m-d H:i:s');
	        $dateRange[1] = \Carbon\Carbon::parse($dateRange[1])->endOfDay()->format('Y-m-d H:i:s');
	        // dump($dateRange);
    	}
    	// dd($dateRange);
    	// $user                      = User::find($request->userId);
		$users                      = User::whereIn('id', $userIds)->get();
		$agentsTicketsCountByStatus = $this->getUsersTicketsCountByStatus($userIds, $dateRange);
		// $agentsTicketsData          = $this->getAgentsTicketsData($userIds, $dateRange, $agentsTicketsCountByStatus);
        $ticket = new Ticket;
        $agentsTicketsData          = $ticket->getAgentsTicketsData($userIds, $dateRange, $agentsTicketsCountByStatus);
		$tickets                    = $agentsTicketsData['tickets'];
		$agentsTicketsCountByStatus = $agentsTicketsData['agentsTicketsCountByStatus'];


		if ( $tickets->count() )
		{
			// dd($agentsTicketsCountByStatus);
			return  view('exports.view-multiple', compact([
                    'users',
                    '_userIds',
					'tickets',
					'agentsTicketsCountByStatus',
                ]))->render();

		}
		else
		{
			return response()->json(['success' => false, 'message' => 'No tickets found.']);
		}
    }

    /*public function view_agent_performance(Request $request)
    {

    	$dateRange = '';
    	if ( $request->dateRange != null )
    	{
	    	$dateRange = explode(' - ', $request->dateRange);
	    	// dump($dateRange);
	    	$dateRange[0] = \Carbon\Carbon::parse($dateRange[0])->startOfDay()->format('Y-m-d H:i:s');
	        $dateRange[1] = \Carbon\Carbon::parse($dateRange[1])->endOfDay()->format('Y-m-d H:i:s');
	        // dump($dateRange);
    	}

    	$user                      = User::find($request->userId);
		$tickets                   = $this->getAgentTicketsData($user->id, $dateRange);
		$agentTicketsCountByStatus = $this->getUserTicketsCountByStatus($user->id, $dateRange);

		if ( $tickets->count() )
		{

			return  view('exports.view', compact([
                    'user',
					'tickets',
					'agentTicketsCountByStatus',
                ]))->render();

		}
		else
		{
			return response()->json(['success' => false, 'message' => 'No tickets found.']);
		}
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
			$ticketOpenedToSolvedTime      = $ticketOpenedToClosedTime      = $ticketSolvedToClosedTime      = [];
			// dump($tickets->toArray());
			// $tmpp = '';
			// foreach($tickets as $ticket)
			// {
			// 	$tmpp .= $ticket->id.',';
			// }
			// dd($tmpp);
	        foreach($tickets as $_key => $ticket)
	        {
	        	// dump($ticket->id);
	            // $ticket->messages;

	            $ticket->agent_reply_count = $ticket->messages->whereIn('from', $_emailSupportAddresses)->count();

	            $ticketMessagesTimeStamp = [];

	            // will be use as a flag if previous message is from customer or not, to avoid getting incorrect timestamps when theres 2 consecutive replies
	            $isLastMessageCustomer   = false; // default true, since we need to start on customer message
	            $_ctr                    = 0; // tmp counter will be used once for first customer message

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
                        $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
                    }
                    // elseif ( $isLastMessageCustomer && in_array($message->from, $_emailSupportAddresses) )
                    elseif ( $isLastMessageCustomer && ( in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                    {
                        $isLastMessageCustomer     = false;
                        $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
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

                            // for incoming tickets that are outside work schedule.
                            $tmpStart = $timestamp[0];

                            if ( $tmpStart > \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 15:00:00') )
                            {
                                $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->addDay()->format('Y-m-d 06:00:00');
                            }
                            elseif ( $tmpStart < \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00') )
                            {
                                $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00');
                            }

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
	                	// if ($ticket->id == 29611)
	                	// 	dd($ticket->performanceLogs);

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

							// $ticket->agent_first_response_duration          = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans());
							// $ticket->agent_first_response_duration_detailed = str_replace(' ago', '', \Carbon\Carbon::now()->subSeconds( $agentFirstResponseDuration )->diffForHumans(['parts' => 2]));
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
	                   		$ticket->closed_over_a_day = true;
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
							$seconds                                               = array_sum($ticketSolvedToClosedTime) / count($ticketSolvedToClosedTime);
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

    public function getUserTicketsCountByStatus($userId, $dateRange)
    {

		$user    = User::find($userId);
		$tickets = $user->tickets;


    	return [
			'pending' => $tickets->where('status_id', Ticket::STATUS_PENDING)->whereBetween('created_at', ($dateRange ?: [\Carbon\Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), \Carbon\Carbon::now()->format('Y-m-d H:i:s')]))->count(),
			'solved'  => $tickets->where('status_id', Ticket::STATUS_SOLVED)->whereBetween('created_at', ($dateRange ?: [\Carbon\Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), \Carbon\Carbon::now()->format('Y-m-d H:i:s')]))->count(),
			'closed'  => $tickets->where('status_id', Ticket::STATUS_CLOSED)->whereBetween('created_at', ($dateRange ?: [\Carbon\Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), \Carbon\Carbon::now()->format('Y-m-d H:i:s')]))->count(),
    	];
    }

    public function getAgentsTicketsData($userIds, $dateRange, $agentsTicketsCountByStatus)
    {

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
                // $tickets = Ticket::assigned()->whereHas('assignedTo', function($a) use($userIds){
                //         $a->whereIn('user_id', $userIds); // change to var
                //     })
                // ->whereBetween('created_at', $dateRange)
                // ->orderBy('updated_at', 'DESC')
                // ->get();

                // dump('after daterange query: ' . (microtime(true) - $time_start) );
                // $time_start = microtime(true);

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

                // dump('after daterange query: ' . (microtime(true) - $time_start) ); 
	        }
	        else
	        {
	        	$tickets = Ticket::whereHas('assignedTo', function($a) use($userIds){
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

            // dd('after daterange query: ' . (microtime(true) - $time_start) ); 

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
                            $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
                        }
                        // elseif ( $isLastMessageCustomer && in_array($message->from, $_emailSupportAddresses) )
                        elseif ( $isLastMessageCustomer && ( in_array($message->from, $_emailSupportAddresses) || $message->from == 'Brandbeast' ) )
                        {
                            $isLastMessageCustomer     = false;
                            $ticketMessagesTimeStamp[] = $message->created_at->format('Y-m-d H:i:s');
                        }

    	            }

                    // dump('after ticket->messages query: ' . (microtime(true) - $time_start) . 'sec' );

    	            /* compute average response time of agents
    	             * chunk by 2, to compare
    	            */
                    /*if($ticket->id == 35603)
                    {
                        dump($ticket->messages);
                        dd($ticketMessagesTimeStamp);
                    }*/
                    
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

                                // for incoming tickets that are outside work schedule.
                                $tmpStart = $timestamp[0];

                                if ( $tmpStart > \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 15:00:00') )
                                {
                                    $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->addDay()->format('Y-m-d 06:00:00');
                                }
                                elseif ( $tmpStart < \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00') )
                                {
                                    $timestamp[0] = \Carbon\Carbon::parse($timestamp[0])->format('Y-m-d 06:00:00');
                                }

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
    							$seconds                                               = array_sum($ticketSolvedToClosedTime) / count($ticketSolvedToClosedTime);
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
	        foreach($tickets as $key => $ticket)
	        {
	        	if($key > 20) // limit display of ticket under data tab on view report
	        	{
	        		$tickets->forget($key);
	        	}
	        }

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

    public function getUsersTicketsCountByStatus($userIds, $dateRange)
    {

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

		// $tickets = $user->tickets;

   //  	return [
			// 'pending' => $tickets->where('status_id', Ticket::STATUS_PENDING)->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]))->count(),
			// 'solved'  => $tickets->where('status_id', Ticket::STATUS_SOLVED)->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]))->count(),
			// 'closed'  => $tickets->where('status_id', Ticket::STATUS_CLOSED)->whereBetween('created_at', ($dateRange ?: [Carbon::now()->subDay(1)->format('Y-m-d H:i:s'), Carbon::now()->format('Y-m-d H:i:s')]))->count(),
   //  	];
    }

    public function getUsersTicketsSummaryCountByStatus($userId, $dateRange)
    {

        $user = User::find($userId);

        $usersData = [];

        $ctrPending = $ctrSolved = $ctrClosed = 0;

        $userTickets = $user->tickets->whereBetween('created_at', $dateRange);

        foreach($userTickets as $userTicket)
        {
            if ( $userTicket->status_id == Ticket::STATUS_PENDING )
                $ctrPending++;

            if ( $userTicket->status_id == Ticket::STATUS_SOLVED )
                $ctrSolved++;

            if ( $userTicket->status_id == Ticket::STATUS_CLOSED )
                $ctrClosed++;
        }


        $usersData['tickets_pending'] = $ctrPending;
        $usersData['tickets_solved']  = $ctrSolved;
        $usersData['tickets_closed']  = $ctrClosed;


        return $usersData;

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
