<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect,Response,Exception;
use Illuminate\Support\Facades\Auth;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Filter;
use Google_Service_Gmail_FilterCriteria;
use Google_Service_Gmail_FilterAction;
use App\User;
use App\Role;
use App\GmailApi;
use App\Ticket;
use App\Message;
use App\TicketType;
use App\TicketPriority;
use App\TicketStatus;
use App\TicketOrigin;
use App\CustomVariable;
use App\EmailTemplate;
use App\AssignedTicket;
use App\UserCustomPage;
use App\CustomPageCondition;
use App\Tag;
use App\Category;
use App\Notification;
use App\EmailSupportAddress;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Collection;
use Log;

class TicketController extends Controller
{

    public function index($status = null, Request $request)
    {

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $ticketStatusId   = TicketStatus::getStatusId($status);
        $user             = Auth::user();
        $agents           = User::allAgents()->get();
        $tags             = Tag::all();
        // $categories       = Category::all();

        $categories         = Category::where('id', '<', 91)->get(); // maybe exclude newly added categories by parent category id.. 111
        // $categories         = Category::all(); // maybe exclude newly added categories by parent category id..
        // dd($categories->count());
        $newCategory        = new Collection();

        foreach($categories as $key => $category)
        {
            // dump($category->name);

            if ( !in_array($category->parent_category_id, [64,641,6411,642,65,651,652,653,6531,6532,654]) )
            {
                $newCategory->push($category);
            }


            $categoryIds = [
                222  => [223],
                17   => [7, 71, 711, 712, 72, 721, 722],
                2    => [24, 241, 242, 243, 244],
                22   => [227, 2271, 2272, 228, 2281, 2282, 229],
                224  => [2241, 2242, 2243],
                225  => [223],
                231  => [2311, 2312],
                232  => [2321, 2322, 26, 261, 2611, 2612, 27, 271, 272],
                311  => [312],
                32   => [37, 38, 381, 3811, 382, 3821, 39],
                4    => [44],
                6    => [66, 661, 662],
                631  => [6311],
                6324 => [633,65,651,652,653,6531,6532,654,64,641,6411,642,9,91,92,93,931,94,8,81,82,83,84,841,842,843,85,851,852,853,86]
            ];

            // $newCategory = [];

            foreach ($categoryIds as $parentCategoryId => $childCategoryIds) {
                if ($parentCategoryId == $category->parent_category_id) {
                    if (is_array($childCategoryIds)) {
                        foreach ($childCategoryIds as $childCategoryId) {
                            $tmpCat = Category::where('parent_category_id', $childCategoryId)->first();

                            if ($tmpCat) {
                                $newCategory->push($tmpCat);
                            }
                        }
                    } else {
                        $tmpCat = Category::where('parent_category_id', $childCategoryIds)->first();

                        if ($tmpCat) {
                            $newCategory->push($tmpCat);
                        }
                    }
                }
            }


        }

        $categories = $newCategory;


        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        if ( isset($request->ticket_ids) )
        {

            $tickets = Ticket::whereIn('id', explode(',', $request->ticket_ids))
                            ->orderBy('thread_started_at', 'DESC')
                            ->paginate(20);
        }
        else
        {
            // if ( $ticketStatusId == false || $ticketStatusId == TicketStatus::STATUS_UNASSIGNED )
            if ( $status == 'my-tickets' || $status == null )
            {
                
                $tickets = $user->tickets()
                                ->excludeFacebook()
                                ->excludeEbay()
                                ->with('messages')
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                // ->orderBy('thread_started_at', 'DESC')
                                ->orderBy('updated_at', 'DESC')
                                ->paginate(20);

            }
            else if ( $status == 'awaiting-fulfillment' )
            {
                $tickets = Ticket::excludeFacebook()->excludeEbay()
                            ->with('messages')
                            ->where('type_id', TicketType::TYPE_AWAITING_FULFILLMENT)
                            // ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                            ->orderBy('thread_started_at', 'DESC')
                            ->paginate(20);
            }
            else if ( $status == 'awaiting-shipment' )
            {
                $tickets = Ticket::excludeFacebook()->excludeEbay()
                            ->with('messages')
                            ->where('type_id', TicketType::TYPE_AWAITING_SHIPMENT)
                            // ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                            ->orderBy('thread_started_at', 'DESC')
                            ->paginate(20);
            }
            else if ( $status == 'needs-urgent-attention' )
            {
                // $tickets = Ticket::whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                //                 ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )    
                //                 ->orderBy('thread_started_at', 'DESC')
                //                 ->paginate(20);
                //use to identify if any admin/manager/developer, show all current ticket count depending on view
                // else show only the auth user tickets data
                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com','no-reply@localsearch.com.au'])
                                ->where('created_at', '<', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                                ->whereDoesntHave('messages', function($q){
                                    $q->where('from', EmailSupportAddress::active()->first()->email);
                                })
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                //                 ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
                //                 ->where('created_at', '<', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                //                 ->whereDoesntHave('messages', function($q){
                //                     $q->where('from', EmailSupportAddress::active()->first()->email);
                //                 })
                //                 ->orderBy('thread_started_at', 'DESC')
                //                 ->paginate(20);
                // }
            }
            else if ( $status == 'over-4-hours' )
            {

                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com','no-reply@localsearch.com.au'])
                                ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(1)->toDateTimeString(), \Carbon\Carbon::now()->subHours(4)->toDateTimeString()])
                                ->whereDoesntHave('messages', function($q){
                                    $q->where('from', EmailSupportAddress::active()->first()->email);
                                })
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                //                 ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
                //                 ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(1)->toDateTimeString(), \Carbon\Carbon::now()->subHours(4)->toDateTimeString()])
                //                 ->whereDoesntHave('messages', function($q){
                //                     $q->where('from', EmailSupportAddress::active()->first()->email);
                //                 })
                //                 ->orderBy('thread_started_at', 'DESC')
                //                 ->paginate(20);
                // }

            }
            else if ( $status == 'under-4-hours' )
            {

                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com','no-reply@localsearch.com.au'])
                                ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(4)->toDateTimeString())
                                ->whereDoesntHave('messages', function($q){
                                    $q->where('from', EmailSupportAddress::active()->first()->email);
                                })
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                //                 ->whereNotIn('requester', ['eBay','csfeedback@go.ebay.com'])
                //                 ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(4)->toDateTimeString())
                //                 ->whereDoesntHave('messages', function($q){
                //                     $q->where('from', EmailSupportAddress::active()->first()->email);
                //                 })
                //                 ->orderBy('thread_started_at', 'DESC')
                //                 ->paginate(20);
                // }

            }
            else if ( $status == 'closed' )
            {

                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()->with(['messages','categories','assignedTo','origin','status','type','priority'])->whereIn('status_id', [TicketStatus::STATUS_CLOSED])->whereBetween('updated_at', 

                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                        )->orderBy('updated_at', 'DESC')->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_CLOSED])->orderBy('updated_at', 'DESC')->paginate(20);
                // }
                
            }
            else if ( $status == 'solved' )
            {
                
                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()->with(['messages','categories','assignedTo','origin','status','type','priority'])->whereIn('status_id', [TicketStatus::STATUS_SOLVED])->whereBetween('updated_at', 

                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                        )->orderBy('updated_at', 'DESC')->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_SOLVED])->orderBy('updated_at', 'DESC')->paginate(20);
                // }

            }
            else if ( $status == 'sent' )
            {
                // $tickets = Ticket::orderBy('thread_started_at', 'DESC')->paginate(20);
                $tickets = $user->tickets()->orderBy('updated_at', 'DESC')->paginate(20);

                return view('ticketing.index-sent', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'tags', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']));
            }
            else if ( $status == 'spam' )
            {
                $tickets = Ticket::withTrashed()->excludeFacebook()->excludeEbay()->with(['categories','assignedTo','origin','status','type','priority'])->whereIn('type_id', [TicketType::TYPE_SPAM])->whereNotNull('deleted_at')->orderBy('updated_at', 'DESC')->paginate(20);
            }
            else if ( $status == 'important' )
            {
                $tickets = Ticket::excludeFacebook()
                            ->excludeEbay()
                            ->with(['messages','categories','assignedTo','origin','status','type','priority'])
                            ->where('requester', 'no-reply@localsearch.com.au')
                            ->orderBy('created_at', 'DESC')
                            ->paginate(20);
            }
            else if ( $status == 'new-orders' ) //unassigned new orders
            {
                $tickets = Ticket::excludeFacebook()->excludeEbay()
                        ->with(['messages','categories','assignedTo','origin','status','type','priority'])
                        ->where('subject','like','%[Frankies Auto Electrics & Car Audio]: New order #%')
                        ->whereIn('status_id', [TicketStatus::STATUS_UNASSIGNED])->whereBetween('updated_at', 

                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                        )->orderBy('updated_at', 'DESC')->paginate(20);
            }
            else
            {

                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()->where('status_id', $ticketStatusId)->orderBy('created_at', 'DESC')->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->where('status_id', $ticketStatusId)->orderBy('created_at', 'DESC')->paginate(20);
                // }

                //or get query all ids with status unassigned first..
                // $setDuration = Ticket::UNASSIGNED_DURATION; // duration point where possible to assign tickets if already >= this value (hour)
                // foreach ( $tickets as $ticket )
                // {
                    
                //     if ( $ticket->status_id == TicketStatus::STATUS_UNASSIGNED )
                //     {

                //         $durationUnassigned = Ticket::getDurationUnassigned($ticket->thread_started_at);

                //         if ($durationUnassigned >= $setDuration)
                //         {
                //             // do process, assign ticket to an agent
                //         }

                //         $ticket->durationUnassignedStr = Ticket::get_time_ago( strtotime($ticket->thread_started_at) );

                //     }

                // }

            }

        }
        

        // $notifications = Notification::all()->take(5);

        // foreach($notifications as $notification)
        // {
        //     $model     = $notification->subject_type;
        //     $subjectId = $notification->subject_id;

        //     $user = User::find($notification->sender_id);
        //     $notification->user = $user;
        // }

        // $agents = User::allAgents()->get();

        // return view('ticketing.index');
        return view('ticketing.index', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'tags', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']));
    }

    /*
     * dynamic page view generated by the user.
     * 
    */
    public function customPage($slug = null, Request $request)
    {

        $user             = Auth::user();
        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $agents           = User::allAgents()->get();
        $tags             = Tag::all();
        $categories       = Category::all();

        if ( $slug == 'from-ebay' )
        {

            if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT, Role::AGENT_EBAY]) )
            {
                $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
            }
            // elseif ( $user->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
            // {
            //     $tickets = $user->tickets()->excludeFacebook()->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
            // }

            // ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])

            $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);

        }
        else
        {

            $customPages = UserCustomPage::where('slug', $slug)->where('user_id', Auth::id());
            // dump($customPages->get());
            foreach ( $customPages->get() as $customPage )
            {

                if ( $customPage->pageConditions->count() )
                {

                    // dd( $customPage->pageConditions );
                    $tickets        = '';
                    $pageConditions = $customPage->pageConditions()->orderBy('operator', 'ASC')->get();
                    // dump($pageConditions);
                    foreach ( $pageConditions as $key => $pageCondition )
                    {

                        //setup eloquent on first iteration
                        if ( $key === 0 )
                        {

                            if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                            {
                                $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                            }
                            elseif ( $user->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
                            {
                                // $tickets = $user->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);

                                if ( $pageCondition->filter == 'origin' && $pageCondition->filter_id == 8 ) // 8 = FAE
                                {
                                    $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                                else
                                {
                                    $tickets = $user->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }

                            }

                            //tmp solution for ebay mixing solved tickets to pending,unassigned
                            if ( strpos(strtolower($customPage->name), 'ebay') !== false )
                            {

                                if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                                {
                                    $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where('origin_id', TicketOrigin::ORIGIN_EBAY);
                                }
                                // elseif ( $user->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
                                // {
                                //     $tickets = $user->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where('origin_id', TicketOrigin::ORIGIN_EBAY);
                                // }

                            }

                        }
                        else
                        {

                            if ( strpos(strtolower($customPage->name), 'ebay') === false )
                            {
                                if ( $pageCondition->operator == 'AND' )
                                {
                                    $tickets->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                                else
                                {
                                    $tickets->orWhere($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                            }

                        }
                        
                    }
                    // dd($tickets);
                    $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);
                    // $tickets->withPath('/ajaxFetchData');
                    // $tickets = $tickets->orderBy('updated_at', 'DESC')->paginate(20);
                
                }

            }

        }


        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        return view('ticketing.index', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'tags', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']));
        
    }

    public function pageTag($slug = null, Request $request)
    {

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        // $ticketStatusId   = TicketStatus::getStatusId($status);
        $user             = Auth::user();
        $agents           = User::allAgents()->get();
        $categories       = Category::all();

        // if ( $ticketStatusId == false || $ticketStatusId == TicketStatus::STATUS_UNASSIGNED )
        // if ( $tag == 'my-tickets' )
        // {
            
            // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) )
            if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
            {
                $tickets = Ticket::excludeFacebook()
                                    ->whereHas('tags', function ($q) use ($slug) {
                                        $q->where('slug', $slug);
                                    })
                                    ->orderBy('created_at', 'DESC')
                                    ->paginate(20);
            }
            else
            {
                $tickets = Auth::user()
                                ->tickets()
                                ->excludeFacebook()
                                ->whereHas('tags', function ($q) use ($slug) {
                                    $q->where('slug', $slug);
                                })
                                ->orderBy('created_at', 'DESC')
                                ->paginate(20);
            }


        // }
        // else
        // {

        //     if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
        //     {
        //         $tickets = Ticket::excludeFacebook()->excludeEbay()->where('status_id', $ticketStatusId)->orderBy('created_at', 'DESC')->paginate(20);
        //     }
        //     else
        //     {
        //         $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()->where('status_id', $ticketStatusId)->orderBy('created_at', 'DESC')->paginate(20);
        //     }

        // }
        

        $tags                             = Tag::all();
        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        return view('ticketing.index', compact(
                    'tickets',
                    'ticketPriorities',
                    'ticketTypes',
                    'ticketStatus',
                    'customVariables',
                    'emailTemplates',
                    'user',
                    'agents',
                    'tags',
                    'categories',
                    'emailSupportAddress',
                    'rolesAdminManagerDeveloperExists'
                 ));

    }

    public function pageCategory($slug = null, Request $request)
    {

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        // $ticketStatusId   = TicketStatus::getStatusId($status);
        $user             = Auth::user();
        $agents           = User::allAgents()->get();
        $categories       = Category::all();

        // if ( $ticketStatusId == false || $ticketStatusId == TicketStatus::STATUS_UNASSIGNED )
        // if ( $tag == 'my-tickets' )
        // {
            
            // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) )
            if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
            {
                $tickets = Ticket::excludeFacebook()
                                    ->whereHas('categories', function ($q) use ($slug) {
                                        $q->where('slug', $slug);
                                    })->whereBetween('updated_at', 

                                        [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                    )
                                    ->orderBy('created_at', 'DESC')
                                    ->paginate(20);
            }
            else
            {
                $tickets = Auth::user()
                                ->tickets()
                                ->excludeFacebook()
                                ->whereHas('categories', function ($q) use ($slug) {
                                    $q->where('slug', $slug);
                                })->whereBetween('updated_at', 

                                    [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                )
                                ->orderBy('created_at', 'DESC')
                                ->paginate(20);
            }


        // }
        // else
        // {

        //     if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
        //     {
        //         $tickets = Ticket::excludeFacebook()->excludeEbay()->where('status_id', $ticketStatusId)->orderBy('created_at', 'DESC')->paginate(20);
        //     }
        //     else
        //     {
        //         $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()->where('status_id', $ticketStatusId)->orderBy('created_at', 'DESC')->paginate(20);
        //     }

        // }
        

        $tags = Tag::all();

        return view('ticketing.index', compact(
                    'tickets',
                    'ticketPriorities',
                    'ticketTypes',
                    'ticketStatus',
                    'customVariables',
                    'emailTemplates',
                    'user',
                    'agents',
                    'tags',
                    'categories'
                 ));

    }

    /*
     * view only, sent messages by the user
     * 
    */
    // public function sent(Request $request)
    // {
    //     $tickets = Ticket::all()
    //                     ->orderBy('thread_started_at', 'DESC')
    //                     ->paginate(20);
    //     dd($tickets);
    // }

    public function myTickets()
    {

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();

        $user = Auth::user();

        $assignedTickets = AssignedTicket::where('user_id', $user->id)->get()->toArray();

        $assignedTickets = array_column($assignedTickets, 'ticket_id');
        
        // if ( Auth::user()->rolesByIdExists([Role::MANAGER, Role::CUSTOMER_SERVICE_SUPPORT]) )
        // {
        //     $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
        //                 ->orderBy('thread_started_at', 'DESC')
        //                 ->paginate(20);
        // }
        // else
        // {
            // $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
            $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
        // }

        $tags = Tag::all();
        $categories = Category::all();

        return view('ticketing.index', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'tags', 'categories']));

    }

    public function agentTickets()
    {

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $tags             = Tag::all();
        $categories       = Category::all();
        $user             = Auth::user();



        if ( Auth::id() == 9 )
        {
            $agents = User::withCount(['tickets'])->teamAnne()->get();
        }
        else if ( Auth::id() == 21 )
        {
            $agents = User::withCount(['tickets'])->teamThea()->get();
        }
        else
        {
            $agents = User::withCount(['tickets'])->allAgents()->get();
        }

        $defaultUser                      = $agents->first();
        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        // $tickets = $user->tickets()->paginate(20);
        $tickets = $defaultUser->tickets()->with('messages')->with('assignedTo')->excludeFacebook()->excludeEbay()->orderBy('updated_at', 'DESC')->paginate(20);
        $tickets->withPath('/ajaxGetAgentTickets');
        $myAgentTickets = true;

        return view('ticketing.index', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']));

    }

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    public function getClient()
    {

        $client = new Google_Client();
        $client->setApplicationName('Gmail API PHP Quickstart');
        $client->setScopes([Google_Service_Gmail::GMAIL_READONLY, Google_Service_Gmail::GMAIL_SETTINGS_BASIC]);
        // $client->setScopes(Array(Google_Service_Gmail::GMAIL_READONLY,'https://mail.google.com','https://www.googleapis.com/auth/gmail.settings.basic'));
        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        $tokenPath = 'token.json';
        if (file_exists($tokenPath)) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            } else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Open the following link in your browser:\n%s\n", $authUrl);
                print 'Enter verification code: ';
                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents($tokenPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    public function listMessages($service, $userId)
    {

        $pageToken = NULL;
        $messages = array();
        $opt_param = array();
        do {
          try {
            if ($pageToken) {
              $opt_param['pageToken'] = $pageToken;
            }
            $messagesResponse = $service->users_messages->listUsersMessages($userId, $opt_param);
            if ($messagesResponse->getMessages()) {
              $messages = array_merge($messages, $messagesResponse->getMessages());
              $pageToken = $messagesResponse->getNextPageToken();
            }
          } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
          }
        } while ($pageToken);
      
        $i = 0;
        $data = Array();
        foreach ($messages as $message) {
    
            if ($i <= 20)
            {
                // print 'Message with ID: ' . $message->getId() . '<br/>';
                $message = $service->users_messages->get('me', $message->getId());

                //search and get index where subject header is.
                $subjectIndex = array_search('Subject', array_column($message->payload->headers, 'name'));
                $fromIndex = array_search('From', array_column($message->payload->headers, 'name'));

                $data[$i]['message_id'] = $message->getId();
                $data[$i]['subject'] = $message->payload->headers[$subjectIndex]['value'];
                $data[$i]['from'] = $message->payload->headers[$fromIndex]['value'];
                
                $data[$i]['body'] = base64_decode(str_pad(strtr($message->payload->parts[0]->body->data, '-_', '+/'), strlen($message->payload->parts[0]->body->data) % 4, '=', STR_PAD_RIGHT));

                $i++;
            }
            // usleep(1000000);
        }
      
        return $data;
        
    }

    /**
     * Get all Threads in the user's mailbox.
     *
     * @param  Google_Service_Gmail $service Authorized Gmail API instance.
     * @param  string $userId User's email address. The special value 'me'
     * can be used to indicate the authenticated user.
     * @return array Array of Threads.
     */
    public function listThreads($service, $userId) {

        $threads = array();
        $pageToken = NULL;

        do {
            try {

                $opt_param = array('q' => '!in:chat !in:draft from:rodney@frankiesautoelectrics.com.au');
                // $opt_param = array();
                if ($pageToken) {
                    $opt_param['pageToken'] = $pageToken;
                }

                $threadsResponse = $service->users_threads->listUsersThreads($userId, $opt_param);
                if ($threadsResponse->getThreads()) {
                    $threads = array_merge($threads, $threadsResponse->getThreads());
                    $pageToken = $threadsResponse->getNextPageToken();
                }

            } catch (Exception $e) {
                print 'An error occurred: ' . $e->getMessage();
                $pageToken = NULL;
            }

        } while ($pageToken);
    
        // foreach ($threads as $thread) {
        //     print 'Thread with ID: ' . $thread->getId() . '<br/>';
        // }
    
        return $threads;

    }

    /**
     * Get Thread with given ID.
     *
     * @param  Google_Service_Gmail $service Authorized Gmail API instance.
     * @param  string $userId User's email address. The special value 'me'
     * can be used to indicate the authenticated user.
     * @param  string $threadId ID of Thread to get.
     * @return Google_Service_Gmail_Thread Retrieved Thread.
     */
    public function getThread($service, $userId, $threadId) {

        try {
            $thread = $service->users_threads->get($userId, $threadId);
            $messages = $thread->getMessages();
            $msgCount = count($messages);
            // print 'Number of Messages in the Thread: ' . $msgCount;
            return $thread;
        } catch (Exception $e){
            print 'An error occurred: ' . $e->getMessage();
        }

        // return $messages;
    }

    public function getSubject($headers) {

        $index = array_search('Subject', array_column($headers, 'name'));

        return $headers[$index]['value'];
    }

    public function getFrom($headers) {

        $index = array_search('From', array_column($headers, 'name'));

        return $headers[$index]['value'];
    }

    public function getDate($headers) {

        $index = array_search('Date', array_column($headers, 'name'));

        return $headers[$index]['value'];
    }

    public function setFilter($service) {
        $criteria = new Google_Service_Gmail_FilterCriteria();
        $criteria->setExcludeChats(true);

        $filter = new Google_Service_Gmail_Filter();
        $filter->setCriteria($criteria);

        $service->users_settings_filters->create('me',$filter);
    }

}
