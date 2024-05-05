<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect,Response,Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\GmailApi;
use App\Ticket;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\TicketOrigin;
use App\Message;
use App\CustomVariable;
use App\EmailTemplate;
use App\AssignedTicket;
use App\User;
use App\Role;
use App\RoleUser;
use App\Signature;
use App\EmailSupportAddress;
use App\EbayAPI;                      
use App\UserCustomPage;
use App\CustomPageCondition;
use App\Facebook;
use App\FacebookPage;
use App\Chat;
use App\ChatMessage;
use App\AgentChatLog;
use App\Customer;
use App\Setting;
use App\File;
use App\Tag;
use App\TicketsTags;
use App\TicketCategories;
use App\Category;
use App\AssignTicketRequest;
use App\Notification;
use App\TicketLimit;
use App\Reminder;
use App\ReminderInterval;
use App\ReminderIntervalRecord;
use App\EmailNotification;
use App\UserSchedule;
use App\UserPerformanceLog;
use App\SpamFilter;
use App\Jobs\SendMessage;
use Storage;
use DateTime;
use DateTimeZone;
use DB;
use Validator;
use URL;
use Log;
use App\Mail\SendEmail;
use Illuminate\Support\Facades\Mail;
use \DTS\eBaySDK\Constants;
use \DTS\eBaySDK\Trading\Types;
use \DTS\eBaySDK\Trading\Enums;
use \DTS\eBaySDK\Trading\Services;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Intervention\Image\Facades\Image;

class AjaxController extends Controller
{

    public function refreshCsrfToken(Request $request)
    {
        //for special csrf token error issue on tin, maybe this is due to the account is logged in to multiple device?... further investigation needed.
        $userId = Auth::user()->id;
        // if ($userId == 1 || $userId == 8) // rod, tin
        if ($userId == 8) // tin
        {
            session()->regenerate();
             return response()->json([
                "token"=>csrf_token()],
              200);
        }
    }

    /*
     * Bulk delete of email templates
     */
    public function ajaxBulkDeleteTemplates(Request $request)
    {

        $user = Auth::user();

        if ( $request->ajax() )
        {

            //get email template names to be deleted for alert
            $emailTemplateNames = EmailTemplate::whereIn('id', $request->checkedTemplateIds)->get(['name']);
            $emailTemplateNames = array_column($emailTemplateNames->toArray(), 'name');
            $message = '';
            foreach( $emailTemplateNames as $key => $val ) {
                $message .= '<li>' . $val . '</li>';
            }


            DB::beginTransaction();

            try {

                $deleteEmailTemplates = EmailTemplate::whereIn('id', $request->checkedTemplateIds)->delete();

                if( !$deleteEmailTemplates )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }

                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['success' => false,'message'=>$e->getMessage()]);

            }

            $message = '<ul>' . $message . '</ul>';

            logger('Bulk deleting email templates: ' . implode(',', $emailTemplateNames) . ' By User: ' . $user->id);

            return response()->json(['success' => true, 'message' => 'Selected templates has been deleted.' . $message]);

        }

    }

    public function ajaxGetTicketDetails(Request $request)
    {

        $ticketId = $request->ticketId;

        $ticket = Ticket::with(['messages', 'categories', 'tags'])->where('id', $ticketId)->first();

        $initialMessageId = (isset($ticket->messages->first()->id)) ? $ticket->messages->first()->id : '';

        return view('ticketing.custom_popover', compact(['ticket', 'initialMessageId']));

    }

    public function ajaxGetTicketsStatistics(Request $request)
    {

        /** get all parent categories
         ** add counter to parent categories everytime their child/subcateg has record of tix(use first digit of parent_category_id  for comparison to identify parent child relationship)
         ** use the existing record data for subcateg to display on hover in chart to sidebar, and display the parent category tix data on hover in chart itself
         */

        if ( $request->action == 'current_week' )
        {

            $categories = Category::where('parent_category_id', '<', 10)->get()->toArray();

            //request, default weekly, then last 30 days, this month, last month, custom
            // $time_start = microtime(true);

            $now           = \Carbon\Carbon::now();
            $weekStartDate = $now->startOfWeek()->format('Y-m-d H:i:s');
            $weekEndDate   = $now->endOfWeek()->format('Y-m-d H:i:s');
            $tickets = Ticket::whereHas('categories')
                                ->whereBetween('created_at', [$weekStartDate, $weekEndDate])
                                ->get('id');

            //get all categories involved
            $dateRange                = \Carbon\CarbonPeriod::create($weekStartDate, $weekEndDate);
            $tmpCategories            = $tmpParentCategories = $labels = [];
            $datasets                 = ['datasets' => []];
            $parentCategoriesDatasets = ['datasets' => []]; // to add count, need to identify if the current sub categ is under the parent category
            $tmpParentCategoryKey     = $tmpCategoryKey = 0;
            foreach($tickets as $ticketKey => $ticket )
            {
                //for subcateg
                $category            = $ticket->categories->last()->name;
                if ( !in_array($category, $tmpCategories) ) // to only get the unique categories
                {
                    $tmpCategories[] = $category;
                    $datasets['datasets'][$tmpCategoryKey]['label'] = $category;

                    if ( $tmpCategoryKey == 0 )
                    {
                        $datasets['datasets'][$tmpCategoryKey]['pointRadius'] = 5;
                    }
                    else
                    {
                        $datasets['datasets'][$tmpCategoryKey]['showLine'] = false;
                    }

                    foreach($dateRange as $dateRangeKey => $date)
                    {
                        $datasets['datasets'][$tmpCategoryKey]['data'][$dateRangeKey] = 0;
                    }

                    $tmpCategoryKey++;
                }

                //for parent category
                $tmpParentCategoryId = substr($ticket->categories->last()->parent_category_id, 0,1);
                $tmpParentCategoryId = (int) $tmpParentCategoryId;
                $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
                    return ($categories['parent_category_id'] == $tmpParentCategoryId);
                });
                $parentCategoryName = array_column($categoryData, 'name')[0];

                if ( !in_array($parentCategoryName, $tmpParentCategories) )
                {
                    $tmpParentCategories[] = $parentCategoryName;

                    if ( !empty($categoryData) )
                    {
                        $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['label'] = array_column($categoryData, 'name')[0];

                        if ( $tmpParentCategoryKey == 0 )
                        {
                            $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['pointRadius'] = 5;
                        }
                        else
                        {
                            // $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['showLine'] = false;
                        }

                        foreach($dateRange as $dateRangeKey => $date)
                        {
                            $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['data'][$dateRangeKey] = 0;
                        }

                        $tmpParentCategoryKey++;
                    }

                }

            }


            // $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
            //     return ($categories['parent_category_id'] == $tmpParentCategoryId);
            // });

            foreach($dateRange as $dateRangeKey => $date)
            {

                $startOfDay = $date->startOfDay()->format('Y-m-d H:i:s');
                $endOfDay   = $date->endOfDay()->format('Y-m-d H:i:s');

                $tickets = Ticket::whereHas('categories')
                                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                                ->get();

                $labels['labels'][] = $date->format('M d, y');

                foreach($tickets as $ticketKey => $ticket)
                {

                    //sub category
                    $category = $ticket->categories->last()->name;

                    $categoryData = array_filter($datasets['datasets'], function($datasets) use($category) {
                       return ($datasets['label'] == $category);
                    });

                    if ( !empty($categoryData) )
                    {
                        $categoryKey = array_keys($categoryData)[0];
                        $datasets['datasets'][$categoryKey]['data'][$dateRangeKey] += 1;
                    }

                    //parent category
                    $tmpParentCategoryId = substr($ticket->categories->last()->parent_category_id, 0,1);
                    $tmpParentCategoryId = (int) $tmpParentCategoryId;

                    $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
                        return ($categories['parent_category_id'] == $tmpParentCategoryId);
                    });

                    $parentCategoryName = array_column($categoryData, 'name')[0];
                    $categoryData = array_filter($parentCategoriesDatasets['datasets'], function($categories) use($parentCategoryName) {
                        return ($categories['label'] == $parentCategoryName);
                    });

                    $tmpKey = array_keys($categoryData)[0];
                    $parentCategoriesDatasets['datasets'][$tmpKey]['data'][$dateRangeKey] += 1;

                }

            }

            return response()->json(['data' => $datasets, 'parentCategoriesDatasets' => $parentCategoriesDatasets, 'labels' => $labels['labels']]);

        }
        elseif ( $request->action == 'last_week' )
        {
            
            $categories = Category::where('parent_category_id', '<', 7)->get()->toArray();

            //request, default weekly, then last 30 days, this month, last month, custom
            // $time_start = microtime(true);

            $now = \Carbon\Carbon::now();

            //last week start end
            $weekStartDate = \Carbon\Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d H:i:s');
            $weekEndDate   = \Carbon\Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d H:i:s');

            $tickets = Ticket::whereHas('categories')
                                ->whereBetween('created_at', [$weekStartDate, $weekEndDate])
                                ->get('id');


            //get all categories involved
            $dateRange                = \Carbon\CarbonPeriod::create($weekStartDate, $weekEndDate);
            $tmpCategories            = $tmpParentCategories = $labels = [];
            $datasets                 = ['datasets' => []];
            $parentCategoriesDatasets = ['datasets' => []]; // to add count, need to identify if the current sub categ is under the parent category
            $tmpParentCategoryKey     = $tmpCategoryKey = 0;
            foreach($tickets as $ticketKey => $ticket )
            {
                //for subcateg
                $category            = $ticket->categories->last()->name;
                if ( !in_array($category, $tmpCategories) ) // to only get the unique categories
                {
                    $tmpCategories[] = $category;
                    $datasets['datasets'][$tmpCategoryKey]['label'] = $category;

                    if ( $tmpCategoryKey == 0 )
                    {
                        $datasets['datasets'][$tmpCategoryKey]['pointRadius'] = 5;
                    }
                    else
                    {
                        $datasets['datasets'][$tmpCategoryKey]['showLine'] = false;
                    }

                    foreach($dateRange as $dateRangeKey => $date)
                    {
                        $datasets['datasets'][$tmpCategoryKey]['data'][$dateRangeKey] = 0;
                    }

                    $tmpCategoryKey++;
                }

                //for parent category
                $tmpParentCategoryId = substr($ticket->categories->last()->parent_category_id, 0,1);
                $tmpParentCategoryId = (int) $tmpParentCategoryId;
                $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
                    return ($categories['parent_category_id'] == $tmpParentCategoryId);
                });
                $parentCategoryName = array_column($categoryData, 'name')[0];

                if ( !in_array($parentCategoryName, $tmpParentCategories) )
                {
                    $tmpParentCategories[] = $parentCategoryName;

                    if ( !empty($categoryData) )
                    {
                        $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['label'] = array_column($categoryData, 'name')[0];

                        if ( $tmpParentCategoryKey == 0 )
                        {
                            $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['pointRadius'] = 5;
                        }
                        else
                        {
                            // $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['showLine'] = false;
                        }

                        foreach($dateRange as $dateRangeKey => $date)
                        {
                            $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['data'][$dateRangeKey] = 0;
                        }

                        $tmpParentCategoryKey++;
                    }

                }

            }

            // dd($parentCategoriesDatasets);
            // $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
            //     return ($categories['parent_category_id'] == $tmpParentCategoryId);
            // });

            foreach($dateRange as $dateRangeKey => $date)
            {

                $startOfDay = $date->startOfDay()->format('Y-m-d H:i:s');
                $endOfDay   = $date->endOfDay()->format('Y-m-d H:i:s');

                $tickets = Ticket::whereHas('categories')
                                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                                ->get();

                $labels['labels'][] = $date->format('M d, y');

                foreach($tickets as $ticketKey => $ticket)
                {

                    //sub category
                    $category = $ticket->categories->last()->name;

                    $categoryData = array_filter($datasets['datasets'], function($datasets) use($category) {
                       return ($datasets['label'] == $category);
                    });

                    if ( !empty($categoryData) )
                    {
                        $categoryKey = array_keys($categoryData)[0];
                        $datasets['datasets'][$categoryKey]['data'][$dateRangeKey] += 1;
                    }

                    //parent category
                    $tmpParentCategoryId = substr($ticket->categories->last()->parent_category_id, 0,1);
                    $tmpParentCategoryId = (int) $tmpParentCategoryId;

                    $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
                        return ($categories['parent_category_id'] == $tmpParentCategoryId);
                    });

                    $parentCategoryName = array_column($categoryData, 'name')[0];
                    $categoryData = array_filter($parentCategoriesDatasets['datasets'], function($categories) use($parentCategoryName) {
                        return ($categories['label'] == $parentCategoryName);
                    });

                    $tmpKey = array_keys($categoryData)[0];
                    $parentCategoriesDatasets['datasets'][$tmpKey]['data'][$dateRangeKey] += 1;

                }

            }

            return response()->json(['data' => $datasets, 'parentCategoriesDatasets' => $parentCategoriesDatasets, 'labels' => $labels['labels']]);

        }
        elseif ( $request->action == 'date_range')
        {

            $dateRangePicker = $request->date_range;
            $dateRangePicker = explode(' - ', $dateRangePicker);

            $categories      = Category::where('parent_category_id', '<', 7)->get()->toArray();
            $startDate       = \Carbon\Carbon::parse(current($dateRangePicker))->startOfDay()->format('Y-m-d H:i:s');
            $endDate         = \Carbon\Carbon::parse(end($dateRangePicker))->endOfDay()->format('Y-m-d H:i:s');


            //request, default weekly, then last 30 days, this month, last month, custom
            $tickets = Ticket::whereHas('categories')
                                ->whereBetween('created_at', [$startDate, $endDate])
                                ->get('id');

            //get all categories involved
            $dateRange                = \Carbon\CarbonPeriod::create($startDate, $endDate);
            $tmpCategories            = $tmpParentCategories = $labels = [];
            $datasets                 = ['datasets' => []];
            $parentCategoriesDatasets = ['datasets' => []]; // to add count, need to identify if the current sub categ is under the parent category
            $tmpParentCategoryKey     = $tmpCategoryKey = 0;
            foreach( $tickets as $ticketKey => $ticket )
            {
                //for subcateg
                $category            = $ticket->categories->last()->name;
                if ( !in_array($category, $tmpCategories) ) // to only get the unique categories
                {
                    $tmpCategories[] = $category;
                    $datasets['datasets'][$tmpCategoryKey]['label'] = $category;

                    if ( $tmpCategoryKey == 0 )
                    {
                        $datasets['datasets'][$tmpCategoryKey]['pointRadius'] = 5;
                    }
                    else
                    {
                        $datasets['datasets'][$tmpCategoryKey]['showLine'] = false;
                    }

                    foreach($dateRange as $dateRangeKey => $date)
                    {
                        $datasets['datasets'][$tmpCategoryKey]['data'][$dateRangeKey] = 0;
                    }

                    $tmpCategoryKey++;
                }

                //for parent category
                $tmpParentCategoryId = substr($ticket->categories->last()->parent_category_id, 0,1);
                $tmpParentCategoryId = (int) $tmpParentCategoryId;
                $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
                    return ($categories['parent_category_id'] == $tmpParentCategoryId);
                });
                $parentCategoryName = array_column($categoryData, 'name')[0];

                if ( !in_array($parentCategoryName, $tmpParentCategories) )
                {
                    $tmpParentCategories[] = $parentCategoryName;

                    if ( !empty($categoryData) )
                    {
                        $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['label'] = array_column($categoryData, 'name')[0];

                        if ( $tmpParentCategoryKey == 0 )
                        {
                            $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['pointRadius'] = 5;
                        }
                        else
                        {
                            // $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['showLine'] = false;
                        }

                        foreach($dateRange as $dateRangeKey => $date)
                        {
                            $parentCategoriesDatasets['datasets'][$tmpParentCategoryKey]['data'][$dateRangeKey] = 0;
                        }

                        $tmpParentCategoryKey++;
                    }

                }

            }


            foreach($dateRange as $dateRangeKey => $date)
            {

                $startOfDay = $date->startOfDay()->format('Y-m-d H:i:s');
                $endOfDay   = $date->endOfDay()->format('Y-m-d H:i:s');

                $tickets = Ticket::whereHas('categories')
                                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                                ->get();

                $labels['labels'][] = $date->format('M d, y');

                foreach($tickets as $ticketKey => $ticket)
                {

                    //sub category
                    $category = $ticket->categories->last()->name;

                    $categoryData = array_filter($datasets['datasets'], function($datasets) use($category) {
                       return ($datasets['label'] == $category);
                    });

                    if ( !empty($categoryData) )
                    {
                        $categoryKey = array_keys($categoryData)[0];
                        $datasets['datasets'][$categoryKey]['data'][$dateRangeKey] += 1;
                    }

                    //parent category
                    $tmpParentCategoryId = substr($ticket->categories->last()->parent_category_id, 0,1);
                    $tmpParentCategoryId = (int) $tmpParentCategoryId;

                    $categoryData = array_filter($categories, function($categories) use($tmpParentCategoryId) {
                        return ($categories['parent_category_id'] == $tmpParentCategoryId);
                    });

                    $parentCategoryName = array_column($categoryData, 'name')[0];
                    $categoryData = array_filter($parentCategoriesDatasets['datasets'], function($categories) use($parentCategoryName) {
                        return ($categories['label'] == $parentCategoryName);
                    });

                    $tmpKey = array_keys($categoryData)[0];
                    $parentCategoriesDatasets['datasets'][$tmpKey]['data'][$dateRangeKey] += 1;

                }

            }

            return response()->json(['data' => $datasets, 'parentCategoriesDatasets' => $parentCategoriesDatasets, 'labels' => $labels['labels']]);

        }

    }

    public function ajaxRemoveAsSpam(Request $request)
    {
        // dd($request->all());

        $spamFilter = SpamFilter::find($request->spamFilterId);

        $restored = false;
        if ( $spamFilter->type == SpamFilter::TYPE_EMAIL )
        {

            $ticket          = Ticket::withTrashed()->where('requester', trim($spamFilter->keyword))->where('type_id', TicketType::TYPE_SPAM)->first();
            $ticket->type_id = TicketType::TYPE_QUESTION;
            $ticket->save();
            
            if( $ticket->restore() )
            {

                if ( !$spamFilter->delete() )
                {
                    return response()->json(['success' => false]);
                }

            }
            else
            {
                return response()->json(['success' => false]);
            }

        }
 
        return response()->json(['success' => true]);

    }

    public function ajaxMarkSpamTickets(Request $request)
    {

        // dd($request->all());

        $ticket = Ticket::find($request->ticket_id);

        $ticket->type_id = TicketType::TYPE_SPAM;
        $ticket->save();

        $spamFilterExists = SpamFilter::exists($request->email)->count();
        // dump($spamFilterExists);

        if( $spamFilterExists ) // of spam filter already exists, just marked ticket as spam
        {

            $deleteSpamTicket = $ticket->delete();

            if( !$deleteSpamTicket )
            {
                return response()->json(['success' => true]);
            }

        }
        elseif( !$spamFilterExists && filter_var($request->email, FILTER_VALIDATE_EMAIL) && strpos($request->email, '@frankiesautoelectrics.com.au') === false ) 
        {
            // marked as spam and make sure that it is an email and is not frankies email

            $deleteSpamTicket = $ticket->delete();

            if( !$deleteSpamTicket )
            {
                return response()->json(['success' => false, 'message' => 'Something went wrong, Please try again.']);
            }

            //store email to new table
            $spamEmails = SpamFilter::create([
                'keyword'   => $request->email,
                'type'      => SpamFilter::TYPE_EMAIL,
                'action_by' => Auth::id(),
            ]);

        }
        else
        {
            return response()->json(['success' => false, 'message' => 'Something went wrong, Please try again.']);
        }

        return response()->json(['success' => true]);

    }

    public function ajaxPreviewTicket(Request $request)
    {
        // dump($request->all());

        $ticket = Ticket::find($request->ticketId);

        $ticket->thread_started_at = \Carbon\Carbon::parse($ticket->thread_started_at)->diffForHumans();

        $ticket->messages;

        // dump($ticket);

        // $messages = $ticket->messages;
        $messages = $ticket->messages()->orderBy('id', 'DESC')->get();
        $_files        = [];
        $imgExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        foreach($messages as $message)
        {
            $decodedMessage   = ($ticket->origin_id == TicketOrigin::ORIGIN_EBAY) ? $this->setOutgoingLinksToTarget($message->message) : $this->decodeMessage($message->message);
            $decodedMessage   = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $decodedMessage );
            $decodedMessage   = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $decodedMessage );
            $decodedMessage   = str_replace("progress-bar", "", $decodedMessage);
            $message->message = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');


            //temporary fix for plain text, for some reason on some messages the gmail only returns text/plain and not text/html
            //if message has no html, automatically add <p> on every white/breaklines
            if ( $message->message == strip_tags($message->message)  )
            {
                $message->message = preg_replace("/[\r\n]/","<p></br>",$message->message);
            }

            if ( !empty($message->file_ids) )
            {

                $files = \App\File::whereIn('id', json_decode($message->file_ids))->get();

                foreach($files as $file)
                {

                    if ( in_array($file->extension, $imgExtensions) )
                    {
                        $file->link = '';
                        $file->data = base64_encode( Storage::get('public/attachments/'.$file->name) );
                    }
                    else
                    {
                        $file->data = '';
                        $file->link = URL::to('/') . Storage::url('public/attachments/' . $file->name);
                    }

                    $_files[$message->message_id][] = $file;

                }

            }

        }

        $ticket->messages = $messages;

        return view('ticketing.preview_ticket_content', compact(['ticket','_files']));

    }

    public function updateUserSchedule(Request $request)
    {
        // dump($request->all());
        //function for fetching users to be used for auto assignment
        // dd( UserSchedule::whereIn('user_id', $faeAgentsId)->where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get() );
        
        $userSchedule = UserSchedule::find($request->userScheduleId);
        $isWorkDay    = filter_var($request->isWorkDay, FILTER_VALIDATE_BOOLEAN);
        $day          = $request->day;

        // dump($userSchedule);
        // dump($userSchedule->$day);

        if ( $isWorkDay != $userSchedule->$day )
        {
            $userSchedule->$day = $isWorkDay;

            $userSchedule->save();
        }

        $users       = User::faeAgents()->get(['id'])->toArray();
        $faeAgentsId = array_column($users, 'id');

        $user             = Auth::user();
        $usersSchedule    = UserSchedule::all();
        // $usersSchedule    = UserSchedule::whereIn('user_id', $faeAgentsId)->get();
        // $authUserSchedule = $user->userSchedule;

        // only faeAgents
        /*if ( $user->roles->first()->id == Role::AGENT || $user->roles->first()->id == Role::CUSTOMER_SERVICE_SUPPORT )
        {

            if ( !$authUserSchedule ) {

                $authUserSchedule = UserSchedule::create([
                    'user_id' => Auth::id(),
                    'mon'     => true,
                    'tue'     => true,
                    'wed'     => true,
                    'thu'     => true,
                    'fri'     => true,
                    'sat'     => true,
                    'sun'     => true,
                ]);

            }

        }*/

        $carbon        = \Carbon\CarbonImmutable::now()->format('Y-m-d H:i:s');
        $now           = \Carbon\Carbon::now();
        $weekStartDate = $now->startOfWeek()->format('Y-m-d');
        $weekEndDate   = $now->endOfWeek()->format('Y-m-d');
        $period        = \Carbon\CarbonPeriod::create($weekStartDate, $weekEndDate);

        // Iterate over the period
        $arrDateDay = [];
        foreach ($period as $date) {
            array_push($arrDateDay, ['date' => $date->format('d'), 'day' => $date->format('D')]);
        }

        return view('users.users_schedules_data', compact(['arrDateDay','usersSchedule','user']));

    }
    
    public function ajaxCheckTicketsCounts(Request $request)
    {

        /*$uri            = $request->uri;
        $slug = substr(strrchr($uri, "/"), 1);*/

        $ticket = new Ticket;

        $ticketCounts = Array(
            'awaitingFulfillmentCtr'  => $ticket->count_awaiting_fulfillment_tickets(),
            'awaitingShipmentCtr'     => $ticket->count_awaiting_shipment_tickets(),
            'needsUrgentAttentionCtr' => $ticket->count_tickets_needs_urgent_attention(),
            'overFourHoursCtr'        => $ticket->count_tickets_over_four_hours(),
            'underFourHoursCtr'       => $ticket->count_tickets_under_four_hours(),
            'unassignedTicketsCtr'    => $ticket->count_tickets_unassigned(),
            'myTicketsCtr'            => $ticket->count_my_tickets(),
            'allAgentsCtr'            => User::allAgents()->count(),
            'recentlySolvedCtr'       => $ticket->count_recently_solved_tickets(),
            'recentlyClosedCtr'       => $ticket->count_recently_closed_tickets(),
            'ebayTicketsCtr'          => $ticket->count_custom_page_tickets('ebay'),
            'fromEbayTicketsCtr'      => $ticket->count_custom_page_tickets('from-ebay'),
        );

        // dd($ticketCounts);

        return response()->json(['success' => true, 'ticketCounts' => $ticketCounts]);

    }

    public function ajaxReadReminder(Request $request)
    {

        $reminder = Reminder::find( $request->reminderId );

        $reminder->read = true;

        if ( !$reminder->save() )
        {
            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.']);
        }

        return response()->json(['success' => true, 'message' => 'Reminder has been updated.']);

    }

    public function ajaxUpdateReminder(Request $request)
    {
        // dd($request->all());

        if ( $request->ajax() ) {
            
            DB::beginTransaction();

            try {


                if( empty($request->reminderName) )
                {
                    throw new Exception("Reminder name is required.");
                }
                if( $request->reminderType == 'scheduled' && empty($request->notifyAt) )
                {
                    throw new Exception("Notify At is required.");
                }


                $reminder               = Reminder::find($request->reminderId);

                /*dump($request->all());
                dump($reminder);
                dd( ($reminder->type == Reminder::TYPE_SYSTEM_GENERATED) ? $request->reminderStatus : Reminder::STATUS_PENDING );*/

                $interval               = $reminder->interval->first();
                $reminderIntervalRecord = ReminderIntervalRecord::where('reminder_id', $reminder->id)->first();
                $tmpIntervalId          = 0;

                if ( $request->reminderType == 'scheduled' )
                {

                    //if the updated is scheduled type and previously its interval, delete records on reminder_interval, and reminder_interval_records
                    if ( !empty($interval) )
                    {
                        $interval->delete();
                        $reminderIntervalRecord->delete();
                    }

                }
                else
                {

                    //if has existing interval records = update else create
                    if ( !empty($interval) )
                    {

                        $interval->day    = ($request->interval == 'daily') ? $request->time : 0;
                        $interval->hour   = ($request->interval == 'hourly') ? $request->time : 0;
                        $interval->minute = ($request->interval == 'minute') ? $request->time : 0;

                        if ( !$interval->save() )
                        {
                            throw new Exception("Something went wrong. Please try again.");
                        }

                        // dd($interval);

                    }
                    else
                    {

                        $reminderInterval = ReminderInterval::create([
                            'day'    => ($request->interval == 'daily') ? $request->time : 0,
                            'hour'   => ($request->interval == 'hourly') ? $request->time : 0,
                            'minute' => ($request->interval == 'minute') ? $request->time : 0,
                        ]);

                        if ( !$reminderIntervalRecord )
                        {
                            $reminderIntervalRecord = ReminderIntervalRecord::create([
                                'reminder_id' => $reminder->id,
                                'user_id'     => Auth::id(),
                                'created_at'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                                'updated_at'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                            ]);
                        }

                    }


                    $tmpIntervalId = (!empty($interval)) ? $interval->id : $reminderInterval->id;

                }

                $reminder->ticket_id            = $request->ticket_id;
                $reminder->title                = $request->reminderName;
                $reminder->description          = $request->reminderDescription;
                $reminder->reminder_interval_id = $tmpIntervalId;
                $reminder->notify_at            = ($request->reminderType == 'scheduled') ? $request->notifyAt : '0000-00-00 00:00:00';
                $reminder->status_id            = ($reminder->type == Reminder::TYPE_SYSTEM_GENERATED) ? $request->reminderStatus : Reminder::STATUS_PENDING;
                $reminder->read                 = false;

                if ( !$reminder->save() )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }


                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['error'=>$e->getMessage()]);

            }

            return response()->json(['success' => true, 'message' => 'Reminder has been updated.']);

        }

    }

    public function ajaxGetReminderDetails(Request $request)
    {

        $user     = Auth::user();
        $reminder = Reminder::find( $request->reminderId );
        $interval = $reminder->interval->first();

        return view('users.update_reminder_input_fields', compact('reminder', 'interval', 'user'))->render();

    }

    public function ajaxDeleteReminder(Request $request)
    {
        //update to delete also if reminder has reminder_interval and reminder_interval_records

        $reminder               = Reminder::find( $request->reminderId );
        $interval               = $reminder->interval->first();
        $reminderIntervalRecord = ReminderIntervalRecord::where('reminder_id', $reminder->id)->first();

        $result = $reminder->delete();

        if ( !$result )
        {
            return response()->json(['error' => true, 'message' => 'Something went wrong. Please try again.']);
        }
        else
        {
            if ( !empty($interval) )
            {
                $interval->delete();
                $reminderIntervalRecord->delete();
            }
        }


        return response()->json(['success' => true, 'message' => 'Reminder has been deleted.']);

    }

    public function ajaxCreateReminder(Request $request)
    {

        // dd($request->all());

        if ( $request->ajax() ) {
            
            DB::beginTransaction();

            try {


                if( empty($request->reminderName) )
                {
                    throw new Exception("Reminder name is required.");
                }
                if( $request->reminderType == 'scheduled' && empty($request->notifyAt) )
                {
                    throw new Exception("Notify At is required.");
                }


                if ( $request->reminderType == 'scheduled' )
                {

                    if ( !isset($request->ticket_id) )
                    {
                        throw new Exception("Ticket Id is required.");
                    }

                    //reminder type = scheduled
                    $reminder = Reminder::create([
                        'ticket_id'            => isset($request->ticket_id) && !is_null($request->ticket_id) ? $request->ticket_id : 0,
                        'reminder_interval_id' => 0,
                        'title'                => $request->reminderName,
                        'description'          => empty($request->reminderDescription) ? '' : $request->reminderDescription,
                        'for_user'             => Auth::id(),
                        'type'                 => Reminder::TYPE_USER_GENERATED,
                        'notify_at'            => $request->notifyAt,
                        'status_id'            => Reminder::STATUS_PENDING,
                        'read'                 => false,
                        're_notify'            => 0
                    ]);

                }
                else
                {
                    // dd($request->all());
                    //reminder type = interval
                    $reminderInterval = ReminderInterval::create([
                        'day'    => ($request->interval == 'daily') ? $request->time : 0,
                        'hour'   => ($request->interval == 'hourly') ? $request->time : 0,
                        'minute' => ($request->interval == 'minute') ? $request->time : 0,
                    ]);

                    $reminder = Reminder::create([
                        'ticket_id'            => isset($request->ticket_id) && !is_null($request->ticket_id) ? $request->ticket_id : 0,
                        'reminder_interval_id' => $reminderInterval->id,
                        'title'                => $request->reminderName,
                        'description'          => empty($request->reminderDescription) ? '' : $request->reminderDescription,
                        'for_user'             => Auth::id(),
                        'type'                 => Reminder::TYPE_USER_GENERATED,
                        'notify_at'            => '0000-00-00 00:00:00',
                        'status_id'            => Reminder::STATUS_PENDING,
                        'read'                 => false,
                        're_notify'            => 0
                    ]);

                    $reminderIntervalRecord = ReminderIntervalRecord::where('reminder_id', $reminder->id)->first();

                    if ( !$reminderIntervalRecord )
                    {
                        ReminderIntervalRecord::create([
                            'reminder_id' => $reminder->id,
                            'user_id'     => Auth::id(),
                            'created_at'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                            'updated_at'  => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                    }

                }


                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['error'=>$e->getMessage()]);

            }

            return response()->json(['success' => true, 'message' => 'Reminder has been created.']);

        }

    }

    public function ajaxCheckReminders(Request $request)
    {

        /*
         * Get all system and user reminders and store in array to return for web notifications if results met.
         * and run a trigger if conditions met
         */
        $notifications = [];
        $reminders = Reminder::systemReminders()->get();
        // $user = Auth::user();
        // dd( $user->rolesByIdExists([\App\Role::DEVELOPER]) );

        if ( $reminders->count() )
        {

            foreach ( $reminders as $reminder )
            {

                //tmp - improve this later from static to dynamic conditions / loop
                if ( $reminder->title == Reminder::TITLE_PENDING_TICKET )
                {
                    $result = $this->remindPendingTickets( $reminder );
                    if ( $result && $result['notify'] )
                    {
                        $notifications[] = $result;

                        // $this->triggerMailPendingTickets( $reminder );
                    }
                }

                if ( $reminder->title == Reminder::TITLE_TICKET_LEFT_UNATTENDED )
                {
                    $result = $this->remindUnattendedTickets( $reminder );
                    if ( $result && $result['notify'] )
                    {
                        $notifications[] = $result;

                        // $this->triggerMailUnattendedTickets( $reminder );
                    }
                }

                if ( $reminder->title == Reminder::TITLE_UNASSIGNED_TICKET )
                {
                    $result = $this->remindUnassignedTickets( $reminder );
                    if ( $result && $result['notify'] )
                    {
                        $notifications[] = $result;

                        // $this->triggerMailUnassignedTickets( $reminder );
                    }
                }

                //loop and, make the call to function specific to column values?

            }

        }

        //user reminders
        $getAuthUserReminders = $this->getAuthUserReminders();
        // dd($getAuthUserReminders);
        if ( !empty($getAuthUserReminders) )
        {
            $notifications = array_merge($notifications, $getAuthUserReminders);
        }

        //user was notified but unread reminders, run web notification twice daily
        $getAuthUserUnreadReminders = $this->getAuthUserUnreadReminders();
        // dump($getAuthUserUnreadReminders);
        if ( !empty($getAuthUserUnreadReminders) )
        {
            $notifications = array_merge($notifications, $getAuthUserUnreadReminders);
        }


        //check pending tickets for sometime to notify the user through email
        // $this->emailPendingTickets();

        // dump($notifications);
        return response()->json([
            'notifications' => $notifications
        ]);

    }

    public function triggerMailPendingTickets( $reminder )
    {

        /*$emailPendingTicketsStart   = \Carbon\Carbon::now()->format('Y-m-d 15:00:00'); // 09 / 08am ph
        $emailPendingTicketsEnd     = \Carbon\Carbon::now()->format('Y-m-d 15:00:15');*/

        $tickets = Auth::user()->tickets()->pendingTicketsToNotify()->get();

        // if ( \Carbon\Carbon::now()->between( $emailPendingTicketsStart , $emailPendingTicketsEnd ) && $tickets->count() )
        if ( $tickets->count() )
        {

            //get all ticket ids
            $pendingTicketIdsToNotify = [];
            foreach( $tickets as $ticket )
            {
                array_push($pendingTicketIdsToNotify, $ticket->id);
            }

            //if there is tickets pending for some time. send via email to notify
            $pendingTicketIdsToNotify = implode(',', $pendingTicketIdsToNotify);
            if ( !empty($pendingTicketIdsToNotify) )
            {
                // $ticketsViewUrl = url('/tickets/my-tickets?ticket_ids='.$pendingTicketIdsToNotify.'&name='.Auth::user()->name);
                $ticketsViewUrl = url('/tickets/my-tickets?ticket_ids='.$pendingTicketIdsToNotify);
                $result = Mail::to('rodney@frankiesautoelectrics.com.au')->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
                // $result = Mail::to(Auth::user()->email)->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
            }

        }

    }

    public function triggerMailUnattendedTickets( $reminder )
    {

        $emailUnattendedTicketsStart   = \Carbon\Carbon::now()->format('Y-m-d 15:00:00'); // 09 / 08am ph
        $emailUnattendedTicketsEnd     = \Carbon\Carbon::now()->format('Y-m-d 15:00:15');

        $usersWithPendingTickets = User::where('id', Auth::id())->whereHas('tickets', function($q){
                                            $q->where('status_id', Ticket::STATUS_PENDING);
                                        })->first();

        if ( \Carbon\Carbon::now()->between( $emailUnattendedTicketsStart , $emailUnattendedTicketsEnd ) )
        {

            //get all ticket ids
            $unattendedTicketsToNotify = [];
            foreach( $usersWithPendingTickets->tickets as $ticket )
            {

                $message = $ticket->messages->where('created_at', '<=', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                                            ->reverse()
                                            ->first();

                if ( !empty($message) && count($unattendedTicketsToNotify) <= 20 && $message->from != \App\EmailSupportAddress::active()->first()->email )
                {
                    // dump($message->from);
                    array_push($unattendedTicketsToNotify, $ticket->id);
                }

            }
            // dd($unattendedTicketsToNotify);
            //if there is tickets pending for some time. send via email to notify
            $unattendedTicketsToNotify = implode(',', $unattendedTicketsToNotify);
            if ( !empty($unattendedTicketsToNotify) )
            {
                $ticketsViewUrl = url('/tickets/my-tickets?ticket_ids='.$unattendedTicketsToNotify.'');
                $result = Mail::to('rodneydcro3@gmail.com')->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
                // $result = Mail::to(Auth::user()->email)->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
            }

        }

    }

    public function triggerMailUnassignedTickets( $reminder )
    {

        $emailUnassignedTicketsStart   = \Carbon\Carbon::now()->format('Y-m-d 15:00:00'); // 09 / 08am ph
        $emailUnassignedTicketsEnd     = \Carbon\Carbon::now()->format('Y-m-d 15:00:15');

        $tickets = Auth::user()->tickets()->unassignedTicketsToNotify()->get();

        if ( \Carbon\Carbon::now()->between( $emailUnassignedTicketsStart , $emailUnassignedTicketsEnd ) && $tickets->count() )
        {

            //get all ticket ids
            $unassignedTicketIdsToNotify = [];
            foreach( $tickets as $ticket )
            {
                array_push($unassignedTicketIdsToNotify, $ticket->id);
            }

            //if there is tickets pending for some time. send via email to notify
            $unassignedTicketIdsToNotify = implode(',', $unassignedTicketIdsToNotify);
            if ( !empty($unassignedTicketIdsToNotify) )
            {
                $ticketsViewUrl = url('/tickets/my-tickets?ticket_ids='.$unassignedTicketIdsToNotify.'');
                $result = Mail::to('rodneydcro3@gmail.com')->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
                // $result = Mail::to(Auth::user()->email)->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
            }

        }

    }

    public function triggerMailSolvedTickets( $reminder )
    {

        $emailPendingTicketsStart   = \Carbon\Carbon::now()->format('Y-m-d 15:00:00'); // 09 / 08am ph
        $emailPendingTicketsEnd     = \Carbon\Carbon::now()->format('Y-m-d 15:00:15');

        $tickets = Auth::user()->tickets()->scopeSolvedTicketsToNotify()->get();

        $daysToCheckSolvedTickets = Array(\Carbon\Carbon::MONDAY, \Carbon\Carbon::WEDNESDAY, \Carbon\Carbon::FRIDAY);

        if ( \Carbon\Carbon::now()->between( $emailPendingTicketsStart , $emailPendingTicketsEnd ) && $tickets->count() && in_array(\Carbon\Carbon::now()->dayOfWeek, $daysToCheckSolvedTickets) )
        {

            //get all ticket ids
            $pendingTicketIdsToNotify = [];
            foreach( $tickets as $ticket )
            {
                array_push($pendingTicketIdsToNotify, $ticket->id);
            }

            //if there is tickets pending for some time. send via email to notify
            $pendingTicketIdsToNotify = implode(',', $pendingTicketIdsToNotify);
            if ( !empty($pendingTicketIdsToNotify) )
            {
                $ticketsViewUrl = url('/tickets/my-tickets?ticket_ids='.$pendingTicketIdsToNotify.'');
                $result = Mail::to('rodneydcro3@gmail.com')->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
                // $result = Mail::to(Auth::user()->email)->send(new SendEmail(Auth::user(), $reminder, $ticketsViewUrl));
            }

        }

    }

    public function getAuthUserReminders()
    {

        $notify = [];

        // for interval reminders maybe remove the conidtions where read = false, since reminders with intervals are to be repeated even when its been read
        $reminders = Reminder::authUserReminders()->get();
        // dd($reminders);
        if ( $reminders->count() )
        {

            foreach ( $reminders as $reminder )
            {

                //scheduled reminder
                if ( !$reminder->reminder_interval_id && !$reminder->read && \Carbon\Carbon::now()->gte( \Carbon\Carbon::parse($reminder->notify_at)->format('Y-m-d H:i:s') ) )
                {

                    /*dump(\Carbon\Carbon::now());
                    dump(\Carbon\Carbon::parse($reminder->notify_at)->format('Y-m-d H:i:s'));
                    dd(\Carbon\Carbon::now()->gte( \Carbon\Carbon::parse($reminder->notify_at)->format('Y-m-d H:i:s') ));*/
                    $notify[] = [
                        'notify'      => true,
                        'title'       => $reminder->title,
                        'description' => $reminder->description,
                        'tickets'     => $reminder->ticket_id,
                        'id'          => $reminder->id,
                    ];

                    $reminder->status_id = Reminder::STATUS_DONE;
                    $reminder->save();

                }
                else if ( $reminder->reminder_interval_id )
                {

                    $interval               = $reminder->interval->first();
                    $reminderIntervalRecord = ReminderIntervalRecord::where('user_id', Auth::id())->where('reminder_id', $reminder->id)->first();

                    $condition = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';

                    if ( \Carbon\Carbon::now()->gte( $condition ) )
                    {
                        $notify[] = [
                            'notify'      => true,
                            'title'       => $reminder->title,
                            'description' => $reminder->description,
                            'tickets'     => $reminder->ticket_id,
                            'id'          => $reminder->id,
                        ];

                        $reminderIntervalRecord->updated_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                        $reminderIntervalRecord->save();
                    }

                }

            }

        }


        return $notify;

    }

    public function getAuthUserUnreadReminders()
    {

        $notify = [];

        $reminders = Reminder::authUserUnreadReminders()->get();

        // dd($reminders);

        if ( $reminders->count() )
        {

            foreach ( $reminders as $reminder )
            {

                //re-notify between these datetime (AM/PM)
                $notifyMorningStart   = \Carbon\Carbon::now()->format('Y-m-d 11:00:00'); // 09 / 08am ph
                $notifyMorningEnd     = \Carbon\Carbon::now()->format('Y-m-d 11:00:15');
                $notifyAfternoonStart = \Carbon\Carbon::now()->format('Y-m-d 16:00:00'); // 01 / 12pm ph
                $notifyAfternoonEnd   = \Carbon\Carbon::now()->format('Y-m-d 16:00:15');

                if ( \Carbon\Carbon::now()->between( $notifyMorningStart , $notifyMorningEnd ) )
                {
                    $notify[] = [
                        'notify'      => true,
                        'title'       => $reminder->title,
                        'description' => $reminder->description,
                        'tickets'     => $reminder->ticket_id,
                        'id'          => $reminder->id,
                    ];
                }

                if ( \Carbon\Carbon::now()->between( $notifyAfternoonStart , $notifyAfternoonEnd ) )
                {
                    $notify[] = [
                        'notify'      => true,
                        'title'       => $reminder->title,
                        'description' => $reminder->description,
                        'tickets'     => $reminder->ticket_id,
                        'id'          => $reminder->id,
                    ];
                }

            }

        }


        return $notify;

    }

    /*public function checkUnreadReminders()
    {

    }*/

    //if there is no agent reply for some time..
    public function remindUnattendedTickets($reminder)
    {

        $notify = [
                    'notify'      => false,
                    'title'       => '',
                    'description' => '',
                    'tickets'     => '',
                ];

        /*$usersWithPendingTickets = User::where('id', Auth::id())->whereHas('tickets', function($q){
                                            $q->where('status_id', Ticket::STATUS_PENDING);
                                        })->first();*/
        $usersWithPendingTickets = User::where('id', Auth::id())->with(['tickets' => function($q){
                                            $q->where('status_id', Ticket::STATUS_PENDING);
                                            $q->with('messages');
                                        }])->first();

        $unattendedTickets      = [];
        $interval               = $reminder->interval->first();
        $reminderIntervalRecord = ReminderIntervalRecord::where('user_id', Auth::id())->where('reminder_id', $reminder->id)->first();
        $emailSupportAddress    = \App\EmailSupportAddress::active()->first()->email;

        /* create user reminder interval record if theres none
        ** each user should have separate reminder interval records
        */
        if ( empty($reminderIntervalRecord) )
        {
            $createReminderInterval = ReminderIntervalRecord::create([
                'reminder_id' => $reminder->id,
                'user_id'     => Auth::id(),
            ]);

            $reminderIntervalRecord = ReminderIntervalRecord::where('user_id', Auth::id())->where('reminder_id', $reminder->id)->first();
            $condition              = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';
        }
        else
        {
            $condition = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';
        }


        if ( isset($usersWithPendingTickets->tickets) )
        {

            foreach($usersWithPendingTickets->tickets as $ticket)
            {

                $message = $ticket->messages->where('created_at', '<=', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                                            ->reverse()
                                            ->first();
                                            // ->where('created_at', '<=', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())->last();
                
                //store ticket ids with recent messages as from customer and created_at is after no. of days
                if ( !empty($message) && count($unattendedTickets) <= 20 && $message->from != $emailSupportAddress )
                {   
                    // dump($message->from);
                    array_push($unattendedTickets, $ticket->id);
                }

            }

        }

        if ( !empty($unattendedTickets) && \Carbon\Carbon::now()->gte( $condition ) )
        {
            $notify['notify']      = true;
            $notify['title']       = $reminder->title;
            $notify['description'] = $reminder->description;
            $notify['tickets']     = implode(',', $unattendedTickets);
            $notify['id']          = $reminder->id;

            $reminderIntervalRecord->updated_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $reminderIntervalRecord->save();

            // $this->triggerMailUnattendedTickets( $reminder, $unattendedTickets);
        }


        return $notify;

    }

    public function remindPendingTickets($reminder)
    {

        $notify = [
                    'notify'      => false,
                    'title'       => '',
                    'description' => '',
                    'tickets'     => '',
                ];

        //get users with tickets pending for atleast 1 day
        $users = User::where('id', Auth::id())->with(['tickets' => function($q){
                    $q->where('status_id', Ticket::STATUS_PENDING);
                    $q->where('created_at', '<=', \Carbon\Carbon::now()->subDays(2)->toDateTimeString());
                    $q->select('ticket_id');
                }])->get();

        if ($users->count())
        {

            $interval               = $reminder->interval->first();
            $reminderIntervalRecord = ReminderIntervalRecord::where('user_id', Auth::id())->where('reminder_id', $reminder->id)->first();

            /* create user reminder interval record if theres none
            ** each user should have separate reminder interval records
            */
            if ( empty($reminderIntervalRecord) )
            {
                $createReminderInterval = ReminderIntervalRecord::create([
                    'reminder_id' => $reminder->id,
                    'user_id'     => Auth::id(),
                ]);

                $reminderIntervalRecord = ReminderIntervalRecord::where('user_id', Auth::id())->where('reminder_id', $reminder->id)->first();
                $condition              = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';
            }
            else
            {
                $condition = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';
            }

            /*
             * check if Carbon::now(); is greater than equal reminder interval record's updated_at+2mins 
             * NOTE: Continue working tomorrow on this condition to process correct web notification intervals..
             *  then after that, add other needed reminders and work on the dynamic settings for the notifications/reminders
             */
            if ( \Carbon\Carbon::now()->gte( $condition ) && count($users[0]->tickets->toArray()) )
            {

                // $this->triggerMailPendingTickets( $reminder );

                $notify['notify']      = true;
                $notify['title']       = $reminder->title;
                $notify['description'] = $reminder->description;
                $notify['id']          = $reminder->id;
                $notify['tickets']     = implode(',', array_column($users[0]->tickets->toArray(), 'ticket_id'));

                // update $reminderIntervalRecord->first()->updated_at
                $reminderIntervalRecord->updated_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
                $reminderIntervalRecord->save();

            }

        }

        return $notify;

    }

    public function remindUnassignedTickets($reminder)
    {

        $notify = [
                    'notify'      => false,
                    'title'       => '',
                    'description' => '',
                    'tickets'     => '',
                ];

        //get users with tickets pending for atleast 1 day
        /*$userWithUnassignedTickets = User::where('id', Auth::id())->whereHas('tickets', function($q){
                                            $q->where('status_id', Ticket::STATUS_UNASSIGNED);
                                            // $q->where('created_at', '<=', \Carbon\Carbon::now()->subHours(3)->toDateTimeString());
                                        })->first();*/
        // $userWithUnassignedTickets = $userWithUnassignedTickets->tickets()->where('status_id', Ticket::STATUS_UNASSIGNED)->where('created_at', '<=', \Carbon\Carbon::now()->subHours(3)->toDateTimeString())->get();

        // $userWithUnassignedTickets = $userWithUnassignedTickets->tickets()->unassignedTicketsToNotify()->get();
        $userWithUnassignedTickets = Auth::user()->tickets()->unassignedTicketsToNotify()->get();

        if ( !$userWithUnassignedTickets->count() )
        {
            return;
        }

        $unassignedTickets      = [];
        $interval               = $reminder->interval->first();
        // $reminderIntervalRecord = ReminderIntervalRecord::where('user_id', Auth::id())->where('reminder_id', $reminder->id)->first();
        $reminderIntervalRecord = ReminderIntervalRecord::where('reminder_id', $reminder->id)->first();

        /* create user reminder interval record if theres none
        ** each user should have separate reminder interval records
        */
        if ( empty($reminderIntervalRecord) )
        {
            $createReminderInterval = ReminderIntervalRecord::create([
                'reminder_id' => $reminder->id,
                'user_id'     => Auth::id(),
            ]);

            $reminderIntervalRecord = ReminderIntervalRecord::where('user_id', Auth::id())->where('reminder_id', $reminder->id)->first();
            $condition              = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';
        }
        else
        {
            $condition = ($reminder) ? $this->getNotificationIntervalCondition($reminderIntervalRecord, $interval) : '';
        }

        if ( isset($userWithUnassignedTickets) )
        {

            foreach($userWithUnassignedTickets as $ticket)
            {
                array_push($unassignedTickets, $ticket->id);
            }

        }

        /*
         * check if Carbon::now(); is greater than equal reminder interval record's updated_at+2mins 
         * NOTE: Continue working tomorrow on this condition to process correct web notification intervals..
         *  then after that, add other needed reminders and work on the dynamic settings for the notifications/reminders
         */
        if ( !empty($unassignedTickets) && \Carbon\Carbon::now()->gte( $condition ) )
        {

            $notify['notify']      = true;
            $notify['title']       = $reminder->title;
            $notify['description'] = $reminder->description;
            $notify['tickets']     = implode(',', $unassignedTickets);
            $notify['id']          = $reminder->id;

            // update $reminderIntervalRecord->first()->updated_at
            $reminderIntervalRecord->updated_at = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
            $reminderIntervalRecord->save();

        }


        return $notify;

    }

    public function getNotificationIntervalCondition($reminderIntervalRecord, $interval)
    {

        $condition = '';

        if ( $interval->day )
        {
            $condition = $reminderIntervalRecord->updated_at->addDays($interval->day);
        }

        if ( $interval->hour )
        {
            $condition = $reminderIntervalRecord->updated_at->addHours($interval->hour);
        }

        if ( $interval->minute )
        {
            $condition = $reminderIntervalRecord->updated_at->addMinutes($interval->minute);
        }


        return $condition;

    }

    public function ajaxRefreshUsersTicketLimitListing(Request $request)
    {
        $users = User::faeAgents()->orderBy('created_at', 'DESC')->paginate(10);

        foreach($users as $_user)
        {
            $_user->ticketLimit;
        }

        return view('users.user_ticket_limit', compact(['users']))->render();

    }

    public function ajaxUpdateUserTicketLimit(Request $request)
    {

        $ticketLimit        = TicketLimit::find( $request->ticketLimitId );
        $ticketLimit->limit = (int) $request->ticketLimit;
        $userName           = $ticketLimit->user->name;
        
        if ( !$ticketLimit->save() )
        {
            return response()->json(['success' => false]);
        }


        return response()->json([
            'success' => true,
            'message' => 'User ' . $userName .'\'s ticket limit has been updated.',
        ]);

        // return view('notifications.notification-tab', compact(['notifications', 'unreadNotficiationsCount', 'showNotificationTab']))->render();

    }

    public function ajaxActionTicketRequest(Request $request)
    {

        /* approval for ticket request.
         * re-assign ticket on approved by the manager.
         * this feature can be found under notifications
         */
        $action        = $request->action;
        $ticketRequest = AssignTicketRequest::find( $request->ticketRequestId );
        $message       = '';

        if ( $action == 'approve')
        {
            $ticketRequest->status = AssignTicketRequest::STATUS_APPROVED;

            $this->assignTicket($ticketRequest->ticket_id, $ticketRequest->user_id);

            $message = 'Ticket Request has been approved.';
        }
        else //decline
        {
            $ticketRequest->status = AssignTicketRequest::STATUS_DECLINED;
            $message = 'Ticket Request has been declined.';
        }

        $ticketRequest->action_by = Auth::id();
        $ticketRequest->save();

        return response()->json(['success' => true, 'message' => $message]);

    }

    public function ajaxGetTicketRequestData(Request $request)
    {

        $ticket                   = Ticket::find( $request->ticketId );
        $ticketRequest            = AssignTicketRequest::find( $request->ticketRequestId );
        $ticketRequest->user      = User::find( $ticketRequest->user_id );
        $ticketRequest->action_by = User::find( $ticketRequest->action_by )->name;
        $messagesDate             = [];

        $notification       = $this->readNotification( $request->notificationId );

        foreach ($ticket->messages as $message)
        {
            //decode message, remove style if exists, remove progress-bar class(which messed the html/text email color) in case the email has one
            $decodedMessage = $this->decodeMessage($message->message);
            $decodedMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $decodedMessage );
            $decodedMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?>/si', ' ', $decodedMessage );
            $decodedMessage = str_replace("progress-bar", "", $decodedMessage);
            $message->message = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');

            //temporary fix for plain text, for some reason on some messages the gmail only returns text/plain and not text/html
            //if message has no html, automatically add <p> on every white/breaklines
            if ( $message->message == strip_tags($message->message)  )
            {
                $message->message = preg_replace("/[\r\n]/","<p></br>",$message->message);
            }

            $messagesDate[$message->message_id] = date('M d H:i', strtotime($message->internal_date));

        }


        return response()->json(['success' => true, 'ticket' => $ticket, 'ticketRequest' => $ticketRequest, 'messagesDate' => $messagesDate]);

    }

    public function readNotification($notificationId)
    {

        $notification       = Notification::find( $notificationId );
        $notification->read = true;

        if ( $notification->save() )
        {
            return true;
        }

        return false;

    }

    public function ajaxRenderNotifications(Request $request)
    {

        /* for view more notifications, if view more notifications is clicked, reused this function
         * and take additional 5 notifications if total notifications > $defaultDisplayNotificationsCount else do nothing.
         */
        $displayNotificationsCount = 5;

        if ( $request->currentNotificationsCount > 0 )
        {
            $displayNotificationsCount += 5;
        }

        $unreadNotficiationsCount = Notification::where('read', false)->count();
        $notifications            = Notification::orderBy('created_at', 'DESC')->get()->take($displayNotificationsCount);

        foreach($notifications as $notification)
        {
            $model     = $notification->subject_type;
            $subjectId = $notification->subject_id;

            $user               = User::find($notification->sender_id);
            $notification->user = $user;
            $notification->ticket_request = $model::find($subjectId);
        }

        $showNotificationTab = ($request->showNotificationTab == 'true' ? 'show' : '');


        return view('notifications.notification-tab', compact(['notifications', 'unreadNotficiationsCount', 'showNotificationTab']))->render();

    }

    public function ajaxRequestTicket(Request $request)
    {

        $userId            = Auth::id();
        $hasPendingRequest = AssignTicketRequest::where('ticket_id', $request->ticket_id)
                            ->where('user_id', $userId)
                            ->where('status', AssignTicketRequest::STATUS_PENDING)->count();

        if ( !$hasPendingRequest )
        {

            $assignTicketRequest            = new AssignTicketRequest;
            $assignTicketRequest->ticket_id = $request->ticket_id;
            $assignTicketRequest->user_id   = $userId;
            $assignTicketRequest->status    = AssignTicketRequest::STATUS_PENDING;

            if ( $assignTicketRequest->save() )
            {

                $managers     = RoleUser::where('role_id', Role::MANAGER)->get('user_id')->toArray();
                $recipient_id = implode(',', array_column($managers, 'user_id') );

                $notification = new Notification;
                $description = 'Request for Ticket#' . $request->ticket_id;
                $notification->createNotification( $assignTicketRequest->id, 'App\AssignTicketRequest', $description, $recipient_id );

            }

        }
        else
        {
            return response()->json(['success' => false, 'message' => 'You already have an existing request!']);
        }


        return response()->json(['success' => true, 'message' => 'Request sent!']);

    }

    public function ajaxGetUserAgents(Request $request)
    {

        $agents = User::where('id', '!=', Auth::id())->whereHas(
                    'roles', function($q){
                        $q->whereIn('id', [Role::AGENT, Role::AGENT_EBAY, Role::CUSTOMER_SERVICE_SUPPORT]);
                    })
                    ->get(['id','name']);
        
        return response()->json(['success' => true, 'agents' => $agents]);

    }

    public function ajaxSaveCategories(Request $request)
    {

        if( $request->ajax() )
        {

            $ticketId           = $request->ticket_id;
            $selectedCategories = $request->selected_categories;
            $error              = 0;

            // dump($selectedCategories);

            // dump($ticketId, $selectedCategories);

            $ticketCategories = TicketCategories::where('ticket_id', '=', $ticketId)->delete();

            if ($selectedCategories)
            {

                foreach ( $selectedCategories as $category ) {

                    $_category         = Category::find($category['category_id']);
                    $parentCategoryId = str_split($_category->parent_category_id);

                    while( count($parentCategoryId) != 1 )
                    {
                        array_pop($parentCategoryId);

                        $tmpParentCategoryId = implode('', $parentCategoryId);
                        // dump($tmpParentCategoryId);

                        $tmpCategory = Category::where('parent_category_id', $tmpParentCategoryId)->first();

                        // //make this in a function later
                        $_ticketCategories = TicketCategories::where('ticket_id', '=', $ticketId)
                                                            ->where('category_id', '=', $tmpCategory->id)
                                                            ->count();

                        if (!$_ticketCategories) {

                            $_ticketCategories              = new TicketCategories;
                            $_ticketCategories->ticket_id   = $ticketId;
                            $_ticketCategories->category_id = $tmpCategory->id;

                            if ( !$_ticketCategories->save() ) {
                                $error++;
                            }
                        }
                    }


                    $ticketCategories = TicketCategories::where('ticket_id', '=', $ticketId)
                                                            ->where('category_id', '=', $category['category_id'])
                                                            ->count();

                    // dump($ticketCategories);
                    if (!$ticketCategories) {

                        $ticketCategories              = new TicketCategories;
                        $ticketCategories->ticket_id   = $ticketId;
                        $ticketCategories->category_id = $category['category_id'];

                        if ( !$ticketCategories->save() ) {
                            $error++;
                        }
                    }

                }

                //insert sub categories under a category
                $ticket = Ticket::find($request->ticket_id);
                $ticket->categories;
                /*if ( $ticket->categories->count() )
                {
                    $c = Category::where('parent_category_id', null)->get()->toArray();

                    foreach($ticket->categories as $category)
                    {
                        // dump($category->toArray());
                        $_category                   = $category;
                        $key                         = array_search($category->parent_category_id, array_column($c, 'id'));
                        $c[$key]['sub_categories'][] = $_category->toArray();

                    }

                    $c = array_map(function ($_categories) {

                        if ( isset($_categories['sub_categories']) )
                        {
                            return $_categories;
                        }
                        else
                        {
                            return false;
                        }


                    }, $c);

                    $c = array_filter($c);

                    $ticket->custom_categories = $c;
                }*/

                if ( $error ) {
                    return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.']);
                }
                else
                {
                    return response()->json(['success' => true, 'message' => 'Categories successfully saved.', 'ticket' => $ticket]);
                }

            }

        }

    }

    public function ajaxSaveTags(Request $request)
    {

        if( $request->ajax() )
        {

            $ticketId     = $request->ticket_id;
            $selectedTags = $request->selected_tags;
            $error        = 0;

            // dump($ticketId, $selectedTags);

            $ticketsTags = TicketsTags::where('ticket_id', '=', $ticketId)->delete();

            if ($selectedTags)
            {

                foreach ( $selectedTags as $tagId ) {

                    $ticketsTags = TicketsTags::where('ticket_id', '=', $ticketId)->where('tag_id', '=', $tagId)->count();

                    if (!$ticketsTags) {

                        $ticketsTags             = new TicketsTags;
                        $ticketsTags->ticket_id  = $ticketId;
                        $ticketsTags->tag_id     = $tagId;

                        if ( !$ticketsTags->save() ) {
                            $error++;
                        }
                    }

                }

                if ( $error ) {
                    return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.']);
                }
                else
                {
                    return response()->json(['success' => true, 'message' => 'Tags successfully saved.']);
                }

            }

        }

    }

    public function ajaxLinkOrderNumber(Request $request)
    {

        if( $request->ajax() )
        {

            $ticket = Ticket::findOrFail($request->ticket_id);

            $orderNumbers = explode(',', $ticket->order_number);

            if ( !in_array($request->order_number, $orderNumbers) )
            {
                $ticket->order_number .= ($ticket->order_number ? ',' : '') . $request->order_number;

                if ( $ticket->save() )
                {
                    return response()->json(['success' => true, 'message' => 'Order Number has been linked.', 'order_numbers' => $ticket->order_number]);
                }
            }
            else
            {
                return response()->json(['success' => false, 'message' => 'Order Number exists.', 'order_number' => $request->order_number]);
            }

        }

    }

    public function ajaxUnlinkOrderNumber(Request $request)
    {

        if( $request->ajax() )
        {

            $ticket = Ticket::findOrFail($request->ticket_id);

            $orderNumbers = explode(',', $ticket->order_number);

            foreach( $orderNumbers as $key => $orderNumber )
            {

                if ($orderNumber == $request->order_number)
                {
                    unset($orderNumbers[$key]);
                }

            }

            $ticket->order_number = implode(',', $orderNumbers);

            if ($ticket->save())
            {
                return response()->json(['success' => true, 'message' => 'Order Number has been unlinked.', 'order_numbers' => $ticket->order_number]);
            }

        }

    }

    public function ajaxUpdateRequesterEmail(Request $request)
    {
        if( $request->ajax() )
        {
            //requesterEmail
            $requesterEmail = str_replace(' ', '', $request->requesterEmail);
            $ticket = Ticket::find( (int)$request->ticket_id );
            if ( $ticket->requester != $requesterEmail )
            {
                $ticket->requester = $requesterEmail;
                $ticket->save();
            }

            return response()->json(['success' => true, 'message' => 'Email receiver has been updated.', 'email' => $requesterEmail]);
        }
    }

    public function ajaxGetAgentTicketsByView(Request $request)
    {

        if( $request->ajax() )
        {
            $user_id = $request->user_id;
            $slug    = $request->agent_ticket_view;

            $ticketPriorities = TicketPriority::all();
            $ticketTypes      = TicketType::all();
            $ticketStatus     = TicketStatus::all();
            $customVariables  = CustomVariable::all();
            $emailTemplates   = EmailTemplate::all();

            $agents         = User::withCount(['tickets'])->allAgents()->get();
            $user           = User::find( $request->user_id );
            $myAgentTickets = true;

            $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
            $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

            // $tickets = Ticket::excludeFacebook()->where('status_id', TicketStatus::STATUS_CLOSED)->orderBy('updated_at', 'DESC')->paginate(20);

            // $tickets = Ticket::excludeFacebook()->where('status_id', TicketStatus::STATUS_SOLVED)->orderBy('updated_at', 'DESC')->paginate(20);
            if ( $slug == 'pending' )
            {
                $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->where('status_id', TicketStatus::STATUS_PENDING)->orderBy('updated_at', 'DESC')->paginate(20);
            }
            elseif ( $slug == 'solved' )
            {
                $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->where('status_id', TicketStatus::STATUS_SOLVED)->orderBy('updated_at', 'DESC')->paginate(20);
            }
            elseif ( $slug == 'closed' )
            {
                $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->where('status_id', TicketStatus::STATUS_CLOSED)->orderBy('updated_at', 'DESC')->paginate(20);
            }
            else
            {
                $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->orderBy('updated_at', 'DESC')->paginate(20);
            }

            // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
            // {
            // }
            $tickets->withPath('/ajaxGetAgentTickets');

            $tags       = Tag::all();
            // $categories = Category::all();

            $categories         = Category::where('id', '<', 88)->get();
            $newCategory        = new Collection();

            foreach($categories as $key => $category)
            {

                $newCategory->push($category);

                if( $category->parent_category_id == 225 ) // Delayed
                {
                    $tmpCat = Category::where('id', '>=', 88)->where('parent_category_id', 226)->first();

                    $newCategory->push($tmpCat);
                }

            }

            $categories = $newCategory;

            return view('ticketing.ticketing_table_data', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'agents', 'myAgentTickets', 'tags', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']));
            
        }

    }

    public function ajaxGetAgentTickets(Request $request)
    {
        // dd(session()->all());
        if( $request->ajax() )
        {

            $start_time = microtime(true);

            // dd($request->all());
            $ticketPriorities = TicketPriority::all();
            $ticketTypes      = TicketType::all();
            $ticketStatus     = TicketStatus::all();
            $customVariables  = CustomVariable::all();
            $emailTemplates   = EmailTemplate::all();
            

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

            // $agents = User::withCount(['tickets'])->allAgents()->get();
            $user   = User::find( $request->user_id );

            $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
            $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

            // $tickets = $user->tickets()->paginate(20);
            $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->orderBy('created_at', 'DESC')->limit(100)->paginate(20);
            $tickets->withPath('/ajaxGetAgentTickets');
            $myAgentTickets = true;

            $tags       = Tag::all();
            $categories = Category::all();

            // End clock time in seconds
            $end_time = microtime(true);
              
            // Calculate script execution time
            $execution_time = ($end_time - $start_time);
            Log::info("Execution time: " . $execution_time . " seconds");

            return view('ticketing.ticketing_table_data', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'agents', 'myAgentTickets', 'tags', 'categories', 'rolesAdminManagerDeveloperExists', 'user', 'emailSupportAddress']));

        }
    }

    public function ajaxSearchByTags($slug = null, Request $request)
    {

        $tagIds = $request->tags;

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        // $ticketStatusId   = TicketStatus::getStatusId($status);
        $user             = Auth::user();
        $agents           = User::allAgents()->get();
        $myAgentTickets   = false;

        // if ( $ticketStatusId == false || $ticketStatusId == TicketStatus::STATUS_UNASSIGNED )
        // if ( $tag == 'my-tickets' )
        // {
            
            // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) )
            if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
            {
                $tickets = Ticket::excludeFacebook()
                                    ->whereHas('tags', function ($q) use ($tagIds) {
                                        $q->whereIn('id', $tagIds);
                                    })
                                    ->orderBy('created_at', 'DESC')
                                    ->paginate(20);
            }
            else
            {
                $tickets = Auth::user()
                                ->tickets()
                                ->excludeFacebook()
                                ->whereHas('tags', function ($q) use ($tagIds) {
                                        $q->whereIn('id', $tagIds);
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
        

        $tags          = Tag::all();
        $categories    = Category::all();
        $requestedTags = $request->tags;

        // return view('ticketing.index', compact([
        //             'tickets',
        //             'ticketPriorities',
        //             'ticketTypes',
        //             'ticketStatus',
        //             'customVariables',
        //             'emailTemplates',
        //             'user',
        //             'agents',
        //             'myAgentTickets',
        //             'tags'
        //          ]));

        return view('ticketing.ticketing_table_data', compact([
                    'tickets',
                    'ticketPriorities',
                    'ticketTypes',
                    'ticketStatus',
                    'customVariables',
                    'emailTemplates',
                    'user',
                    'agents',
                    'myAgentTickets',
                    'tags',
                    'requestedTags',
                    'categories'
                    ]))->render();

    }

    public function ajaxSearchByCategories($slug = null, Request $request)
    {

        $categoryIds = $request->categories;

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        // $ticketStatusId   = TicketStatus::getStatusId($status);
        $user             = Auth::user();
        $agents           = User::allAgents()->get();
        $myAgentTickets   = false;

        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        // if ( $ticketStatusId == false || $ticketStatusId == TicketStatus::STATUS_UNASSIGNED )
        // if ( $tag == 'my-tickets' )
        // {
            
            // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) )
            // if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
            // {
                $tickets = Ticket::excludeFacebook()
                                    ->with(['origin','status','type','priority'])
                                    ->whereHas('categories', function ($q) use ($categoryIds) {
                                        $q->whereIn('id', $categoryIds);
                                    })
                                    ->orderBy('created_at', 'DESC')
                                    ->paginate(20);
            // }
            // else
            // {
            //     $tickets = Auth::user()
            //                     ->tickets()
            //                     ->excludeFacebook()
            //                     ->with(['origin','status','type','priority'])
            //                     ->whereHas('categories', function ($q) use ($categoryIds) {
            //                             $q->whereIn('id', $categoryIds);
            //                         })
            //                     ->orderBy('created_at', 'DESC')
            //                     ->paginate(20);
            // }


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
        
        
        $tags                = Tag::all();
        $categories          = Category::all();
        $requestedTags       = $request->tags;
        $requestedCategories = $request->categories;

        // return view('ticketing.index', compact([
        //             'tickets',
        //             'ticketPriorities',
        //             'ticketTypes',
        //             'ticketStatus',
        //             'customVariables',
        //             'emailTemplates',
        //             'user',
        //             'agents',
        //             'myAgentTickets',
        //             'tags'
        //          ]));

        return view('ticketing.ticketing_table_data', compact([
                    'tickets',
                    'ticketPriorities',
                    'ticketTypes',
                    'ticketStatus',
                    'customVariables',
                    'emailTemplates',
                    'user',
                    'agents',
                    'myAgentTickets',
                    'tags',
                    'requestedTags',
                    'requestedCategories',
                    'categories',
                    'rolesAdminManagerDeveloperExists',
                    'emailSupportAddress'
                ]))->render();

    }

    public function ajaxSearchTickets(Request $request)
    {

        if( $request->ajax() )
        {

            $general_search = filter_var($request->general_search, FILTER_VALIDATE_BOOLEAN);

            $user    = Auth::user();
            $search  = $request->search;
            $page    = $request->page;
            $tickets = '';
            $myAgentTickets = false;
            
            $ticketPriorities = TicketPriority::all();
            $ticketTypes      = TicketType::all();
            $ticketStatus     = TicketStatus::all();
            $customVariables  = CustomVariable::all();
            $emailTemplates   = EmailTemplate::all();
            $agents           = User::allAgents()->get();

            $tags       = Tag::all();
            $categories = Category::all();

            $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
            $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);


            // dump($request->all());
            if ($general_search == true)
            {
                Log::info('General Search: True, Searching: ' . $search . ', in Page: ' . $page);
                $tickets = Ticket::excludeFacebook()
                                        ->with(['categories','assignedTo','origin','status','type','priority'])
                                        ->where(function($query) use($search){
                                            $query->where('requester','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('subject','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->orWhereHas('messages', function($a) use($search) {
                                            $a->where('from','like','%'.$search.'%');
                                        })
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);

                // dump(99);
                // dd($tickets);
                //search all - disregarding tickets view, will search all records
                /*if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {

                    // $tickets = Ticket::excludeFacebook()->excludeEbay()
                    $tickets = Ticket::excludeFacebook()
                                        ->where(function($query) use($search){
                                            // $query->where('subject','like','%'.$search.'%')
                                            //         ->orWhere('snippet','like','%'.$search.'%')
                                            //         ->orWhere('requester','like','%'.$search.'%');
                                            $query->where('requester','like','%'.$search.'%');
                                        })
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {

                    // $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                    ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->orderBy('thread_started_at', 'DESC')
                                    ->paginate(20);
                }*/

                if ( $page == '/tickets/sent' )
                {
                    return view('ticketing.ticketing_table_data_sent', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'general_search','categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']))->render();
                }

            }
            else
            {
                Log::info('General Search: False, Searching: ' . $search . ', in Page: ' . $page);
                if ( $page == '/tickets/needs-urgent-attention' )
                {

                    //use to identify if any admin/manager/developer, show all current ticket count depending on view
                    // else show only the auth user tickets data
                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                    {

                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                            ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%')
                                                        ->orWhere('order_number','like','%'.$search.'%');
                                            })
                                            ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                            ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                            ->orderBy('thread_started_at', 'DESC')
                                            ->paginate(20);
                                            // dump(333);
                    }
                    else
                    {

                        $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%')
                                                        ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                                    // dump(444);
                    }

                }
                elseif ( $page == '/tickets/solved' )
                {
                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                    {
                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->whereBetween('updated_at', 

                                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                        )
                                        ->paginate(20);
                    }
                    else
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                    }
                }
                elseif ( $page == '/tickets/closed' )
                {
                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                    {
                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->whereBetween('updated_at', 

                                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                        )
                                        ->paginate(20);
                    }
                    else
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                    }
                }
                elseif ( $page == '/tickets/over-4-hours' )
                {
                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                    {
                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                            ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%')
                                                        ->orWhere('order_number','like','%'.$search.'%');
                                            })
                                            ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                            ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                                            ->orderBy('thread_started_at', 'DESC')
                                            ->paginate(20);
                    }
                    else
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                    }
                }
                elseif ( $page == '/tickets/under-4-hours' )
                {

                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                    {
                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                            ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%')
                                                        ->orWhere('order_number','like','%'.$search.'%');
                                            })
                                            ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                            ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                                            ->orderBy('thread_started_at', 'DESC')
                                            ->paginate(20);
                    }
                    else
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                    }
                    
                }
                elseif ( $page == '/tickets/my-tickets' )
                {
                    // $tickets = Auth::user()->tickets()->excludeFacebook()
                    //                 ->where('subject','like','%'.$search.'%')
                    //                 ->orWhere('snippet','like','%'.$search.'%')
                    //                 ->orWhere('requester','like','%'.$search.'%')
                    //                 ->orderBy('created_at', 'DESC')
                    //                 ->paginate(20);
                    if ( !empty($search) )
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%')
                                                ->orWhere('order_number','like','%'.$search.'%');
                                    })
                                    ->orderBy('created_at', 'DESC')->paginate(20);
                    }
                    else
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()->orderBy('created_at', 'DESC')->paginate(20);
                    }
                }
                elseif ( $page == '/my-agent-tickets' )
                {
                    $user    = User::find( $request->user_id );
                    $tickets = $user->tickets()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%')
                                                ->orWhere('order_number','like','%'.$search.'%');
                                    })
                                    ->paginate(20);
                    $myAgentTickets = true;
                }
                elseif ( $page == '/tickets/sent' )
                {
                    if ( !empty($search) )
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%')
                                                ->orWhere('order_number','like','%'.$search.'%');
                                    })
                                    ->orderBy('created_at', 'DESC')->paginate(20);
                    }
                    else
                    {
                        $tickets = Auth::user()->tickets()->excludeFacebook()->orderBy('created_at', 'DESC')->paginate(20);
                    }

                    return view('ticketing.ticketing_table_data_sent', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'general_search', 'categories']))->render();

                }
                elseif ( strpos($page, '/tickets/tag/') !== false )
                {

                    $slug = explode('/', $page);
                    $slug = end($slug);

                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                    {
                        $tickets = Ticket::excludeFacebook()
                                            ->whereHas('tags', function ($q) use ($slug) {
                                                $q->where('slug', $slug);
                                            })
                                            ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%')
                                                        ->orWhere('order_number','like','%'.$search.'%');
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
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(20);
                    }

                }
                elseif ( strpos($page, '/tickets/category/') !== false )
                {

                    $slug = explode('/', $page);
                    $slug = end($slug);

                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                    {
                        $tickets = Ticket::excludeFacebook()
                                            ->whereHas('categories', function ($q) use ($slug) {
                                                $q->where('slug', $slug);
                                            })
                                            ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%')
                                                        ->orWhere('order_number','like','%'.$search.'%');
                                            })
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
                                        })
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%')
                                                    ->orWhere('order_number','like','%'.$search.'%');
                                        })
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(20);
                    }

                }
                else
                {
                    // user custom pages conditions
                    if ( $user->hasCustomPages() )
                    {
                        $uri  = $request->path;
                        $slug = substr($uri, strrpos($uri, '/') + 1);

                        $userCustompage = UserCustomPage::where([
                                                'user_id' => $user->id,
                                                'slug'    => $slug
                                            ])->first();
                                            
                        if ( $userCustompage->count() )
                        {

                            $tickets        = '';
                            $pageConditions = $userCustompage->pageConditions()->orderBy('operator', 'ASC')->get();
                            
                            foreach ( $pageConditions as $key => $pageCondition )
                            {

                                //setup eloquent on first iteration
                                if ( $key === 0 )
                                {
                                    if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                                    {
                                        $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                    }
                                    elseif ( Auth::user()->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
                                    {
                                        $tickets = Auth::user()->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                    }

                                    $tickets = $tickets->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%')
                                                        ->orWhere('order_number','like','%'.$search.'%');
                                }
                                elseif ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::AGENT_EBAY, Role::CUSTOMER_SERVICE_SUPPORT]) )
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

                            // $tickets = $tickets->where('subject','like','%'.$search.'%')
                            //                     ->orWhere('snippet','like','%'.$search.'%')
                            //                     ->orWhere('requester','like','%'.$search.'%');
                            
                        }

                        $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);
                        
                    }
                }

            }

        }

        return view('ticketing.ticketing_table_data', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'general_search', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']))->render();

    }

    public function ajaxSetTicketToRead(Request $request)
    {

        if( $request->ajax() )
        {

            $ticket = Ticket::withTrashed()->find($request->ticket_id);
            // if ( $ticket->read == false && !Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) ) // set ticket to read if not manager,admin, or developer
            // if ( $ticket->read == false && !Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) ) // set ticket to read if not manager,admin, or developer
            if ( !$ticket->read && !Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) ) // set ticket to read if not manager,admin, or developer
            {

                // if ( Auth::user()->rolesByIdExists([Role::MANAGER]) && $ticket->origin_id == TicketOrigin::ORIGIN_EBAY )
                $ticket->read = true;
                $ticket->save();
                
                $userId              = Auth::user()->id;
                $checkAssignedTicket = AssignedTicket::where('ticket_id', $request->ticket_id)->where('user_id', $userId)->first();
                if( $checkAssignedTicket )
                {
                    $this->recordPendingTicketOpened($request->ticket_id, $userId); // functions will only be valid if the one's assigned to the ticket opened it.
                }

            }
            elseif ( $ticket->read == false && $ticket->origin_id == TicketOrigin::ORIGIN_EBAY && !Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER]) ) // set ticket to read if not manager,admin, or developer
            {

                $ticket->read = true;
                $ticket->save();

                $userId              = Auth::user()->id;
                $checkAssignedTicket = AssignedTicket::where('ticket_id', $request->ticket_id)->where('user_id', $userId)->first();
                if( $checkAssignedTicket )
                {
                    $this->recordPendingTicketOpened($request->ticket_id, $userId); // functions will only be valid if the one's assigned to the ticket opened it.
                }
                
            }

        }

    }


    public function recordPendingTicketOpened($ticketId, $userId)
    {

        $userPerformanceLog = UserPerformanceLog::where('description', 'Opened a Ticket')->where('ticket_id', $ticketId)->where('user_id', $userId)->first();
        
        if ( !$userPerformanceLog )
        {

            $userPerformanceLog              = new UserPerformanceLog;
            $userPerformanceLog->name        = 'Ticket';
            $userPerformanceLog->description = 'Opened a Ticket';
            $userPerformanceLog->user_id     = $userId;
            $userPerformanceLog->ticket_id   = $ticketId;

            $userPerformanceLog->save();

        }

    }

    public function ajaxBulkUpdateTickets(Request $request)
    {

        // dd(session()->all());
        // dump($request->all());
        $page = $request->page;
        $user = Auth::user();

        Log::info('Bulk updating tickets to '. $request->action .': ' . implode(',',$request->checkedTicketIds) . ' By User: ' . $user->id);

        if ( $request->action == 'close' )
        {
            foreach( $request->checkedTicketIds as $checkedTicketId )
            {
                $ticket = Ticket::find($checkedTicketId);
                $ticket->status_id = TicketStatus::STATUS_CLOSED;
                $ticket->save();
            }
        }
        else if ( $request->action == 'solve' )
        {
            foreach( $request->checkedTicketIds as $checkedTicketId )
            {
                $ticket = Ticket::find($checkedTicketId);
                $ticket->status_id = TicketStatus::STATUS_SOLVED;
                $ticket->save();
            }
        }
        else if ( $request->action == 'update-ticket-type' )
        {   
            // dd($request->all());
            foreach( $request->checkedTicketIds as $checkedTicketId )
            {
                $ticket = Ticket::find((int)$checkedTicketId);
                $ticket->type_id = (int)$request->ticket_type_value;
                $ticket->save();

                // Ticket::whereIn('id', [$request->checkedTicketIds])->update(['type_id' => (int)$request->ticket_type_value]);
            }
        }

        // $tickets = $user->tickets()
        //             ->orderBy('thread_started_at', 'DESC')
        //             ->paginate(20);
        // dump($page);
        $tickets = $this->customFetchTickets($page);
        // dd($page);
        
        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $agents           = User::allAgents()->get();

        $tags       = Tag::all();
        $categories = Category::all();

        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        return view('ticketing.ticketing_table_data', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'tags', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']))->render();

    }

    public function ajaxSetDistributeTickets(Request $request)
    {

        if ( $request->boolDistributeTickets == "true" )
        {
            $setting = Setting::updateOrCreate(
                [
                    'name' => Setting::AUTO_TICKET_DISTRIBUTION,
                ],
                [
                    'status' => Setting::STATUS_ACTIVE,
                ]
            );
        }
        else
        {
            $setting = Setting::updateOrCreate(
                [
                    'name' => Setting::AUTO_TICKET_DISTRIBUTION,
                ],
                [
                    'status' => Setting::STATUS_INACTIVE,
                ]
            );
        }

        return response()->json(['success' => true]);

    }

    public function ajaxFetchData(Request $request)
    {
        // dd($request->all());
        $user            = Auth::user();
        $search          = $request->search;
        $general_search  = filter_var($request->is_general_search, FILTER_VALIDATE_BOOLEAN);
        $where           = Array();
        $myAgentTickets  = false;

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $agents           = User::withCount(['tickets'])->allAgents()->get();

        $tags       = Tag::all();
        // $categories = Category::all();

        $categories         = Category::where('id', '<', 88)->get();
        $newCategory        = new Collection();

        foreach($categories as $key => $category)
        {

            $newCategory->push($category);

            if( $category->parent_category_id == 225 ) // Delayed
            {
                $tmpCat = Category::where('id', '>=', 88)->where('parent_category_id', 226)->first();

                $newCategory->push($tmpCat);
            }

        }

        $categories = $newCategory;
        

        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        $otherTickets = (isset($request->other_tickets) && $request->other_tickets == 'true') ? true : false;

        if ( !empty($request->ticket_status) )
        {
            array_push($where, Array('status_id',$request->ticket_status));
        }

        if ( !empty($request->ticket_type) )
        {
            array_push($where, Array('type_id',$request->ticket_type));
        }

        if ( !empty($request->ticket_priority) )
        {
            array_push($where, Array('priority_id',$request->ticket_priority));
        }

        if( $request->ajax() )
        {


            if ( !empty( $search) && $general_search )
            {

                // if ( $general_search )
                // {

                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->with(['categories','assignedTo','origin','status','type','priority'])
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        })
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                // }
                /*else
                {
                    
                    $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->orderBy('thread_started_at', 'DESC')
                                    ->paginate(20);
                }*/

            }
            elseif ( $request->path == '/tickets/needs-urgent-attention' )
            {
                // dump(222);
                //use to identify if any admin/manager/developer, show all current ticket count depending on view
                // else show only the auth user tickets data
                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->where($where)
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereNotIn('requester', ['no-reply@localsearch.com.au'])
                                        // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                        ->where('created_at', '<', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                                        // dump(333);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                 ->where($where)
                //                 ->where(function($query) use($search){
                //                     $query->where('subject','like','%'.$search.'%')
                //                             ->orWhere('snippet','like','%'.$search.'%')
                //                             ->orWhere('requester','like','%'.$search.'%');
                //                 })
                //                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                //                 // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                //                         ->where('created_at', '<', \Carbon\Carbon::now()->subDays(1)->toDateTimeString())

                //                 ->orderBy('thread_started_at', 'DESC')
                //                 ->paginate(20);
                //                 // dump(444);
                // }
            }
            elseif ( $request->path == '/tickets/solved' )
            {
                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
                                        ->whereBetween('updated_at', 

                                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                        )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                             ->where(function($query) use($search){
                //                                 $query->where('subject','like','%'.$search.'%')
                //                                         ->orWhere('snippet','like','%'.$search.'%')
                //                                         ->orWhere('requester','like','%'.$search.'%');
                //                             })
                //                             ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
                //                             ->orderBy('thread_started_at', 'DESC')
                //                             ->paginate(20);
                // }
            }
            elseif ( $request->path == '/tickets/closed' )
            {
                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    // $tickets = Ticket::excludeFacebook()->excludeEbay()
                    //                     ->where('subject','like','%'.$search.'%')
                    //                     ->orWhere('snippet','like','%'.$search.'%')
                    //                     ->orWhere('requester','like','%'.$search.'%')
                    //                     ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
                    //                     ->orderBy('thread_started_at', 'DESC')
                    //                     ->paginate(20);
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
                                        ->whereBetween('updated_at', 

                                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                        )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                     ->where(function($query) use($search){
                //                         $query->where('subject','like','%'.$search.'%')
                //                                 ->orWhere('snippet','like','%'.$search.'%')
                //                                 ->orWhere('requester','like','%'.$search.'%');
                //                     })
                //                     ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
                //                     ->orderBy('thread_started_at', 'DESC')
                //                     ->paginate(20);
                // }
            }
            elseif ( $request->path == '/tickets/over-4-hours' )
            {
                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->where($where)
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereNotIn('requester', ['no-reply@localsearch.com.au'])
                                        // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                                        ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(1)->toDateTimeString(), \Carbon\Carbon::now()->subHours(4)->toDateTimeString()])
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                     ->where($where)
                //                     ->where(function($query) use($search){
                //                         $query->where('subject','like','%'.$search.'%')
                //                                 ->orWhere('snippet','like','%'.$search.'%')
                //                                 ->orWhere('requester','like','%'.$search.'%');
                //                     })
                //                     ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                //                     // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                //                     ->whereBetween('created_at', [\Carbon\Carbon::now()->subDays(1)->toDateTimeString(), \Carbon\Carbon::now()->subHours(4)->toDateTimeString()])
                //                     ->orderBy('thread_started_at', 'DESC')
                //                     ->paginate(20);
                // }
            }
            elseif ( $request->path == '/tickets/under-4-hours' )
            {
                // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                // {
                    $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->where($where)
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        })
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereNotIn('requester', ['no-reply@localsearch.com.au'])
                                        // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                                        ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(4)->toDateTimeString())
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                // }
                // else
                // {
                //     $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                //                     ->where($where)
                //                     ->where(function($query) use($search){
                //                         $query->where('subject','like','%'.$search.'%')
                //                                 ->orWhere('snippet','like','%'.$search.'%')
                //                                 ->orWhere('requester','like','%'.$search.'%');
                //                     })
                //                     ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                //                     // ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                //                     ->where('created_at', '>=', \Carbon\Carbon::now()->subHours(4)->toDateTimeString())
                //                     ->orderBy('thread_started_at', 'DESC')
                //                     ->paginate(20);
                // }
            }
            elseif ( $request->path == '/tickets/my-tickets' )
            {
                // $tickets = $user->tickets()->excludeFacebook()
                //                 ->where('subject','like','%'.$search.'%')
                //                 ->orWhere('snippet','like','%'.$search.'%')
                //                 ->orWhere('requester','like','%'.$search.'%')
                //                 ->orderBy('created_at', 'DESC')
                //                 ->paginate(20);

                /*if ( !empty($search) )
                {
                    $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                ->where(function($query) use($search){
                                    $query->where('subject','like','%'.$search.'%')
                                            ->orWhere('snippet','like','%'.$search.'%')
                                            ->orWhere('requester','like','%'.$search.'%');
                                })
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                ->orderBy('created_at', 'DESC')->paginate(20);
                }
                else
                {
                    $tickets = $user->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
                }*/

                // if ( $user->rolesByIdExists([Role::MANAGER, Role::CUSTOMER_SERVICE_SUPPORT]) || $request->other_tickets == 'true' )
                // if ( $user->rolesByIdExists([Role::CUSTOMER_SERVICE_SUPPORT]) || $request->other_tickets == 'true' )
                // if ( $request->other_tickets == 'true' )
                if ( $request->other_tickets )
                {

                    if ( !empty($search) )
                    {
                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                    ->orderBy('created_at', 'DESC')->paginate(20);
                    }
                    else
                    {
                        $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
                    }

                }
                else
                {
                    if ( !empty($search) )
                    {
                        $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                    ->orderBy('created_at', 'DESC')->paginate(20);
                    }
                    else
                    {
                        $tickets = $user->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
                    }
                }

            }
            elseif ( $request->path == '/tickets/unassigned' )
            {
                // $tickets = $user->tickets()->excludeFacebook()
                //                 ->where('subject','like','%'.$search.'%')
                //                 ->orWhere('snippet','like','%'.$search.'%')
                //                 ->orWhere('requester','like','%'.$search.'%')
                //                 ->orderBy('created_at', 'DESC')
                //                 ->paginate(20);

                /*if ( !empty($search) )
                {
                    $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                ->where(function($query) use($search){
                                    $query->where('subject','like','%'.$search.'%')
                                            ->orWhere('snippet','like','%'.$search.'%')
                                            ->orWhere('requester','like','%'.$search.'%');
                                })
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                ->orderBy('created_at', 'DESC')->paginate(20);
                }
                else
                {
                    $tickets = $user->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
                }*/

                // if ( $user->rolesByIdExists([Role::MANAGER, Role::CUSTOMER_SERVICE_SUPPORT]) || $request->other_tickets == 'true' )
                // if ( $user->rolesByIdExists([Role::CUSTOMER_SERVICE_SUPPORT]) || $request->other_tickets == 'true' )
                // if ( $request->other_tickets == 'true' )
                if ( $request->other_tickets )
                {

                    if ( !empty($search) )
                    {
                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                    ->orderBy('created_at', 'DESC')->paginate(20);
                    }
                    else
                    {
                        $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
                    }

                }
                else
                {
                    if ( !empty($search) )
                    {
                        $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                    ->orderBy('created_at', 'DESC')->paginate(20);
                    }
                    else
                    {
                        $tickets = $user->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
                    }
                }

            }
            elseif ( $request->path == '/tickets/sent' )
            {

                if ( !empty($search) )
                {
                    $tickets = $user->tickets()->excludeFacebook()
                                ->where(function($query) use($search){
                                    $query->where('subject','like','%'.$search.'%')
                                            ->orWhere('snippet','like','%'.$search.'%')
                                            ->orWhere('requester','like','%'.$search.'%');
                                })
                                ->orderBy('created_at', 'DESC')->paginate(20);
                }
                else
                {
                    $tickets = $user->tickets()->excludeFacebook()->orderBy('created_at', 'DESC')->paginate(20);
                }

                return view('ticketing.ticketing_table_data_sent', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'categories', 'otherTickets', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']))->render();

            }
            elseif ( $request->path == '/my-agent-tickets' )
            {

                $user           = User::find( $request->user_id );
                $userView       = $request->user_view;
                $myAgentTickets = true;
                $tickets        = '';

                if ( $userView == 'view-tickets' )
                {
                    $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->orderBy('updated_at', 'DESC')->paginate(20);
                }
                elseif ( $userView == 'pending' )
                {

                    $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                    ->orderBy('updated_at', 'DESC')
                                    ->paginate(20);
                }
                elseif ( $userView == 'solved' )
                {

                    $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
                                    ->orderBy('updated_at', 'DESC')
                                    ->paginate(20);
                }
                else // closed
                {
                    $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
                                    ->where(function($query) use($search){
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                    })
                                    ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
                                    ->orderBy('updated_at', 'DESC')
                                    ->paginate(20);
                }
                // dump($request->all());
                // dd($tickets);

            }
            elseif ( strpos($request->path, '/tickets/tag/') !== false )
            {

                $slug = explode('/', $request->path);
                $slug = end($slug);

                if ( !empty($search) )
                {

                    if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                    {
                        $tickets = Ticket::excludeFacebook()
                                            ->whereHas('tags', function ($q) use ($slug) {
                                                            $q->where('slug', $slug);
                                                        })
                                            ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%');
                                            })
                                            ->orderBy('created_at', 'DESC')->paginate(20);
                    }
                    else
                    {
                        $tickets = $user
                                        ->tickets()
                                        ->excludeFacebook()
                                        ->whereHas('tags', function ($q) use ($slug) {
                                            $q->where('slug', $slug);
                                        })
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        })
                                        ->orderBy('created_at', 'DESC')
                                        ->paginate(20);
                    }

                }
                else
                {

                    // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                    // {
                        $tickets = Ticket::excludeFacebook()
                                            ->whereHas('tags', function ($q) use ($slug) {
                                                            $q->where('slug', $slug);
                                                        })
                                            ->orderBy('created_at', 'DESC')->paginate(20);
                    // }
                    // else
                    // {
                    //     $tickets = $user
                    //                     ->tickets()
                    //                     ->excludeFacebook()
                    //                     ->whereHas('tags', function ($q) use ($slug) {
                    //                         $q->where('slug', $slug);
                    //                     })
                    //                     ->orderBy('created_at', 'DESC')
                    //                     ->paginate(20);
                    // }

                }

            }
            elseif ( strpos($request->path, '/tickets/category/') !== false )
            {

                $slug = explode('/', $request->path);
                $slug = end($slug);

                if ( !empty($search) )
                {

                    // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                    // {
                        $tickets = Ticket::excludeFacebook()
                                            ->whereHas('categories', function ($q) use ($slug) {
                                                            $q->where('slug', $slug);
                                                        })
                                            ->whereBetween('updated_at', 

                                                [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                            )
                                            ->where(function($query) use($search){
                                                $query->where('subject','like','%'.$search.'%')
                                                        ->orWhere('snippet','like','%'.$search.'%')
                                                        ->orWhere('requester','like','%'.$search.'%');
                                            })
                                            ->orderBy('created_at', 'DESC')->paginate(20);
                    // }
                    // else
                    // {
                    //     $tickets = $user
                    //                     ->tickets()
                    //                     ->excludeFacebook()
                    //                     ->whereHas('categories', function ($q) use ($slug) {
                    //                         $q->where('slug', $slug);
                    //                     })
                    //                     ->where(function($query) use($search){
                    //                         $query->where('subject','like','%'.$search.'%')
                    //                                 ->orWhere('snippet','like','%'.$search.'%')
                    //                                 ->orWhere('requester','like','%'.$search.'%');
                    //                     })
                    //                     ->orderBy('created_at', 'DESC')
                    //                     ->paginate(20);
                    // }

                }
                else
                {

                    // if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                    // {
                        $tickets = Ticket::excludeFacebook()
                                            ->whereHas('categories', function ($q) use ($slug) {
                                                            $q->where('slug', $slug);
                                                        })->whereBetween('updated_at', 

                                                            [\Carbon\Carbon::now()->subMonth(2), \Carbon\Carbon::now()]

                                                        )
                                            ->orderBy('created_at', 'DESC')->paginate(20);
                    // }
                    // else
                    // {
                    //     $tickets = $user
                    //                     ->tickets()
                    //                     ->excludeFacebook()
                    //                     ->whereHas('categories', function ($q) use ($slug) {
                    //                         $q->where('slug', $slug);
                    //                     })
                    //                     ->orderBy('created_at', 'DESC')
                    //                     ->paginate(20);
                    // }

                }

            }
            else
            {
                // user custom pages conditions
                if ( $user->hasCustomPages() )
                {
                    $uri  = $request->path;
                    $slug = substr($uri, strrpos($uri, '/') + 1);

                    $userCustompage = UserCustomPage::where([
                                            'user_id' => $user->id,
                                            'slug'    => $slug
                                        ])->first();

                    $tickets = '';
                    if ( $slug == 'from-ebay' )
                    {

                        if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                        {
                            $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
                        }
                        // elseif ( $user->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
                        // {
                        //     $tickets = $user->tickets()->excludeFacebook()->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
                        // }

                        // ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])

                        // $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);

                    }
                    elseif ( !empty($userCustompage) && $userCustompage->count() )
                    {

                        // $tickets        = '';
                        $pageConditions = $userCustompage->pageConditions()->orderBy('operator', 'ASC')->get();
                        
                        foreach ( $pageConditions as $key => $pageCondition )
                        {

                            //setup eloquent on first iteration
                            if ( $key === 0 )
                            {
                                if ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                                {
                                    $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                                elseif ( $user->rolesByIdExists([Role::AGENT_EBAY]) )
                                {

                                    if ( $pageCondition->filter == 'origin' && $pageCondition->filter_id == 8 ) // 8 = FAE
                                    {
                                        $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                    }
                                    else
                                    {
                                        $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                    }
                                }

                                //tmp solution for ebay mixing solved tickets to pending,unassigned
                                if ( strpos(strtolower($userCustompage->name), 'ebay') !== false )
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
                            elseif ( $user->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::AGENT_EBAY, Role::CUSTOMER_SERVICE_SUPPORT]) )
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
                    // $tickets = $tickets->where('subject','like','%'.$search.'%')
                    //                     ->orWhere('snippet','like','%'.$search.'%')
                    //                     ->orWhere('requester','like','%'.$search.'%');
                    
                    if ( $tickets == '' )
                    {
                        $tickets = Ticket::excludeFacebook()->excludeEbay()
                                        ->with(['categories','assignedTo','origin','status','type','priority'])
                                        ->where(function($query) use($search){
                                            $query->where('subject','like','%'.$search.'%')
                                                    ->orWhere('snippet','like','%'.$search.'%')
                                                    ->orWhere('requester','like','%'.$search.'%');
                                        });
                    }
                    else
                    {
                        $tickets = $tickets->where(function ($query) use($search) {
                                        $query->where('subject','like','%'.$search.'%')
                                                ->orWhere('snippet','like','%'.$search.'%')
                                                ->orWhere('requester','like','%'.$search.'%');
                                        });
                    }

                    $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);
                    
                }
            }

            return view('ticketing.ticketing_table_data', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'categories', 'otherTickets', 'general_search', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']))->render();

        }

    }

    // public function ajaxFetchData(Request $request)
    // {
    //     // dd($request->all());
    //     $user           = Auth::user();
    //     $search         = $request->search;
    //     $general_search = filter_var($request->is_general_search, FILTER_VALIDATE_BOOLEAN);
    //     $where          = Array();
    //     $myAgentTickets = false;

    //     $ticketPriorities = TicketPriority::all();
    //     $ticketTypes      = TicketType::all();
    //     $ticketStatus     = TicketStatus::all();
    //     $customVariables  = CustomVariable::all();
    //     $emailTemplates   = EmailTemplate::all();
    //     $agents           = User::allAgents()->get();

    //     $tags       = Tag::all();
    //     $categories = Category::all();

    //     $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
    //     $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

    //     $otherTickets = (isset($request->other_tickets) && $request->other_tickets == 'true' ? true : false);

    //     if ( !empty($request->ticket_status) )
    //     {
    //         array_push($where, Array('status_id',$request->ticket_status));
    //     }

    //     if ( !empty($request->ticket_type) )
    //     {
    //         array_push($where, Array('type_id',$request->ticket_type));
    //     }

    //     if ( !empty($request->ticket_priority) )
    //     {
    //         array_push($where, Array('priority_id',$request->ticket_priority));
    //     }

    //     if( $request->ajax() )
    //     {

    //         if ( !empty( $search) )
    //         {

    //             if ( $general_search )
    //             {

    //                 $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->orderBy('thread_started_at', 'DESC')
    //                                     ->paginate(20);
    //             }
    //             else
    //             {

    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->orderBy('thread_started_at', 'DESC')
    //                                 ->paginate(20);
    //             }

    //         }
    //         elseif ( $request->path == '/tickets/needs-urgent-attention' )
    //         {
    //             // dump(222);
    //             //use to identify if any admin/manager/developer, show all current ticket count depending on view
    //             // else show only the auth user tickets data
    //             if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //             {
    //                 $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                                     ->where($where)
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                                     ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
    //                                     ->orderBy('thread_started_at', 'DESC')
    //                                     ->paginate(20);
    //                                     // dump(333);
    //             }
    //             else
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                             ->where($where)
    //                             ->where(function($query) use($search){
    //                                 $query->where('subject','like','%'.$search.'%')
    //                                         ->orWhere('snippet','like','%'.$search.'%')
    //                                         ->orWhere('requester','like','%'.$search.'%');
    //                             })
    //                             ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                             ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
    //                             ->orderBy('thread_started_at', 'DESC')
    //                             ->paginate(20);
    //                             // dump(444);
    //             }
    //         }
    //         elseif ( $request->path == '/tickets/solved' )
    //         {
    //             if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //             {
    //                 $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
    //                                     ->orderBy('thread_started_at', 'DESC')
    //                                     ->paginate(20);
    //             }
    //             else
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                                         ->where(function($query) use($search){
    //                                             $query->where('subject','like','%'.$search.'%')
    //                                                     ->orWhere('snippet','like','%'.$search.'%')
    //                                                     ->orWhere('requester','like','%'.$search.'%');
    //                                         })
    //                                         ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
    //                                         ->orderBy('thread_started_at', 'DESC')
    //                                         ->paginate(20);
    //             }
    //         }
    //         elseif ( $request->path == '/tickets/closed' )
    //         {
    //             if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //             {
    //                 // $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                 //                     ->where('subject','like','%'.$search.'%')
    //                 //                     ->orWhere('snippet','like','%'.$search.'%')
    //                 //                     ->orWhere('requester','like','%'.$search.'%')
    //                 //                     ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
    //                 //                     ->orderBy('thread_started_at', 'DESC')
    //                 //                     ->paginate(20);
    //                 $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
    //                                     ->orderBy('thread_started_at', 'DESC')
    //                                     ->paginate(20);
    //             }
    //             else
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
    //                                 ->orderBy('thread_started_at', 'DESC')
    //                                 ->paginate(20);
    //             }
    //         }
    //         elseif ( $request->path == '/tickets/over-4-hours' )
    //         {
    //             if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //             {
    //                 $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                                     ->where($where)
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                                     ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
    //                                     ->orderBy('thread_started_at', 'DESC')
    //                                     ->paginate(20);
    //             }
    //             else
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where($where)
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                                 ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
    //                                 ->orderBy('thread_started_at', 'DESC')
    //                                 ->paginate(20);
    //             }
    //         }
    //         elseif ( $request->path == '/tickets/under-4-hours' )
    //         {
    //             if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //             {
    //                 $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                                     ->where($where)
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                                     ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
    //                                     ->orderBy('thread_started_at', 'DESC')
    //                                     ->paginate(20);
    //             }
    //             else
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where($where)
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                                 ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
    //                                 ->orderBy('thread_started_at', 'DESC')
    //                                 ->paginate(20);
    //             }
    //         }
    //         elseif ( $request->path == '/tickets/my-tickets' )
    //         {
    //             // $tickets = Auth::user()->tickets()->excludeFacebook()
    //             //                 ->where('subject','like','%'.$search.'%')
    //             //                 ->orWhere('snippet','like','%'.$search.'%')
    //             //                 ->orWhere('requester','like','%'.$search.'%')
    //             //                 ->orderBy('created_at', 'DESC')
    //             //                 ->paginate(20);

    //             /*if ( !empty($search) )
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                             ->where(function($query) use($search){
    //                                 $query->where('subject','like','%'.$search.'%')
    //                                         ->orWhere('snippet','like','%'.$search.'%')
    //                                         ->orWhere('requester','like','%'.$search.'%');
    //                             })
    //                             ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                             ->orderBy('created_at', 'DESC')->paginate(20);
    //             }
    //             else
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
    //             }*/

    //             // if ( Auth::user()->rolesByIdExists([Role::MANAGER, Role::CUSTOMER_SERVICE_SUPPORT]) || $request->other_tickets == 'true' )
    //             // if ( Auth::user()->rolesByIdExists([Role::CUSTOMER_SERVICE_SUPPORT]) || $request->other_tickets == 'true' )
    //             if ( $request->other_tickets == 'true' )
    //             {

    //                 if ( !empty($search) )
    //                 {
    //                     $tickets = Ticket::excludeFacebook()->excludeEbay()
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                                 ->orderBy('created_at', 'DESC')->paginate(20);
    //                 }
    //                 else
    //                 {
    //                     $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
    //                 }

    //             }
    //             else
    //             {
    //                 if ( !empty($search) )
    //                 {
    //                     $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
    //                                 ->orderBy('created_at', 'DESC')->paginate(20);
    //                 }
    //                 else
    //                 {
    //                     $tickets = Auth::user()->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->orderBy('created_at', 'DESC')->paginate(20);
    //                 }
    //             }

    //         }
    //         elseif ( $request->path == '/tickets/sent' )
    //         {

    //             if ( !empty($search) )
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()
    //                             ->where(function($query) use($search){
    //                                 $query->where('subject','like','%'.$search.'%')
    //                                         ->orWhere('snippet','like','%'.$search.'%')
    //                                         ->orWhere('requester','like','%'.$search.'%');
    //                             })
    //                             ->orderBy('created_at', 'DESC')->paginate(20);
    //             }
    //             else
    //             {
    //                 $tickets = Auth::user()->tickets()->excludeFacebook()->orderBy('created_at', 'DESC')->paginate(20);
    //             }

    //             return view('ticketing.ticketing_table_data_sent', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'categories', 'otherTickets', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']))->render();

    //         }
    //         elseif ( $request->path == '/my-agent-tickets' )
    //         {

    //             $user           = User::find( $request->user_id );
    //             $userView       = $request->user_view;
    //             $myAgentTickets = true;
    //             $tickets        = '';

    //             if ( $userView == 'view-tickets' )
    //             {
    //                 $tickets = $user->tickets()->excludeFacebook()->excludeEbay()->orderBy('updated_at', 'DESC')->paginate(20);
    //             }
    //             elseif ( $userView == 'pending' )
    //             {

    //                 $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
    //                                 ->orderBy('updated_at', 'DESC')
    //                                 ->paginate(20);
    //             }
    //             elseif ( $userView == 'solved' )
    //             {

    //                 $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_SOLVED])
    //                                 ->orderBy('updated_at', 'DESC')
    //                                 ->paginate(20);
    //             }
    //             else // closed
    //             {
    //                 $tickets = $user->tickets()->excludeFacebook()->excludeEbay()
    //                                 ->where(function($query) use($search){
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                 })
    //                                 ->whereIn('status_id', [TicketStatus::STATUS_CLOSED])
    //                                 ->orderBy('updated_at', 'DESC')
    //                                 ->paginate(20);
    //             }
    //             // dump($request->all());
    //             // dd($tickets);

    //         }
    //         elseif ( strpos($request->path, '/tickets/tag/') !== false )
    //         {

    //             $slug = explode('/', $request->path);
    //             $slug = end($slug);

    //             if ( !empty($search) )
    //             {

    //                 if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //                 {
    //                     $tickets = Ticket::excludeFacebook()
    //                                         ->whereHas('tags', function ($q) use ($slug) {
    //                                                         $q->where('slug', $slug);
    //                                                     })
    //                                         ->where(function($query) use($search){
    //                                             $query->where('subject','like','%'.$search.'%')
    //                                                     ->orWhere('snippet','like','%'.$search.'%')
    //                                                     ->orWhere('requester','like','%'.$search.'%');
    //                                         })
    //                                         ->orderBy('created_at', 'DESC')->paginate(20);
    //                 }
    //                 else
    //                 {
    //                     $tickets = Auth::user()
    //                                     ->tickets()
    //                                     ->excludeFacebook()
    //                                     ->whereHas('tags', function ($q) use ($slug) {
    //                                         $q->where('slug', $slug);
    //                                     })
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->orderBy('created_at', 'DESC')
    //                                     ->paginate(20);
    //                 }

    //             }
    //             else
    //             {

    //                 if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //                 {
    //                     $tickets = Ticket::excludeFacebook()
    //                                         ->whereHas('tags', function ($q) use ($slug) {
    //                                                         $q->where('slug', $slug);
    //                                                     })
    //                                         ->orderBy('created_at', 'DESC')->paginate(20);
    //                 }
    //                 else
    //                 {
    //                     $tickets = Auth::user()
    //                                     ->tickets()
    //                                     ->excludeFacebook()
    //                                     ->whereHas('tags', function ($q) use ($slug) {
    //                                         $q->where('slug', $slug);
    //                                     })
    //                                     ->orderBy('created_at', 'DESC')
    //                                     ->paginate(20);
    //                 }

    //             }

    //         }
    //         elseif ( strpos($request->path, '/tickets/category/') !== false )
    //         {

    //             $slug = explode('/', $request->path);
    //             $slug = end($slug);

    //             if ( !empty($search) )
    //             {

    //                 if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //                 {
    //                     $tickets = Ticket::excludeFacebook()
    //                                         ->whereHas('categories', function ($q) use ($slug) {
    //                                                         $q->where('slug', $slug);
    //                                                     })
    //                                         ->where(function($query) use($search){
    //                                             $query->where('subject','like','%'.$search.'%')
    //                                                     ->orWhere('snippet','like','%'.$search.'%')
    //                                                     ->orWhere('requester','like','%'.$search.'%');
    //                                         })
    //                                         ->orderBy('created_at', 'DESC')->paginate(20);
    //                 }
    //                 else
    //                 {
    //                     $tickets = Auth::user()
    //                                     ->tickets()
    //                                     ->excludeFacebook()
    //                                     ->whereHas('categories', function ($q) use ($slug) {
    //                                         $q->where('slug', $slug);
    //                                     })
    //                                     ->where(function($query) use($search){
    //                                         $query->where('subject','like','%'.$search.'%')
    //                                                 ->orWhere('snippet','like','%'.$search.'%')
    //                                                 ->orWhere('requester','like','%'.$search.'%');
    //                                     })
    //                                     ->orderBy('created_at', 'DESC')
    //                                     ->paginate(20);
    //                 }

    //             }
    //             else
    //             {

    //                 if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //                 {
    //                     $tickets = Ticket::excludeFacebook()
    //                                         ->whereHas('categories', function ($q) use ($slug) {
    //                                                         $q->where('slug', $slug);
    //                                                     })
    //                                         ->orderBy('created_at', 'DESC')->paginate(20);
    //                 }
    //                 else
    //                 {
    //                     $tickets = Auth::user()
    //                                     ->tickets()
    //                                     ->excludeFacebook()
    //                                     ->whereHas('categories', function ($q) use ($slug) {
    //                                         $q->where('slug', $slug);
    //                                     })
    //                                     ->orderBy('created_at', 'DESC')
    //                                     ->paginate(20);
    //                 }

    //             }

    //         }
    //         else
    //         {
    //             // user custom pages conditions
    //             if ( $user->hasCustomPages() )
    //             {
    //                 $uri  = $request->path;
    //                 $slug = substr($uri, strrpos($uri, '/') + 1);
    //                 // dd($slug);
    //                 $userCustompage = UserCustomPage::where([
    //                                         'user_id' => $user->id,
    //                                         'slug'    => $slug
    //                                     ])->first();

    //                 $tickets = '';
    //                 if ( $slug == 'from-ebay' )
    //                 {

    //                     if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
    //                     {
    //                         $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
    //                     }
    //                     // elseif ( Auth::user()->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
    //                     // {
    //                     //     $tickets = Auth::user()->tickets()->excludeFacebook()->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
    //                     // }

    //                     // ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])

    //                     // $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);

    //                 }     
    //                 if ( !empty($userCustompage) && $userCustompage->count() )
    //                 {

    //                     // $tickets        = '';
    //                     $pageConditions = $userCustompage->pageConditions()->orderBy('operator', 'ASC')->get();
                        
    //                     foreach ( $pageConditions as $key => $pageCondition )
    //                     {

    //                         //setup eloquent on first iteration
    //                         if ( $key === 0 )
    //                         {
    //                             if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //                             {
    //                                 $tickets = Ticket::excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
    //                             }
    //                             elseif ( Auth::user()->rolesByIdExists([Role::AGENT_EBAY]) )
    //                             {
    //                                 $tickets = Auth::user()->tickets()->excludeFacebook()->excludeEbay()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
    //                             }
    //                         }
    //                         elseif ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::AGENT_EBAY, Role::CUSTOMER_SERVICE_SUPPORT]) )
    //                         {

    //                             if ( $pageCondition->operator == 'AND' )
    //                             {
    //                                 $tickets->where($pageCondition->filter.'_id', $pageCondition->filter_id);
    //                             }
    //                             else
    //                             {
    //                                 $tickets->orWhere($pageCondition->filter.'_id', $pageCondition->filter_id);
    //                             }

    //                         }
                            
    //                     }
                        
    //                 }

    //                 // $tickets = $tickets->where('subject','like','%'.$search.'%')
    //                 //                     ->orWhere('snippet','like','%'.$search.'%')
    //                 //                     ->orWhere('requester','like','%'.$search.'%');
    //                 $tickets = $tickets->where(function ($query) use($search) {
    //                                     $query->where('subject','like','%'.$search.'%')
    //                                             ->orWhere('snippet','like','%'.$search.'%')
    //                                             ->orWhere('requester','like','%'.$search.'%');
    //                                     });

    //                 $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);
                    
    //             }
    //         }

    //         return view('ticketing.ticketing_table_data', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents', 'myAgentTickets', 'tags', 'categories', 'otherTickets', 'general_search', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists']))->render();

    //     }

    // }

    public function customFetchTickets($page = null)
    {
        $user    = Auth::user();
        $tickets = '';

        if( !empty( $page ) )
        {

            if ( $page == '/tickets/needs-urgent-attention' )
            {
                // dump(222);
                //use to identify if any admin/manager/developer, show all current ticket count depending on view
                // else show only the auth user tickets data
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()
                                        // ->where($where)
                                        // ->where('subject','like','%'.$search.'%')
                                        // ->orWhere('snippet','like','%'.$search.'%')
                                        // ->orWhere('requester','like','%'.$search.'%')
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                                        // dump(333);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                // ->where($where)
                                // ->where('subject','like','%'.$search.'%')
                                // ->orWhere('snippet','like','%'.$search.'%')
                                // ->orWhere('requester','like','%'.$search.'%')
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
                                // dump(444);
                }
            }
            elseif ( $page == '/tickets/solved' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()
                                        // ->where('subject','like','%'.$search.'%')
                                        // ->orWhere('snippet','like','%'.$search.'%')
                                        // ->orWhere('requester','like','%'.$search.'%')
                                        ->where('status_id', TicketStatus::STATUS_SOLVED)
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                            ->where('status_id', TicketStatus::STATUS_SOLVED)
                                            ->orderBy('thread_started_at', 'DESC')
                                            ->paginate(20);
                }
            }
            elseif ( $page == '/tickets/closed' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()
                                        // ->where('subject','like','%'.$search.'%')
                                        // ->orWhere('snippet','like','%'.$search.'%')
                                        // ->orWhere('requester','like','%'.$search.'%')
                                        ->where('status_id', TicketStatus::STATUS_CLOSED)
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                    // ->where('subject','like','%'.$search.'%')
                                    // ->orWhere('snippet','like','%'.$search.'%')
                                    // ->orWhere('requester','like','%'.$search.'%')
                                    ->where('status_id', TicketStatus::STATUS_CLOSED)
                                    ->orderBy('thread_started_at', 'DESC')
                                    ->paginate(20);
                }
            }
            elseif ( $page == '/tickets/over-4-hours' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()
                                        // ->where($where)
                                        // ->where('subject','like','%'.$search.'%')
                                        // ->orWhere('snippet','like','%'.$search.'%')
                                        // ->orWhere('requester','like','%'.$search.'%')
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                    // ->where($where)
                                    // ->where('subject','like','%'.$search.'%')
                                    // ->orWhere('snippet','like','%'.$search.'%')
                                    // ->orWhere('requester','like','%'.$search.'%')
                                    ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                    ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                                    ->orderBy('thread_started_at', 'DESC')
                                    ->paginate(20);
                }
            }
            elseif ( $page == '/tickets/under-4-hours' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()
                                        // ->where($where)
                                        // ->where('subject','like','%'.$search.'%')
                                        // ->orWhere('snippet','like','%'.$search.'%')
                                        // ->orWhere('requester','like','%'.$search.'%')
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                    // ->where($where)
                                    // ->where('subject','like','%'.$search.'%')
                                    // ->orWhere('snippet','like','%'.$search.'%')
                                    // ->orWhere('requester','like','%'.$search.'%')
                                    ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                    ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                                    ->orderBy('thread_started_at', 'DESC')
                                    ->paginate(20);
                }
            }
            elseif ( $page == '/tickets/my-tickets' )
            {
                $tickets = Auth::user()->tickets()->excludeFacebook()
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])
                                // ->where('subject','like','%'.$search.'%')
                                // ->orWhere('snippet','like','%'.$search.'%')
                                // ->orWhere('requester','like','%'.$search.'%')
                                ->orderBy('created_at', 'DESC')
                                ->paginate(20);
            }
            else
            {
                // user custom pages conditions
                if ( $user->hasCustomPages() )
                {
                    // $uri  = $request->path;
                    $uri  = $page;
                    $slug = substr($uri, strrpos($uri, '/') + 1);

                    $userCustompage = UserCustomPage::where([
                                            'user_id' => $user->id,
                                            'slug'    => $slug
                                        ])->first();

                    if ( strpos(strtolower($page), 'from-ebay') !== false )
                    {

                        if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT, Role::AGENT]) )
                        {
                            $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING])->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
                        }
                        // elseif ( Auth::user()->rolesByIdExists([Role::AGENT, Role::AGENT_EBAY]) )
                        // {
                        //     $tickets = Auth::user()->tickets()->excludeFacebook()->where('origin_id', TicketOrigin::ORIGIN_EBAY)->whereIn('requester', ['eBay','csfeedback@go.ebay.com']);
                        // }

                        // ->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])

                        $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);

                    }
                    elseif ( !empty($userCustompage) && $userCustompage->count() )
                    {

                        // $tickets        = '';
                        $pageConditions = $userCustompage->pageConditions()->orderBy('operator', 'ASC')->get();
                        
                        foreach ( $pageConditions as $key => $pageCondition )
                        {

                            //setup eloquent on first iteration
                            if ( $key === 0 )
                            {
                                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                                {
                                    $tickets = Ticket::excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                                elseif ( Auth::user()->rolesByIdExists([Role::AGENT_EBAY]) )
                                {
                                    $tickets = Auth::user()->tickets()->excludeFacebook()->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->whereIn('status_id', [TicketStatus::STATUS_PENDING, TicketStatus::STATUS_UNASSIGNED])->where($pageCondition->filter.'_id', $pageCondition->filter_id);
                                }
                            }
                            elseif ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::AGENT_EBAY, Role::CUSTOMER_SERVICE_SUPPORT]) )
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

                        $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);
                        
                    }

                    // $tickets = $tickets->where('subject','like','%'.$search.'%')
                    //                     ->orWhere('snippet','like','%'.$search.'%')
                    //                     ->orWhere('requester','like','%'.$search.'%');

                    // $tickets = $tickets->orderBy('thread_started_at', 'DESC')->paginate(20);
                    
                }
            }

            return $tickets;

        }

    }

    public static function messageEncode($message)
    {
        
        // $message = utf8_decode($message);

        $doc = new \DOMDocument();
        $doc->encoding = 'utf-8';
        // @$doc->loadHTML($message);
        // @$doc->loadHTML( utf8_decode( $message ) );
        @$doc->loadHTML( $message );

        $images = $doc->getElementsByTagName('img');

        foreach ($images as $image)
        {
            // if ( isset($attachments) )
            // {
                
                $old_src = $image->getAttribute('src');
                
                $image->setAttribute('src', 'cid:test123.png');

            // }
        }

        $html = $doc->saveHTML();
        // $html = utf8_encode($html);
        
        return $html;

    }

    public function ajaxSendComposedMessage(Request $request)
    {
        //message, notes, emailTo, subject
        if( $request->ajax() ) {

            // dd($request->all());

            $validation = Validator::make($request->all(), [
                'subject' => 'required',
                'emailTo' => 'required|email',
                'message' => 'required',
                ]);

            if($validation->passes())
            {

                $user = Auth::user();

                // $userName      = $user->name;
                // $userEmail     = '<'.$user->email.'>';
                // $userNameEmail = $userName.' '.$userEmail;


                // $assignedTicket = AssignedTicket::where(['ticket_id' => $createTicket->id]);
                
                // if (!$assignedTicket->count())
                // {
                //     AssignedTicket::create(['user_id' => $user->id, 'ticket_id' => $createTicket->id]);
                // }

                $getUserSignature = '';
                $getUserSignature = $this->getUserSignature();
                $src              = '';

                if ( !empty( $getUserSignature ) )
                {

                    $pattern = '/src="([^"]*)"/';
                    preg_match($pattern, $getUserSignature, $matches);
                    if ( !empty($matches) )
                    {
                        $src     = str_replace( '..', base_path().'/public', $matches[1] );
                    }
                    
                    // $getUserSignature = '<p>&nbsp;</p>'.str_replace('../images', URL::to('/').'/images', $getUserSignature);
                    $getUserSignature = '<br><br>'.str_replace('..', url('/'), $getUserSignature);

                }

                $emailContent = $request->message;
                $htmlEmail    = $emailContent = $emailContent.$getUserSignature;

                $doNotReplyMessage = '<div><b>** Please do not reply on this email. **</b></div><br><br>';
                // $emailContent      = $doNotReplyMessage . $request->emailContent;
                // $emailContent     = $emailContent.$getUserSignature;

                // $t = rtrim(strtr(base64_encode($emailContent), '+/', '-_'), '=');
                // dd($t);
                //attachment
                $subjectCharset = $charset = 'utf-8';

                // $filePath = 'http://phplaravel-370483-1521810.cloudwaysapps.com/images/lorem-signature.png';
                // $filePath = base_path().'/public/images/signatures/lorem-signature.png';

                // if ( !empty($src) )
                // {
                //     $filePath   = $src;
                //     $finfo      = finfo_open(FILEINFO_MIME_TYPE);               // return mime type ala mimetype extension
                //     $mimeType   = finfo_file($finfo, $filePath);
                //     $randString = random_bytes(6);
                //     $randString = bin2hex($randString);
                //     $fileName   = 'image-'.$randString.'.png';
                //     $fileData   = base64_encode(file_get_contents($filePath));
                // }

                $boundary   = uniqid(rand(), true);

                
                $emailSupportAddresses = EmailSupportAddress::active()->first();
                $userName              = $emailSupportAddresses->name;
                $userEmail             = '<'.$emailSupportAddresses->email.'>';
                $userNameEmail         = $userName.' '.$userEmail;

                $subject = $request->subject .' - ' . \Carbon\Carbon::now()->format('Ymds');

                $strRawMessage = "From: $userNameEmail\r\n"; // needs to get from user details
                // $strRawMessage .= "To: Rodney Caisip <rodney@frankiesautoelectrics.com.au>\r\n";
                $strRawMessage .= "To: $request->emailTo\r\n";
                $strRawMessage .= "Bcc: <sales@frankiesautoelectrics.com.au>,<theodore@frankiesautoelectrics.com.au>\r\n"; // request to be added - 03/21/23
                // if( Auth::id() == 1 )
                // {
                //     $strRawMessage .= "Bcc: theodore@frankiesautoelectrics.com.au\r\n";

                //     $strRawMessage .= "Bcc: theodore@frankiesautoelectrics.com.au\r\n";
                //     $strRawMessage .= "Bcc: agent@frankiesautoelectrics.com.au\r\n";


                //     $strRawMessage .= "Bcc: agent@frankiesautoelectrics.com.au\r\n";
                //     $strRawMessage .= "Bcc: agent@frankiesautoelectrics.com.au\r\n";
                //     $strRawMessage .= "Bcc: agent@frankiesautoelectrics.com.au\r\n";
                //     $strRawMessage .= "Bcc: agent@frankiesautoelectrics.com.au\r\n";
                //     $strRawMessage .= "Bcc: agent@frankiesautoelectrics.com.au\r\n";
                    
                // }
                
                Log::info("Sending Compose Message... User: " . $user . ", Subject: " . $request->subject . ", To: " . $request->emailTo . ", Time: " . \Carbon\Carbon::now());

                // $strRawMessage .= "To: rodneydc14 <rodneydc14@gmail.com>\r\n";
                $strRawMessage .= "Subject: $request->subject\r\n";
                // $strRawMessage .= "Message-ID: <CA+fFhZAnN+=H93pPPHMhm1ikBS+jEhfdaux6pU8mOQS_bwpDXA@mail.gmail.com>\r\n";
                $strRawMessage .= "In-Reply-To: $userEmail\r\n";
                // $strRawMessage .= "References: <rodney@frankiesautoelectrics.com.au>\r\n";
                $strRawMessage .= "MIME-Version: 1.0\r\n";

                $strRawMessage .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";
                // $strRawMessage .= "\r\n--{$boundary}\r\n";

                if ( !empty($src) )
                {
                    $strRawMessage .= "\r\n--{$boundary}\r\n";
                    $strRawMessage .= 'Content-Type: '. $mimeType .'; name="'. $fileName .'";' . "\r\n";
                    $strRawMessage .= 'Content-Disposition: attachment; filename="' . $fileName . '"; size=' . filesize($filePath). ';' . "\r\n"; //
                    $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n"; //
                    $strRawMessage .= chunk_split(base64_encode(file_get_contents($filePath)), 76, "\n") . "\r\n"; //
                    $strRawMessage .= '--' . $boundary . "\r\n";
                }

                //get all image src's base64 data
                preg_match_all('/base64,(.*?)"/', $request->message, $imagesSrc);

                if( !empty($imagesSrc) )
                {
                    foreach( $imagesSrc[1] as $key => $_imageData )
                    {
                        $_ctr = $key+1;
                        $filesizeInBytes = (int) (strlen(rtrim($_imageData, '=')) * 3 / 4);
                        $_fileName = 'image' . $_ctr;

                        $strRawMessage .= "\r\n--{$boundary}\r\n";
                        $strRawMessage .= 'Content-Type: image/jpeg; name="'. $_fileName .'";' . "\r\n";
                        $strRawMessage .= 'Content-Disposition: attachment; filename="' . $_fileName . '"; size=' . $filesizeInBytes. ';' . "\r\n"; //
                        $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n"; //
                        $strRawMessage .= 'Content-ID: image/jpeg; name="'. $_fileName .'";' . "\r\n";
                        $strRawMessage .= 'X-Attachment-Id: image/jpeg; name="'. $_fileName .'";' . "\r\n";
                        // $strRawMessage .= chunk_split(base64_encode(file_get_contents($filePath)), 76, "\n") . "\r\n"; //
                        $strRawMessage .= chunk_split($_imageData, 76, "\n") . "\r\n";
                        $strRawMessage .= '--' . $boundary . "\r\n";

                    }
                }

                $_fileIds = [];
                if ( isset($request->file_ids) )
                {
                    $files = $this->getFiles($request->file_ids);

                    foreach($files as $file)
                    {
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

                        array_push($_fileIds, $file->id);

                    }
                }

                $strRawMessage .= "\r\n--{$boundary}\r\n";
                $strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
                // $strRawMessage .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n\r\n";
                $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
                // $strRawMessage .= str_replace('href=', 'href=3D', $emailContent) . "\r\n";
                $strRawMessage .= $emailContent;
                // dd($emailContent);
                //after gmail sent message that will be the time we save the message since we need the unique ids of the message itself from gmail api

                /*//create ticket
                $_now   = \Carbon\Carbon::now();
                $createTicket = Ticket::create([
                    'thread_id'         => 0,
                    'history_id'        => 0,
                    'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                    'origin_id'         => $request->emailTo,
                    'subject'           => trim($subject),
                    'snippet'           => trim($subject),
                    // 'requester'         => $thread['requester'],
                    'requester'         => str_replace(array('\'','"'), '', $emailSupportAddresses->email),
                    'receiver'          => $request->emailTo,
                    // 'thread_started_at' => self::formatThreadDate($thread['date']),
                    'thread_started_at' => $_now,
                    'status_id'         => TicketStatus::STATUS_PENDING,
                    // 'priority_id'       => (int)TicketPriority::PRIORITY_NORMAL, // default for now
                    // 'type_id'           => (int)TicketType::TYPE_QUESTION, // default for now
                ]);

                if ( $createTicket )
                {
                    $storeMessage = Message::create([
                        'ticket_id'     => $createTicket->id,
                        'message_id'    => '',
                        'message'       => rtrim(strtr(base64_encode($htmlEmail), '+/', '-_'), '='),
                        'notes'         => empty($request->notesContent)  ? '' : $request->notesContent,
                        'file_ids'      => empty($_fileIds) ? '' : json_encode($_fileIds),
                        'from'          => $emailSupportAddresses->email,
                        'to'            => $request->emailTo,
                        'internal_date' => $_now,
                    ]);
                }*/


                if ( !Ticket::BACKGROUND_PROCESS_SEND_MESSAGE )
                {
                	logger('User: ' . $user->name . ' is trying to send composed message to: ' . $request->emailTo);
                    $result = GmailApi::sendComposedMessage($strRawMessage, $request->emailTo);
                }
                else
                {
                    //create ticket
                    $_now   = \Carbon\Carbon::now();
                    $createTicket = Ticket::create([
                        'thread_id'         => 0,
                        'history_id'        => 0,
                        'channel_id'        => TicketOrigin::ORIGIN_GMAIL,                // temporarily for GMAIL
                        'origin_id'         => $request->emailTo,
                        'subject'           => trim($subject),
                        'snippet'           => trim($subject),
                        // 'requester'         => $thread['requester'],
                        'requester'         => str_replace(array('\'','"'), '', $emailSupportAddresses->email),
                        'receiver'          => $request->emailTo,
                        // 'thread_started_at' => self::formatThreadDate($thread['date']),
                        'thread_started_at' => $_now,
                        'status_id'         => TicketStatus::STATUS_PENDING,
                        // 'priority_id'       => (int)TicketPriority::PRIORITY_NORMAL, // default for now
                        // 'type_id'           => (int)TicketType::TYPE_QUESTION, // default for now
                    ]);

                    if ( $createTicket )
                    {
                        $storeMessage = Message::create([
                            'ticket_id'     => $createTicket->id,
                            'message_id'    => '',
                            'message'       => rtrim(strtr(base64_encode($htmlEmail), '+/', '-_'), '='),
                            'notes'         => empty($request->notesContent)  ? '' : $request->notesContent,
                            'file_ids'      => empty($_fileIds) ? '' : json_encode($_fileIds),
                            'from'          => $emailSupportAddresses->email,
                            'to'            => $request->emailTo,
                            'internal_date' => $_now,
                        ]);
                    }
                
                    $_userId = Auth::user()->id;
                    $result  = SendMessage::dispatch($strRawMessage, '0', $request->emailTo, true, $_userId)->delay(now()->addSeconds(5))->onQueue('sendmessage');
                }

                /*$result = GmailApi::sendComposedMessage($strRawMessage, $request->emailTo);

                if ( !empty($request->notesContent) )
                {
                    Message::where('message_id', $result['message'])->update(['notes' => $request->notesContent]);
                }

                if ( !empty($result['ticket_id']) ) // ticket id
                {
                    //save tags
                    if ( isset($request->selected_tags) )
                    {

                        $ticketId     = $result['ticket_id'];
                        $selectedTags = $request->selected_tags;
                        $error        = 0;

                        // dump($ticketId, $selectedTags);

                        $ticketsTags = TicketsTags::where('ticket_id', '=', $ticketId)->delete();

                        if ($selectedTags)
                        {

                            foreach ( $selectedTags as $tagId ) {

                                $ticketsTags = TicketsTags::where('ticket_id', '=', $ticketId)->where('tag_id', '=', $tagId)->count();

                                if (!$ticketsTags) {

                                    $ticketsTags             = new TicketsTags;
                                    $ticketsTags->ticket_id  = $ticketId;
                                    $ticketsTags->tag_id     = $tagId;

                                    if ( !$ticketsTags->save() ) {
                                        $error++;
                                    }
                                }

                            }

                            if ( $error ) {
                                return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.']);
                            }
                            else
                            {
                                return response()->json(['success' => true]);
                            }

                        }

                    }

                }*/

            }
            else
            {
                return response()->json(['success' => false, 'errors'=> $validation->errors()->all()]);
            }

        }

        return response()->json(['success'=> true]);

    }

    public function ajaxAuthUserAvailableToChat(Request $request)
    {
        $userOnline = (Auth::user()->is_online == true) ? true : false;

        return response()->json($userOnline);
    }

    public function ajaxGetAgentChatLogs(Request $request)
    {
        //request userId, chatIds
        // dd($request->all());

        $agentReviewsDetails = Array();
        $chatIds             = explode(',', $request->chatIds);

        $chats         = Chat::whereIn('id', $chatIds)->get();
        // $agentChatLogs = AgentChatLog::whereIn('chat_id', $chatIds)->get();

        foreach ( $chats as $key => $chat )
        {

            $agentChatLog = AgentChatLog::where('chat_id', $chat->id)->first();

            $agentReviewsDetails[$key]['chat_id']                      = $chat->id;
            $agentReviewsDetails[$key]['agent_name']                   = User::find($request->userId)->name;
            $agentReviewsDetails[$key]['customer_name']                = Customer::find($chat->customer_id)->name;
            $agentReviewsDetails[$key]['rating']                       = $chat->rating;
            $agentReviewsDetails[$key]['remarks']                      = $chat->remarks;
            $agentReviewsDetails[$key]['chat_duration']                = Chat::chat_duration($agentChatLog->created_at, $agentChatLog->ended_at);
            $agentReviewsDetails[$key]['chat_agent_answered_duration'] = Chat::chat_duration($agentChatLog->created_at, $agentChatLog->user_replied_at);
            $agentReviewsDetails[$key]['created_at']                   = \Carbon\Carbon::parse($agentChatLog->created_at)->format('M d, Y h:ia');
            $agentReviewsDetails[$key]['ended_at']                     = \Carbon\Carbon::parse($agentChatLog->ended_at)->format('M d, Y h:ia');

        }

        return response()->json($agentReviewsDetails);

    }

    public function ajaxUpdateUserChatAvailability(Request $request)
    {

        $user = User::find(Auth::user()->id);

        if ( $request->is_available == 'true' )
        {
            $user->is_online = true;
        }
        else
        {
            $user->is_online = false;
        }

        $user->save();

        return response()->json([ 'success' => true]);

    }

    public function ajaxGetAuthuser(Request $request)
    {

        $user = Auth::user();

        return response()->json([ 'success' => true, 'user' => $user ]);

    }

    public function ajaxGetUnreadMessagesCount(Request $request)
    {
        
        $unreadMessagesCount = $this->getUnreadMessagesCount();

        return response()->json([ 'success' => true, 'unreadMessagesCount' => $unreadMessagesCount ]);

    }

    public function getUnreadMessagesCount()
    {

        return ChatMessage::where('read', false)->count() + $this->getChatMessagesRequestCount();

    }

    public function getChatMessagesRequestCount()
    {
        //message request are existing chats that does not have chat messages yet.
        return Chat::doesntHave('chatMessages')->get()->count();

    }

    public function ajaxSeenMessages(Request $request)
    {

        $chat = Chat::find($request->chatId);

        $chatMessages = $chat->chatMessages()->where('read', false)->update([
            'read' => true
        ]);

        return response()->json([ 'success' => true ]);

    }

    public function seenMessages($chatId)
    {
        //set messages to read = true
        $chatMessages = ChatMessage::where('chat_id', $chatId)->update([
            'read' => true
        ]);

    }

    public function ajaxEndChat(Request $request)
    {
        event(new \App\Events\UserEndedChat($request->chat_id));
    }

    public function updateReceiveUnreadMessageCount()
    {
        event(new \App\Events\ReceiveUnreadMessageCount($this->getUnreadMessagesCount()));
    }

    public function ajaxFilterChats(Request $request)
    {

        $chatIdsHaveAccess = Array();

        $showAllChats = filter_var($request->show_all_chats, FILTER_VALIDATE_BOOLEAN);

        if ( $request->status_id == 'unread' )
        {
            $chats = Chat::whereHas('chatMessages', function($a){
                $a->where('read', false);
            })->get();
        }
        // elseif ( $request->status_id = 'agent_no_response' )
        // {
        //     dd($request->all());
        //     $chats = Chat::where('agent_start_chat', Chat::START_CHAT_AGENT_NO_RESPONSE)->orderBy('updated_at', 'DESC')->get();
        // }
        else
        {

            if ( $showAllChats )
            {
                $chats = Chat::where('status_id', $request->status_id)->orderBy('updated_at', 'DESC')->get();
                /*$chats = Chat::where('status_id', $request->status_id)
                                ->with(['customer' => function($a){

                                    return $a->groupBy('ip_address');
                                    $a->groupBy('name');
                                    $a->groupBy('email');
                                }])
                                ->get();

                dump($chats);*/
            }
            else
            {
                $chats = Chat::whereHas('chatLog', function($a){

                    $a->where('user_id', Auth::user()->id);
                })
                // ->where('status_id', $request->status_id)
                ->orderBy('status_id')
                ->orderBy('updated_at', 'DESC')
                ->get();
            }


            //get the ids of chat that u dont have msg and not empty messages which will be use to be compared in chat conversations
            if ( $request->status_id != TicketStatus::STATUS_UNASSIGNED )
            {

                $_chats = Chat::whereHas('chatLog', function($a){

                    $a->where('user_id', Auth::user()->id);
                })
                ->where('status_id', $request->status_id)
                ->get('id')
                ->toArray();
    
                foreach( array_values($_chats) as $val )
                {
                    array_push($chatIdsHaveAccess, $val['id']);
                }
                
            }

        }

        //used for the behavior of the active conversation for realtime chats.
        $setActiveConversation = false;
        //to identify which specific chat block should be active
        $activeChatId = $request->activeChatId;

        return view('chat.chat-conversations-data', compact(['chats', 'setActiveConversation', 'activeChatId', 'chatIdsHaveAccess']))->render();
    }

    public function ajaxGetChatMessage(Request $request)
    {
        $chat         = Chat::find($request->chat_id);
        $chatMessages = $chat->chatMessages;

        return response()->json([ 'chat' => $chat, 'chatMessages' => $chatMessages]);
    }

    public function ajaxSendChatMessage(Request $request)
    {

        $file  = null;
        $image = '';

        if ( $request->has('upload') )
        {

            $imageUrl = $request->file('upload');

            //get image contents
            $randAlphanumeric = random_bytes(8);
            $randAlphanumeric = bin2hex($randAlphanumeric);
            //get image file ext.
            $_imageUrl = explode('?', $imageUrl);
            $ext = '.'.$imageUrl->getClientOriginalExtension();
            //set new image filename and store to storage images
            $name = 'chat'.$randAlphanumeric;
            $fileName = $name.$ext;
            \Storage::put('public/images/'.$fileName, $imageUrl->get());
            
            $file = \App\File::create([
                'name'      => $name,
                'extension' => $ext,
                'path'      => storage_path('public/images/'.$fileName),
                // 'path'      => $image,
            ]);

            // $image = '<img src="data:image/jpeg;charset=utf-8;base64,'.base64_encode(\Storage::get('images/'.$fileName)).'" />';

            //get image src to be sent in chat(copy paste)
            $path       = 'public/images/'.$fileName;
            $full_path  = \Storage::path($path);
            $base64     = base64_encode(\Storage::get($path));
            $image_data = 'data:'.mime_content_type($full_path).';base64,' .$base64;
            $image      = env('APP_URL', false).\Storage::url($path);
            
        }

        $agentChatLog = AgentChatLog::where('chat_id', $request->chatId)
                            ->update([
                                'user_id'         => Auth::user()->id,
                                'user_replied_at' => \Carbon\Carbon::now()
                            ]);

        
        $chat = Chat::find($request->chatId);

        if ( $chat->agent_start_chat == false )
        {
            $chat->agent_start_chat = true;
            $chat->save();
        }

        // $chat = Chat::where('id', $request->chatId)->update([
        //     'agent_start_chat' => true
        // ]);
        
        //on send, send event, update ticket status.
        event(new \App\Events\UserMessaged($request->chatId, $request->message, ($file ? $file->id : null), $image));
        
        $chats = Chat::where('id', $request->chatId)->orderBy('chat_started_at', 'DESC')->get();

        return view('chat.chat-messages', compact('chats'))->render();
        
    }

    public function ajaxStartChat(Request $request)
    {
        
        $user = Auth::user();

        $chat = Chat::where('id', $request->chatId)->update([
                    'agent_start_chat' => Chat::START_CHAT_AGENT_RESPONDED,
                    'status_id' => Chat::STATUS_PENDING,
                ]);

        $agentChatLog = AgentChatLog::updateOrCreate(
            [
                'chat_id' => $request->chatId,
                'user_id' => NULL,
            ],
            [
                'user_id'         => $user->id,
                'user_replied_at' => \Carbon\Carbon::now()
            ]
        );

        //on send, send event, update ticket status.
        event(new \App\Events\UserMessaged($request->chatId, base64_encode('')));

        event(new \App\Events\AgentStartChat($request->chatId, $user->id));
        
    }

    public function ajaxGetChatConversations(Request $request)
    {
        // dd($request->all());
        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $user             = Auth::user();
        $agents           = User::allAgents()->get();

        //used for the behavior of the active conversation for realtime chats.
        $setActiveConversation = true;

        if( isset($request->setActiveConversation) && $request->setActiveConversation == 'false' )
        {
            $setActiveConversation = false;
        }

        //to identify which specific chat block should be active
        $activeChatId = 0;
        $activeChatId = $request->activeChatId;

        //special case for filter in unread messages then it was opened. need proper behavior for setting up active conversation on re-render conversation
        if ( $request->status_id == 'unread' )
        {
            $chats = Chat::whereHas('chatMessages', function($a){
                $a->where('read', false);
            })->get();

            $activeChatId = 0;
            $setActiveConversation = false;

        }
        else
        {
            //get chat by activeChatId and the status. to be used on query for chat all
            $chat  = Chat::find($request->activeChatId);

            $chatStatusId = ( $chat ) ? $chat->status_id : $request->status_id;

            $showAllChats = filter_var($request->show_all_chats, FILTER_VALIDATE_BOOLEAN);
            
            if ( $showAllChats )
            {
                $chats = Chat::where('status_id', $chatStatusId)->orderBy('updated_at', 'DESC')->get();

                $_chats = Chat::whereHas('chatLog', function($a){
                    $a->where('user_id', Auth::user()->id);
                })
                ->where('status_id', $chatStatusId)
                ->get('id')
                ->toArray();
            }
            else
            {
                $chats = Chat::whereHas('chatLog', function($a){

                    $a->where('user_id', Auth::user()->id);
                })
                // ->where('status_id', $request->status_id)
                ->orderBy('status_id')
                ->orderBy('updated_at', 'DESC')
                ->get();

                $_chats = Chat::whereHas('chatLog', function($a){
                    $a->where('user_id', Auth::user()->id);
                })
                ->get('id')
                ->toArray();
            }

            //get the ids of chat that u dont have msg and not empty messages which will be use to be compared in chat conversations
            // $_chats = Chat::whereHas('chatMessages', function($a){
            //     $a->where('from', 'agent');
            //     $a->where('user_id', Auth::user()->id);
            // })
            // ->where('status_id', $chatStatusId)
            // ->get('id')
            // ->toArray();
            
            /*$_chats = Chat::whereHas('chatLog', function($a){
                $a->where('user_id', Auth::user()->id);
            })
            ->where('status_id', $chatStatusId)
            ->get('id')
            ->toArray();*/

            // dump($_chats);

            $chatIdsHaveAccess = Array();
            foreach( array_values($_chats) as $val )
            {
                array_push($chatIdsHaveAccess, $val['id']);
            }
            // dump($chatIdsHaveAccess);

        }
        
        $this->updateReceiveUnreadMessageCount();

        return view('chat.chat-conversations-data', compact(['chats', 'setActiveConversation', 'activeChatId', 'chatIdsHaveAccess']))->render();

    }

    public function ajaxGetChatMessages(Request $request)
    {

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $user             = Auth::user();
        $agents           = User::allAgents()->get();
        
        $chats = Chat::where('id', $request->chatId)->orderBy('chat_started_at', 'DESC')->get();

        //on view conversation, set messages to read = true
        $this->seenMessages($request->chatId);

        $this->updateReceiveUnreadMessageCount();

        return view('chat.chat-messages', compact('chats'))->render();

    }

    public function ajaxSearchChats(Request $request)
    {

        if ( $request->ajax() ) {

            $chats = Chat::whereHas('customer', function($a) use($request) {

                $a->where('name','like','%'.$request->searched_value.'%');

                $a->orWhere('email','like','%'.$request->searched_value.'%');

                // $a->orWhere('ip_address','like','%'.$request->searched_value.'%');

            })
            ->where('status_id', $request->status_id)
            ->orderBy('updated_at', 'DESC')
            ->get();

            //used for the behavior of the active conversation for realtime chats.
            $setActiveConversation = false;
            //to identify which specific chat block should be active
            $activeChatId = 0;

            
            return view('chat.chat-conversations-data', compact(['chats', 'setActiveConversation', 'activeChatId']))->render();

        }

    }

    public function ajaxGetFacebookPageInfo(Request $request)
    {

        if ( $request->ajax() )
        {

            $ticket = Ticket::find($request->ticketId);
            
            return response()->json([ 'facebookPageName' => $ticket->facebookPage->name, 'facebookPageDisplayPhoto' => $ticket->facebookPage->displayPhoto() ]);

        }

    }

    public function ajaxSearchFacebookConversations(Request $request)
    {

        if ( $request->ajax() ) {

            $tickets = Ticket::where(function($q) use($request) {

                $q->where('origin_id', TicketOrigin::ORIGIN_FACEBOOK);

                $q->where(function($a) use($request) {

                    $a->where('subject', 'like', '%'.$request->searched_value.'%');

                    $a->orWhere('snippet', 'like', '%'.$request->searched_value.'%');

                    $a->orWhereHas('facebookMessages', function($b) use($request) {

                        $b->where('requester', 'like', '%'.$request->searched_value.'%');

                    });

                });

            })
            ->orderBy('updated_at', 'DESC')
            ->get();


            $ticketPriorities = TicketPriority::all();
            $ticketTypes      = TicketType::all();
            $ticketStatus     = TicketStatus::all();
            $customVariables  = CustomVariable::all();
            $user             = Auth::user();
            $agents           = User::allAgents()->get();

            // return view('chat.facebook', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'user', 'agents']));
            return view('chat.facebook-conversations-data', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'user', 'agents']));

        }

    }

    public function ajaxUpdateChatStatus(Request $request)
    {

        if ( $request->ajax() ) {
            
            DB::beginTransaction();

            try {

                $chat = Chat::find($request->chat_id);

                $chat->status_id = $request->chat_status;

                if( !$chat->save() )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }


                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['error'=>$e->getMessage()]);

            }

            return response()->json(['success' => 'Status has been updated.']);

        }

    }

    public function ajaxUpdateFacebookConversationTicketStatus(Request $request)
    {

        if ( $request->ajax() ) {
            
            DB::beginTransaction();

            try {

                $ticket = Ticket::find($request->ticket_id);

                $ticket->status_id   = $request->ticket_status;
                $ticket->type_id     = $request->ticket_type;

                if( !$ticket->save() )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }


                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['error'=>$e->getMessage()]);

            }

            return response()->json(['success' => 'Status has been updated.']);

        }

    }

    public function ajaxSendFacebookMessage(Request $request)
    {
        // $request->facebook['ticketId'], threadId, message

        // $ticket = Ticket::where('thread_id', $request->facebook['threadId'])->get();

        // dump($request->all());

        Facebook::facebookInstance();

        Facebook::sendFacebookMessage( $request->facebook['ticketId'], $request->facebook['message'] );

        $tickets = Ticket::where('id', $request->facebook['ticketId'])->where('origin_id', TicketOrigin::ORIGIN_FACEBOOK)->orderBy('created_at', 'DESC')->paginate(10);

        return view('chat.facebook-messages', compact('tickets'))->render();

        // return response()->json(['success' => true, 'message' => 'Message has been sent.']);

    }

    public function ajaxGetFacebookMessages(Request $request)
    {

        $ticketPriorities = TicketPriority::all();
        $ticketTypes      = TicketType::all();
        $ticketStatus     = TicketStatus::all();
        $customVariables  = CustomVariable::all();
        $emailTemplates   = EmailTemplate::all();
        $user             = Auth::user();
        $agents           = User::allAgents()->get();

        $tickets = Ticket::where('id', $request->ticketId)->where('origin_id', TicketOrigin::ORIGIN_FACEBOOK)->orderBy('created_at', 'DESC')->paginate(10);

        return view('chat.facebook-messages', compact('tickets'))->render();
        
    }

    public function ajaxSyncFacebookConversation(Request $request)
    {

        Log::info('User '.Auth::user()->name.' Syncing Facebook Conversations');

        $start = date('h:i:s');
        
        Facebook::facebookInstance();
        Facebook::syncConversations();
        
        $end = date('h:i:s');

        Log::info('Synced Facebook Conversations Start - End Time: '.$start.' - '.$end);

        // Facebook::facebookInstance();
        // Facebook::syncConversation($request->facebook['ticketId']);
        // Facebook::syncConversation();
        
        // $tickets = Ticket::where('id', $request->facebook['ticketId'])->where('origin_id', TicketOrigin::ORIGIN_FACEBOOK)->orderBy('created_at', 'DESC')->paginate(10);
        $tickets = Ticket::where('origin_id', TicketOrigin::ORIGIN_FACEBOOK)->orderBy('created_at', 'DESC')->paginate(10);
        // $tickets = Ticket::where('origin_id', TicketOrigin::ORIGIN_FACEBOOK)->orderBy('created_at', 'DESC')->paginate(10);

        return view('chat.facebook-messages', compact('tickets'))->render();
        
    }

    public function ajaxUpdateUserInfo(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'avatar' => 'image|mimes:jpeg,png,jpg|max:2048',
            'email' => 'required|email',
            'name' => 'required|regex:/^[a-zA-Z .]+$/',
        ]);

        if($validation->passes())
        {
            
            $id   = Auth::user()->id;
            $user = User::find( $id );

            if ( !empty($request->name) )
            {
                $user->name = $request->name;
            }

            if ( !empty($request->email) )
            {
                $user->email = $request->email;
            }

            if ( isset($request->avatar) )
            {
                $avatar = $request->file('avatar');
    
                $randAlphanumeric = random_bytes(8);
                $randAlphanumeric = bin2hex($randAlphanumeric);
    
                $avatar_name  = $randAlphanumeric.'.' . $avatar->getClientOriginalExtension();
                $user->avatar = $avatar_name;
                $avatar->storeAs('images', $avatar_name);
                $avatar->storeAs('public/images', $avatar_name);
            }

            if ( !$user->save() )
            {
                return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.']);
            }

        }
        else
        {
            return response()->json([
                'success' => false,
                'message' => $validation->errors()->all(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'User information has been updated.']);

    }

    public function ajaxUpdatePassword(Request $request)
    {

        $user   = Auth::user();
        $errors = [];
        
        $validation = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string'],
            'new_confirm_password' => ['required', 'string', 'same:new_password'],
        ]);


        if (!Hash::check($request->current_password, $user->password)) {
           array_push($errors, 'incorrect current password.');
        }
        /*dump($request->all());
        dump($validation->errors()->all());
        dump($errors);
        dump($validation->passes());

        $user           = Auth::user();
        dump($user->password);
        $user->password = Hash::make($request->current_password);
        dd($user->password);*/


        if($validation->passes() && empty($errors))
        {
            
            $user           = Auth::user();
            $user->password = Hash::make($request->new_password);

            if ( !$user->save() )
            {
                return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.']);
            }

        }
        else
        {

            $validationErrors = $validation->errors()->all(); // can also merge with $errors

            return response()->json([
                'success' => false,
                'message' => !empty( $validationErrors ) ? $validationErrors : $errors,
            ]);

        }

        return response()->json(['success' => true, 'message' => 'User password has been changed.']);

    }

    public function ajaxRefreshSignatureListing(Request $request)
    {
        
        $user = Auth::user();
        
        $signatures  = Signature::where('user_id', $user->id)->orderBy('created_at', 'DESC')->paginate(10);

        return view('users.signatures_table_data', compact('signatures'))->render();

    }

    public function ajaxRefreshCustomPagesListing(Request $request)
    {

        $user = Auth::user();
        
        $customPages = $user->customPages()->orderBy('created_at', 'DESC')->paginate(10);

        return view('users.custom_pages_table_data', compact('customPages'))->render();

    }

    public function ajaxDeleteCustomPage(Request $request)
    {

        if ( $request->ajax() )
        {

            $userCustomPage = UserCustomPage::find( $request->customPageId );

            $pageName = $userCustomPage->name;

            $userCustomPage->pageConditions()->delete();
            
            $userCustomPage->delete();
            
            return response()->json(['success' => true, 'message' => 'Custom Page '.$pageName.' has been deleted.']);

        }

    }

    public function ajaxUpdatePageConditions(Request $request)
    {
        // with data condition id = existing so update it
        // without  or null data condition id = new
        // existing data conditions that were not in data condition id list post = softDelete

        if ( $request->ajax() )
        {

            $user                  = Auth::user();
            $conditionsToUpdateIds = Array();
            
            DB::beginTransaction();
            
            try {

                //delete
                foreach ( $request->pageConditionsDataToUpdate as $val )
                {
                    
                    if ( $val['conditionId'] != null )
                    {
                        array_push($conditionsToUpdateIds, $val['conditionId']);
                    }
        
                }

                $deletePageConditions = CustomPageCondition::where('custom_page_id', $request->customPageId)->whereNotIn('id', $conditionsToUpdateIds)->delete();


                foreach ( $request->pageConditionsDataToUpdate as $val )
                {
                    
                    //update
                    if ( $val['conditionId'] != null && in_array($val['conditionId'], $conditionsToUpdateIds) )
                    {
                        $updatePageConditions = CustomPageCondition::where('custom_page_id', $request->customPageId)->where('id', $val['conditionId'])->update([
                            'filter'    => $val['selectedColumn'],
                            'filter_id' => (int)$val['selectedValue'],
                            'operator'  => $val['pageConditionOperator'],
                        ]);
                        
                    }

                    //create

                    if ( $val['conditionId'] == null )
                    {

                        $createPageConditions = CustomPageCondition::create([
                            'user_id'        => $user->id,
                            'custom_page_id' => $request->customPageId,
                            'filter'         => $val['selectedColumn'],
                            'filter_id'      => (int)$val['selectedValue'],
                            'operator'       => $val['pageConditionOperator'],
                        ]);

                    }
        
                }

                $userCustomPage       = UserCustomPage::find($request->customPageId);
                $userCustomPage->name = $request->pageName;
                $userCustomPage->slug = $request->pageSlug;

                if ( !$userCustomPage->save() )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }


                DB::commit();

            }
            catch (exception $e)
            {
                DB::rollback();
            }

            return response()->json(['success' => true, 'message' => 'Custom Page '.$request->pageName.' has been updated.']);
        
        }

    }

    public function ajaxGetCustomPageData(Request $request)
    {

        if ( $request->ajax() )
        {
            $userCustomPage = UserCustomPage::find( $request->customPageId );

            return response()->json(['userCustomPage' => $userCustomPage, 'customPageConditions' => $userCustomPage->pageConditions]);
        }

    }

    public function ajaxPostPageConditions(Request $request)
    {

        if ( $request->ajax() )
        {

            DB::beginTransaction();

            $user = Auth::user();

            try {

                $userCustomPage = UserCustomPage::create([
                    'user_id' => $user->id,
                    'name' => $request->pageName,
                    'slug' => $request->pageSlug,
                ]);

                if( $userCustomPage->exists )
                {
                    
                    foreach ( $request->pageConditionsData as $key => $val )
                    {
                        CustomPageCondition::create([
                            'user_id'        => $user->id,
                            'custom_page_id' => $userCustomPage->id,
                            'filter'         => $val['selectedColumn'],
                            'filter_id'      => (int)$val['selectedValue'],
                            'operator'       => $val['pageConditionOperator'],
                        ]);
                    }

                }
                else
                {
                    throw new Exception("Something went wrong. Please try again.");
                }

                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['success' => false,'message'=>$e->getMessage()]);

            }
            
        }

        return response()->json(['success' => true, 'message' => 'Custom Page '.$request->pageName.' has been created.']);

    }

    public function ajaxGetColumnDetails(Request $request)
    {

        $ticketTypes      = TicketType::active()->get();
        $ticketStatus     = TicketStatus::active()->get();
        $ticketPriorities = TicketPriority::active()->get();
        $ticketOrigin     = TicketOrigin::active()->get();

        $ticketColumns = Array(
            'Ticket Types'      => 'type',
            'Ticket Status'     => 'status',
            'Ticket Priorities' => 'priority',
            'Ticket Origin'     => 'origin',
        );

        return response()->json([
            'success'          => true,
            'ticketTypes'      => $ticketTypes,
            'ticketStatus'     => $ticketStatus,
            'ticketPriorities' => $ticketPriorities,
            'ticketOrigin'     => $ticketOrigin,
            'ticketColumns'    => $ticketColumns,
        ]);

    }

    public function ajaxStoreSessionEmailSupportId(Request $request)
    {

        $request->session()->put('email_support_id_to_auth', $request->id);

    }

    public function ajaxRefreshEmailSupportListing(Request $request)
    {

        $emailSupportAddresses = EmailSupportAddress::orderBy('created_at', 'DESC')->paginate(10);

        // return view('ticketing.index');
        return view('channels.email_support_table_data', compact('emailSupportAddresses'))->render();

    }

    public function ajaxDeleteEmailSupport(Request $request)
    {

        if ( $request->ajax() )
        {

            DB::beginTransaction();

            try {

                $emailSupportAddress = EmailSupportAddress::find($request->id);
                $email               = $emailSupportAddress->email;
                $emailSupportAddress = $emailSupportAddress->delete();

                if( !$emailSupportAddress )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }

                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['success' => false,'message'=>$e->getMessage()]);

            }
            
        }

        return response()->json(['success' => true, 'message' => 'Support address '.$email.' has been deleted.']);

    }

    public function ajaxAddEmailSupport(Request $request)
    {

        if ( $request->ajax() )
        {
            // dd($request->all());

            $validation = Validator::make($request->all(), [
                // 'credentials_json' => 'required|file|mimetypes:application/json,text/plain|max:500',
                'email' => 'required|email',
                'name' => 'required|regex:/^[a-zA-Z ]+$/',
               ]);

            if($validation->passes())
            {

                $emailSupportAddress = EmailSupportAddress::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'status' => EmailSupportAddress::STATUS_INACTIVE,
                ]);

                // $jsonfile = $request->file('credentials_json');
                // $new_name = 'credentials'.$emailSupportAddress->id.'.' . $jsonfile->getClientOriginalExtension();
                // // $jsonfile->move(public_path(), $new_name);
                // $jsonfile->storeAs('credentials', $new_name);
    
                return response()->json(['success' => true, 'message' => 'Support Address '.$request->email.' has been created.']);

            }
            else
            {
                return response()->json([
                    'success' => false,
                    'message' => $validation->errors()->all(),
                ]);
            }

        }

    }

    public function ajaxGetCustomVariables(Request $request)
    {

        $customVariables  = CustomVariable::all();

        $variables = Array();

        $ctr = 0;
        foreach ( $customVariables as $customVariable )
        {
            $variables[$ctr]['type']  = 'choiceitem';
            $variables[$ctr]['text']  = '{{'.$customVariable->name.'}}';
            $variables[$ctr]['value'] = '{{'.$customVariable->name.'}}';

            $ctr++;
        }

        return response()->json(['customVariables' => $variables]);
    }
    public function ajaxAssignTicketTo(Request $request)
    {

        if ( $request->ajax() )
        {
            // dd($request->all());
            DB::beginTransaction();

            try {

                $user = User::find($request->user_id);

                // $assignedTicket = AssignedTicket::where(['user_id' => $request->user_id, 'ticket_id' => $request->ticket_id]);
                $assignedTicket = AssignedTicket::where(['ticket_id' => $request->ticket_id]);
                
                if (!$assignedTicket->count())
                {
                    AssignedTicket::create(['user_id' => $request->user_id, 'ticket_id' => $request->ticket_id]);

                    $message = 'Ticket has been assigned to '.$user->name.'.';
                }
                else
                {
                    // $reAssignTicket = AssignedTicket::where(['ticket_id' => $request->ticket_id])->update(['user_id' => $request->user_id]);
                    $reAssignTicket = AssignedTicket::where(['ticket_id' => $request->ticket_id])->first()->update(['user_id' => $request->user_id]);
                    // $reAssignTicket = AssignedTicket::where(['ticket_id' => $request->ticket_id])->first();
                    // $reAssignTicket->user_id = $request->user_id;
                    // $reAssignTicket->save();
                    
                    $message = 'Ticket has been re-assigned to '.$user->name.'.';
                }

                $ticket = Ticket::find($request->ticket_id);

                $ticket->status_id = Ticket::STATUS_PENDING; // default to new after ticket has been assigned

                if ( !$ticket->save() )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }

                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['success' => false,'message'=>$e->getMessage()]);

            }
            
        }

        return response()->json(['success' => true, 'message' => $message]);

    }

    public function ajaxDeleteUser(Request $request)
    {

        if ( $request->ajax() )
        {

            DB::beginTransaction();

            try {

                $user       = User::find($request->user_id);
                $userName   = $user->name;
                $deleteUser = $user->delete();

                if( !$deleteUser )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }
                else
                {
                    if ( $user->userSchedule )
                    {
                        $deleteUserSchedule = $user->userSchedule->delete();

                        // if( !$deleteUserSchedule )
                        // {
                        //     throw new Exception("Something went wrong. Please try again.");
                        // }
                
                    }
                }

                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['success' => false,'message'=>$e->getMessage()]);

            }
            
        }

        return response()->json(['success' => true, 'message' => ''.$userName.' has been deleted.']);

    }

    public function ajaxRefreshUsertable(Request $request) {

        $users = User::orderBy('created_at', 'DESC')->paginate(10);

        return view('users.users_table_data', compact('users'))->render();

    }

    public function ajaxGetUserDetails(Request $request) {

        $user = User::find($request->user_id);

        return response()->json(['success'=> true, 'user' => $user]);

    }

    public function ajaxUpdateUser(Request $request) {

        $user = User::find($request->user_id);

        DB::beginTransaction();
        
        try {

            $messages = array(
                'email.required' => 'The email field is required.',
                'name.required'  => 'The name field is required.',
                // 'password.required' => 'Password is required!'
            );

            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email,'.$request->user_id,
                'name'  => 'required|string|max:50',
            ], $messages);

            if (false)
            {
                throw new Exception($validator->errors()->first());
            }
            else
            {
                // $user             = User::find($request->user_id);
                // $user->name       = $request->name;
                // $user->email      = $request->email;
                // $user->updated_at = \Carbon\Carbon::now();
                $user = User::find($request->user_id)->update(['name' => $request->name, 'email' => $request->email]);

                // $userRoleId = $user->roles->first()->pivot->role_id;
                // $user->roles->first()->pivot->role_id = $_POST['role_id'];

                // if( $userRoleId != $_POST['role_id'] )
                // {
                //     $this->logUserRoleChanges($_POST['user_id'], $_POST['role_id'], $userRoleId);
                // }

                // if( !$user->save() )
                // {
                //     throw new Exception("Something went wrong. Please try again.");
                // }
                // else
                // {
                //     if( !$user->roles->first()->pivot->save() )
                //     {
                //         throw new Exception("Something went wrong. Please try again.");
                //     }
                // }

            }

            DB::commit();
        }
        catch (Exception $e) {

            DB::rollback();
            
            return response()->json(['success' => false, 'message'=>$e->getMessage()]);

        }

        return response()->json(['success'=>true, 'message' => 'User '.$request->name.' has been updated.']);
    }

    public function ajaxRegister(Request $request) {

        $this->validator($request->all())->validate();

        DB::beginTransaction();

        try {

            $user = $this->create($request->all());

            DB::commit();
        }
        catch (exception $e)
        {
            DB::rollback();
            
            
            $users = User::orderBy('created_at', 'DESC')->paginate(10);

            return response()->json(['success' => false, 'message' => $e->getMessage()]);
            
            // return view('users.users_table_data', ['success' => false, 'message' => $e->getMessage(), 'users' => $users])->render();
        }
        
        return response()->json(['success' => true, 'message' => 'User successfully created.']);
        
        // $users = User::orderBy('created_at', 'DESC')->paginate(10);

        // return view('users.users_table_data', ['success' => true, 'message' => 'User successfully created.', 'users' => $users])->render();

    }

    public function ajaxAssignTicket(Request $request)
    {

        if ( $request->ajax() )
        {
            $user = Auth::user();
            // $assignedTicket = AssignedTicket::where(['user_id' => $user->id, 'ticket_id' => $request->ticket_id]);
            $assignedTicket = AssignedTicket::where(['ticket_id' => $request->ticket_id]);
            
            if (!$assignedTicket->count())
            {
                $_assignTicket = AssignedTicket::create(['user_id' => $user->id, 'ticket_id' => $request->ticket_id]);
                // $_assignTicket->enableLogging();

                $this->setTicketPending($request->ticket_id);

                $message = 'Ticket has been assigned to you.';
            }
            else
            {
                // $assignTicketTo = AssignedTicket::where(['ticket_id' => $request->ticket_id])->update(['user_id' => $user->id]);
                $assignTicketTo = AssignedTicket::where(['ticket_id' => $request->ticket_id])->first()->update(['user_id' => $user->id]);
                // $assignTicketTo->enableLogging();
                $message = 'Ticket has been assigned to you.';
                // $message = 'Someone is already assigned to the ticket.';

                return response()->json(['success' => true, 'message' => $message]);
                // return response()->json(['success' => false, 'message' => $message]);
            }
            
        }

        return response()->json(['success' => true, 'message' => $message]);

    }

    public function assignTicket($ticketId, $userId)
    {

        $assignedTicket = AssignedTicket::where(['ticket_id' => $ticketId]);
        
        if ( !$assignedTicket->count() )
        {

            $_assignTicket = AssignedTicket::create(['user_id' => $userId, 'ticket_id' => $ticketId]);

        }
        else
        {

            $assignTicketTo = AssignedTicket::where(['ticket_id' => $ticketId])->update(['user_id' => $userId]);

        }

    }

    public function setTicketPending($ticket_id)
    {
        $ticket            = Ticket::find($ticket_id);
        $ticket->status_id = TicketStatus::STATUS_PENDING;

        $ticket->save();
    }

    public function ajaxGetTemplateData(Request $request)
    {

        if ( $request->ajax() )
        {

            $emailTemplate          = EmailTemplate::find($request->template_id);
            $emailTemplate->content = base64_decode($emailTemplate->content);
            
        }

        return response()->json(['success' => true, 'emailTemplate' => $emailTemplate]);

    }

    public function ajaxDeleteEmailTemplate(Request $request)
    {

        if ( $request->ajax() )
        {

            DB::beginTransaction();

            try {

                $deleteEmailTemplate = EmailTemplate::find($request->template_id);
                $templateName        = $deleteEmailTemplate->name;
                $deleteEmailTemplate = $deleteEmailTemplate->delete();

                if( !$deleteEmailTemplate )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }

                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['success' => false,'message'=>$e->getMessage()]);

            }
            
        }

        return response()->json(['success' => true, 'message' => ''.$templateName.' has been deleted.']);

    }

    public function ajaxSubmitUpdateEmailTemplate(Request $request)
    {

        if ( $request->ajax() )
        {

            $emailTemplate = EmailTemplate::find($request->template_id);

            $emailTemplate->name = $request->template_name;
            $emailTemplate->content = base64_encode($request->template_content);

            if (!$emailTemplate->save())
            {
                return response()->json(['success' => false]);
            }
            
        }

        return response()->json(['success' => true, 'message' => ''.$request->template_name.' has been updated.', 'emailTemplate' => $emailTemplate]);

    }

    public function ajaxGetEmailTemplate(Request $request)
    {

        if ( $request->ajax() )
        {

            $emailTemplate = EmailTemplate::find($request->template_id);

            $emailTemplate->content = base64_decode($emailTemplate->content);
            
        }

        return response()->json(['success' => true, 'emailTemplate' => $emailTemplate]);

    }

    public function ajaxCreateEmailTemplate(Request $request)
    {

        if ( $request->ajax() )
        {

            $emailTemplate = EmailTemplate::create([
                'name' => $request->template_name,
                'content' => base64_encode($request->template_content),
            ]);
            
        }

        // $emailTemplates  = EmailTemplate::orderBy('created_at', 'DESC')->paginate(10);
        // $customVariables = CustomVariable::all();

        // return view('emailTemplates.index', compact(['emailTemplates','customVariables','success','message']));

        return response()->json(['success' => true, 'message' => ''.$request->template_name.' has been created.']);

    }

    public function ajaxFilterTicket(Request $request)
    {

        if ( $request->ajax() )
        {
            // dump($request->all());
            $user        = Auth::user();
            $where       = Array();

            if ( !empty($request->ticket_status) )
            {
                array_push($where, Array('status_id',$request->ticket_status));
            }

            if ( !empty($request->ticket_type) )
            {
                array_push($where, Array('type_id',$request->ticket_type));
            }

            if ( !empty($request->ticket_priority) )
            {
                array_push($where, Array('priority_id',$request->ticket_priority));
            }
            
            if ( $request->page == '/tickets/needs-urgent-attention' )
            {

                //use to identify if any admin/manager/developer, show all current ticket count depending on view
                // else show only the auth user tickets data
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()->where($where)
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                ->where($where)
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
                }
            }
            elseif ( $request->page == '/tickets/solved' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()->where('status_id', TicketStatus::STATUS_SOLVED)
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                            ->where('status_id', TicketStatus::STATUS_SOLVED)
                                            ->orderBy('thread_started_at', 'DESC')
                                            ->paginate(20);
                }
            }
            elseif ( $request->page == '/tickets/closed' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()->where('status_id', TicketStatus::STATUS_CLOSED)
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                            ->where('status_id', TicketStatus::STATUS_CLOSED)
                                            ->orderBy('thread_started_at', 'DESC')
                                            ->paginate(20);
                }
            }
            elseif ( $request->page == '/tickets/over-4-hours' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()->where($where)
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                ->where($where)
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 4' )
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
                }
            }
            elseif ( $request->page == '/tickets/under-4-hours' )
            {
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()->where($where)
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = Auth::user()->tickets()->excludeFacebook()
                                ->where($where)
                                ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) < 4' )
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
                }
            }
            else if ( isset($request->view_my_tickets) && $request->view_my_tickets == 'true' )
            {
                // dump(11);
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()->where($where)
                                        ->whereIn('status_id', [TicketStatus::STATUS_PENDING])
                                        ->whereRaw( 'HOUR(TIMEDIFF(updated_at, thread_started_at)) >= 24' )
                                        ->orderBy('thread_started_at', 'DESC')
                                        ->paginate(20);
                }
                else
                {
                    $tickets = $user->tickets()->excludeFacebook()->where($where)->orderBy('thread_started_at', 'DESC')->paginate(20);
                }

            }
            else if ( isset($request->view_unassigned_tickets) && $request->view_unassigned_tickets == true )
            {
                // dump(22);
                $tickets = Ticket::excludeFacebook()->where('status_id', TicketStatus::STATUS_UNASSIGNED)->orderBy('thread_started_at', 'DESC')->paginate(20);

            }
            elseif ( !empty($request->ticket_status) || !empty($request->ticket_type) || !empty($request->ticket_priority) )
            {
                // dump(33);
                if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
                {
                    $tickets = Ticket::excludeFacebook()->where($where)
                                    ->orderBy('thread_started_at', 'DESC')
                                    ->paginate(20);
                }
                else
                {
                    $tickets = $user->tickets()->excludeFacebook()
                                    ->where($where)
                                    ->orderBy('thread_started_at', 'DESC')
                                    ->paginate(20);
                }

            }
            else
            {
                // dump(44);
                $tickets = Ticket::excludeFacebook()->orderBy('thread_started_at', 'DESC')->paginate(20);
            }


            //this block of code should be optimized and not be called everytime tickets is being fetched. it should only be called when filter ticket is unassigned
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

        // $tickets = Ticket::orderBy('created_at', 'DESC')->paginate(10);

        return view('ticketing.ticketing_table_data', compact('tickets','user'))->render();

    }

    public function ajaxRefreshTicketListing(Request $request)
    {

        $user = Auth::user();
        $page = $request->page;

        $emailSupportAddress              = EmailSupportAddress::active()->first()->email;
        $rolesAdminManagerDeveloperExists = $user->rolesByIdExists([\App\Role::ADMIN, \App\Role::MANAGER, \App\Role::DEVELOPER]);

        if ( strpos($page, '/tickets/tag/') !== false )
        {

            $slug = explode('/', $page);
            $slug = end($slug);

            $myAgentTickets = false;

            $ticketPriorities = TicketPriority::all();
            $ticketTypes      = TicketType::all();
            $ticketStatus     = TicketStatus::all();
            $customVariables  = CustomVariable::all();
            $emailTemplates   = EmailTemplate::all();
            $agents           = User::allAgents()->get();

            if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
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

            $tags       = Tag::all();
            $categories = Category::all();

            return view('ticketing.ticketing_table_data', compact([
                                    'tickets',
                                    'ticketPriorities',
                                    'ticketTypes',
                                    'ticketStatus',
                                    'customVariables',
                                    'emailTemplates',
                                    'user',
                                    'agents',
                                    'myAgentTickets',
                                    'tags',
                                    'categories',
                                    'emailSupportAddress',
                                    'rolesAdminManagerDeveloperExists'
                                ]))->render();

        }
        elseif ( strpos($page, '/tickets/category/') !== false )
        {

            $slug = explode('/', $page);
            $slug = end($slug);

            $myAgentTickets = false;

            $ticketPriorities = TicketPriority::all();
            $ticketTypes      = TicketType::all();
            $ticketStatus     = TicketStatus::all();
            $customVariables  = CustomVariable::all();
            $emailTemplates   = EmailTemplate::all();
            $agents           = User::allAgents()->get();

            if ( Auth::user()->rolesByIdExists([Role::ADMIN, Role::MANAGER, Role::DEVELOPER, Role::CUSTOMER_SERVICE_SUPPORT]) )
            {
                $tickets = Ticket::excludeFacebook()
                                    ->whereHas('categories', function ($q) use ($slug) {
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
                                ->whereHas('categories', function ($q) use ($slug) {
                                    $q->where('slug', $slug);
                                })
                                ->orderBy('created_at', 'DESC')
                                ->paginate(20);
            }

            $tags       = Tag::all();
            $categories = Category::all();

            return view('ticketing.ticketing_table_data', compact([
                                    'tickets',
                                    'ticketPriorities',
                                    'ticketTypes',
                                    'ticketStatus',
                                    'customVariables',
                                    'emailTemplates',
                                    'user',
                                    'agents',
                                    'myAgentTickets',
                                    'tags',
                                    'categories',
                                    'emailSupportAddress',
                                    'rolesAdminManagerDeveloperExists'
                                ]))->render();

        }
        else
        {

            if ( !$user->roles->first()->id == Role::AGENT )
            {
                $tickets = Ticket::orderBy('thread_started_at', 'DESC')->paginate(20);
            }
            else
            {
                // $tickets = Ticket::where('status_id', TicketStatus::STATUS_UNASSIGNED)->orderBy('thread_started_at', 'DESC')->paginate(20);
                $tickets = Auth::user()
                                ->tickets()
                                ->orderBy('thread_started_at', 'DESC')
                                ->paginate(20);
            }

            
            $agents = User::allAgents()->get();

            $setDuration = Ticket::UNASSIGNED_DURATION; // duration point where possible to assign tickets if already >= this value (hour)
            foreach ( $tickets as $ticket )
            {
                
                if ( $ticket->status_id == TicketStatus::STATUS_UNASSIGNED )
                {

                    $durationUnassigned = Ticket::getDurationUnassigned($ticket->thread_started_at);

                    if ($durationUnassigned >= $setDuration)
                    {
                        // do process, assign ticket to an agent
                    }

                    $ticket->durationUnassignedStr = Ticket::get_time_ago( strtotime($ticket->thread_started_at) );

                }

            }

            $tags = Tag::all();
            $categories = Category::all();

            return view('ticketing.ticketing_table_data', compact('tickets', 'user', 'agents', 'tags', 'categories', 'emailSupportAddress', 'rolesAdminManagerDeveloperExists'))->render();

        }

    }

    public function ajaxRefreshTemplatesListing(Request $request)
    {

        $emailTemplates = EmailTemplate::orderBy('created_at', 'DESC')->paginate(10);
        $customVariables  = CustomVariable::all();

        return view('emailTemplates.email_templates_table_data', compact(['emailTemplates', 'customVariables']))->render();

    }

    public function ajaxGetTicketStatus(Request $request)
    {

        if ( $request->ajax() )
        {
            $ticket = Ticket::find($request->ticket_id);
        }

        return response()->json(['success' => true, 'ticket' => $ticket]);

    }

    public function ajaxGetChatStatus(Request $request)
    {

        if ( $request->ajax() )
        {
            $chat = Chat::find($request->chat_id);
        }

        return response()->json(['success' => true, 'chat' => $chat]);

    }

    public function ajaxUpdateTicket(Request $request)
    {

        if ( $request->ajax() ) {

            $request->is_user_manager = (int)$request->is_user_manager;
            
            DB::beginTransaction();

            try {

                $ticket = Ticket::find($request->ticket_id);

                $ticket->status_id   = $request->ticket_status;

                /*$ticket->type_id     = $request->ticket_type;

                if ( $request->is_user_manager )
                {
                    $ticket->priority_id = $request->ticket_priority;
                }*/

                if( !$ticket->save() )
                {
                    throw new Exception("Something went wrong. Please try again.");
                }


                DB::commit();

            }
            catch (exception $e)
            {

                DB::rollback();

                return response()->json(['error'=>$e->getMessage()]);

            }

            return response()->json(['success' => 'A ticket has been updated.']);

        }

    }

    public function ajaxSendMessage(Request $request)
    {

        // $start_time = microtime(true);
        if( $request->ajax() ) {

            // if( Auth::id() == 1 )
            // {
            //     dd($request->all());
            // }

            // dd($request->all());
            //another ebay test

            $ticket = Ticket::find($request->ticket_id);

            $reAssignToMe = filter_var($request->reAssignToMe, FILTER_VALIDATE_BOOLEAN);
            

            if ( $ticket->origin_id == TicketOrigin::ORIGIN_EBAY )
            {

                /*if(Auth::id() == 155)
                {
                    dump($request->all());
                    $_fileIds = [];
                    if ( isset($request->file_ids) )
                    {

                        $files = $this->getFiles($request->file_ids);

                        // dd($files->first());
                        $ebay         = new EbayAPI;
                        dd(EbayAPI::uploadFile($files->first()));
                        dd(999);
                        foreach($files as $file)
                        {

                            $_file = Storage::get('public/attachments/'.$file->name);
                            $img = Image::make($_file)->widen(1000)->encode($file->extension, 90);
                            // $storeResizedImage = Storage::disk('baseStorage')->put( 'app/public/attachments/' . $file->name, $img);
                            $storeResizedImage = Storage::put( 'public/attachments/' . $file->name, $img);
                            dd($storeResizedImage);
                            // if( $storeResizedImage ) {}

                        }
                        
                    }

                    dd();

                }*/


                
                $ebay         = new EbayAPI;
                $emailContent = CustomVariable::getVariableResponse($ticket, $request->emailContent);
                // dump($emailContent);
                // $emailContent = str_replace(array("\n", "\r"), '', $emailContent);
                // dd($emailContent);
                // dd($emailContent);
                // echo $text;
                // dd($text);
                // $emailContent = html_entity_decode($emailContent);
                // $_emailContent = preg_replace("~<p>~", '<div>', $_emailContent);
                // $_emailContent = preg_replace("~</p>~", '<div />', $_emailContent);
                // dd($_emailContent);
                //to be save ebay message
                // $emailContent = strip_tags(html_entity_decode($emailContent));

                if ( Auth::id() == 1 )
                {
                    // $emailContent = html_entity_decode($emailContent);
                    $emailContent = strip_tags(html_entity_decode($emailContent));
                }
                else
                {
                    $emailContent = strip_tags(html_entity_decode($emailContent));
                }

                $files = null;

                if ( isset($request->file_ids) )
                {

                    $files = $this->getFiles($request->file_ids);
                    $files = $files->first();
                }
                
                //send ebay message
                $result = EbayAPI::send($emailContent, $ticket, $files);

                /*if( Auth::id() == 1 )
                {
                    $result = EbayAPI::tmpSend($emailContent, $ticket, 'https://ots.blackedgedigital.com/images/black-edge-logo.png');
                }
                else
                {
                    $result = EbayAPI::send($emailContent, $ticket);
                }*/

                $emailContent = str_replace(array("\n", "\r"), '<br>', $emailContent);

                //add additional process on send ebay message it will automatically store it in the app.
                $randAlphanumeric = random_bytes(8);
                $randAlphanumeric = bin2hex($randAlphanumeric);

                $user = Auth::user();

                $userName      = $user->name;
                $userEmail     = '<'.$user->email.'>';
                $userNameEmail = $userName.' '.$userEmail;

                $emailContent = rtrim(strtr(base64_encode($emailContent), '+/', '-_'), '=');

                /*if( Auth::id() == 1 )
                {
                    echo base64_decode($emailContent);
                    dd();
                }*/

                $storeMessage = Message::create([
                    'ticket_id'     => $ticket->id,
                    'message_id'    => $randAlphanumeric,
                    'message'       => $emailContent,
                    // 'from'          => $userNameEmail,
                    'from'          => 'Brandbeast',
                    'internal_date' => \Carbon\Carbon::now(),
                    'created_at'    => \Carbon\Carbon::now(),
                    'updated_at'    => \Carbon\Carbon::now(),
                ]);

                $ticket->updated_at = \Carbon\Carbon::now();
                $ticket->save();

            }
            else
            {

                $user = Auth::user();

                Log::info("Sending Message.. TicketId => User => Time: " . $request->ticket_id . " => " .$user->name . " => " .\Carbon\Carbon::now());

                // $userName      = $user->name;
                // $userEmail     = '<'.$user->email.'>';
                // $userNameEmail = $userName.' '.$userEmail;
                $emailSupportAddresses = EmailSupportAddress::active()->first();
                $userName              = $emailSupportAddresses->name;
                $userEmail             = '<'.$emailSupportAddresses->email.'>';
                $userNameEmail         = $userName.' '.$userEmail;

                $assignedTicket = AssignedTicket::where(['ticket_id' => $request->ticket_id]);
                
                if (!$assignedTicket->count())
                {
                    AssignedTicket::create(['user_id' => $user->id, 'ticket_id' => $request->ticket_id]);
                }

                $getUserSignature = $this->getUserSignature();
                $src = '';

                $time_start = microtime(true);

                if ( !empty( $getUserSignature ) )
                {

                    $pattern = '/src="([^"]*)"/';
                    preg_match($pattern, $getUserSignature, $matches);
                    if ( !empty($matches) )
                    {
                        $src     = str_replace( '..', base_path().'/public', $matches[1] );
                    }
                    
                    // $getUserSignature = '<p>&nbsp;</p>'.str_replace('../images', URL::to('/').'/images', $getUserSignature);
                    $getUserSignature = '<br><br>'.str_replace('..', url('/'), $getUserSignature);

                }
                // logger('SendMessage - Get user signature: ' . (microtime(true) - $time_start) );
                
                // $emailContent     = CustomVariable::getVariableResponse($ticket, $request->emailContent);
                $doNotReplyMessage = '<div><b>** Please do not reply on this email. **</b></div><br><br>';
                // $emailContent      = $doNotReplyMessage . $request->emailContent;
                $emailContent      = $request->emailContent;
                $emailContent      = $emailContent.$getUserSignature;

                //attachment
                $subjectCharset = $charset = 'utf-8';


                $boundary   = uniqid(rand(), true);
                
                $_ticket    = Ticket::where('thread_id', $request->thread_id)->first();
                $lastMessageObj = $_ticket->messages->last();
                $isNew      = ($lastMessageObj->message_id == 0) ? true : false;
                $_messageId = $lastMessageObj->message_id;

                // if( Auth::id() == 1 ) {
                    // append previous message
                    $lastMessage = $this->decodeMessage( $lastMessageObj->message );
                    $lastMessageDateTime = date('M d, Y H:i A', strtotime( $lastMessageObj->internal_date ));
                    $lastMessage = '<br/><hr style=" height: 1px; background-color: #d9d9d9; border: none; "><br/><div>
                                        <p style="color: #999999; margin-top: 0px;">'.$lastMessageDateTime.' &nbsp;&nbsp;&lt;'.$lastMessageObj->from.'&gt;</p>
                                        <blockquote style="margin:0px 0px 0px 0.8ex;border-left:1px solid rgb(204,204,204);padding-left:1ex">'.$lastMessage.'</blockquote>
                                    </div>';
                    logger('Blockquote below...');
                    // logger($lastMessage);

                    $emailContent .= $lastMessage;
                // }
                
                $strRawMessage = "From: $userNameEmail\r\n"; // needs to get from user details
                // $strRawMessage = "From: no-reply <support@frankiesautoelectrics.com.au>\r\n"; // needs to get from user details

                if ( !is_null($ticket->reply_to) && !empty($ticket->reply_to) )
                {
                    $strRawMessage .= "To: $ticket->reply_to\r\n";
                }
                else
                {
                    $strRawMessage .= "To: $ticket->requester\r\n";
                }

                // if( Auth::id() == 1 )
                // {
                //     $strRawMessage .= "Bcc: <theodore@frankiesautoelectrics.com.au>,<rodneydcro3@gmail.com>\r\n";
                // //     // $strRawMessage .= "Bcc: theodore@frankiesautoelectrics.com.au\r\n";
                // }

                $strRawMessage .= "Bcc: <sales@frankiesautoelectrics.com.au>,<theodore@frankiesautoelectrics.com.au>\r\n"; // request to be added - 03/21/23
                $strRawMessage .= "Subject: $ticket->subject\r\n";
                $strRawMessage .= "Message-ID: $_messageId\r\n";
                $strRawMessage .= "In-Reply-To: $userEmail\r\n";
                $strRawMessage .= "MIME-Version: 1.0\r\n";
                $strRawMessage .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\r\n";


                // if ( !empty($src) )
                // {
                //     //note for multiple attachments, loop through content type

                //     $strRawMessage .= 'Content-Type: '. $mimeType .'; name="'. $file->name .'";' . "\r\n";
                //     // $strRawMessage .= 'Content-Disposition: attachment; filename="' . $file->name . '"; size=' . filesize($filePath). ';' . "\r\n"; //
                //     $strRawMessage .= 'Content-Disposition: attachment; filename="' . $file->name . '"; size=' . $fileSize . ';' . "\r\n"; //
                //     $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n"; //
                //     // $strRawMessage .= chunk_split(base64_encode(file_get_contents($filePath)), 76, "\n") . "\r\n"; //
                //     $strRawMessage .= chunk_split($fileData, 76, "\n") . "\r\n"; //
                // }

                // message & attachments -- dropzone
                // $files = File::whereIn('id', [63,64])->get();

                $_fileIds = [];
                if ( isset($request->file_ids) )
                {

                    $time_start = microtime(true);

                    $files = $this->getFiles($request->file_ids);

                    foreach($files as $file)
                    {
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

                        array_push($_fileIds, $file->id);

                    }

                    // logger('SendMessage - Get Files: ' . (microtime(true) - $time_start) );
                    
                }
                // logger('@@ fileIds: ' . json_encode($_fileIds));

                $strRawMessage .= "\r\n--{$boundary}\r\n";
                $strRawMessage .= "Content-Type: text/html; charset=utf-8\r\n";
                $strRawMessage .= 'Content-Transfer-Encoding: base64' . "\r\n\r\n";
                // $strRawMessage .= str_replace('href=', 'href=3D', $emailContent) . "\r\n";
                if( Auth::id() == 1 )
                {
                    logger($strRawMessage);
                }
                $strRawMessage .= $emailContent;

                if ( $ticket->origin_id != TicketOrigin::ORIGIN_CHAT || !$isNew )
                {

                    if ( !Ticket::BACKGROUND_PROCESS_SEND_MESSAGE )
                    {
                        $result = GmailApi::sendMessage($strRawMessage, $request->thread_id);

                        if ($reAssignToMe)
                        {
                            //update assignment of ticket if you responded in a ticket that is not assigned to you
                            AssignedTicket::where('ticket_id', $request->ticket_id)->where('user_id', '!=', $user->id)->update([
                                'user_id'   => $user->id,
                            ]);
                        }

                        if ( !empty($request->notesContent) )
                        {
                            Message::where('message_id', $result->id)->update(['notes' => $request->notesContent]);
                        }

                    }
                    else
                    {
                        $_now   = \Carbon\Carbon::now();

                        $storeMessage = Message::create([
                            // 'ticket_id'     => $createTicket->id,
                            'ticket_id'     => $ticket->id,
                            'message_id'    => '',
                            // 'message'       => self::messageEncode($message['message'], $message['attachments']),
                            'message'       => rtrim(strtr(base64_encode($htmlEmail), '+/', '-_'), '='),
                            'notes'         => empty($request->notesContent)  ? '' : $request->notesContent,
                            'file_ids'      => empty($_fileIds) ? null : json_encode($_fileIds),
                            'from'          => $emailSupportAddresses->email,
                            'to'            => $ticket->requester,
                            'internal_date' => $_now,
                            'created_at'    => $_now,
                            'updated_at'    => $_now,
                        ]);

                        if ( $storeMessage )
                        {
                            // SendMessage::dispatch($strRawMessage, $ticket->id, $userNameEmail, $ticket->requester)->delay(now()->addSeconds(5));
                            SendMessage::dispatch($strRawMessage, $request->thread_id, null, false, \Auth::id())->onQueue('sendmessage');
                        }

                        $result = ['threadId' => $ticket->thread_id];
                    }

                }
                else
                {
                    if ($isNew)
                    {
                        $result = GmailApi::sendChatMessage($strRawMessage, $emailContent, $ticket->id);
                    }
                }


            }

            /*if ( !empty($request->notesContent) )
            {
                Message::where('message_id', $result->id)->update(['notes' => $request->notesContent]);
            }*/

        }

        // $end_time = microtime(true);
        // dd(($end_time - $start_time));

        return response()->json(['success'=> true, 'emailContent' => $emailContent, 'result' => $result]);

    }

    public function getFiles($fileIds)
    {
        $files = File::whereIn('name', $fileIds)->get();

        return $files;
    }

    public function getUserSignature()
    {

        $signatureContent = '';
        $user             = Auth::user();
        $signature        = Signature::where('user_id', $user->id)->where('active', Signature::ACTIVE)->first();

        if ( !empty($signature) )
        {
            return base64_decode($signature->content, true);
            // return $signature->content;
        }
        else
        {
            return '';
        }

    }

    // public function getVariableResponse($emailContent, $variables)`
    // {

    //     $response = preg_replace_callback('/{{(.+?)}}/ix',function($match)use($variables){
    //         return !empty($variables[$match[1]]) ? $variables[$match[1]] : $match[0];
    //    },$emailContent);

    //    return $response;

    // }

    public function ajaxGetMessages(Request $request)
    {

        if( $request->ajax() ) {

            // if( Auth::id() == 1 )
            // {
            //     dd(99);
                /*dd($request->all());

                $url = URL::current();
                dump($url);
                if (strpos($url, 'tickets/spam'))
                {
                    dd('inn');
                }
                else
                {
                    dd('not found');
                }*/
            // }
            
            // $ticket = Ticket::find($request->ticket_id);
            if( isset($request->path) && $request->path == '/tickets/spam' )
            {
                $ticket = Ticket::withTrashed()->with(['messages', 'categories', 'tags'])->where('id', $request->ticket_id)->first();
            }
            else
            {
                $ticket = Ticket::with(['messages', 'categories', 'tags'])->where('id', $request->ticket_id)->first();
            }

            // $ticket->thread_started_at = $this->get_time_ago( strtotime($ticket->thread_started_at) );
            $ticket->thread_started_at = \Carbon\Carbon::parse($ticket->thread_started_at)->diffForHumans();

            // $ticket->messages;

            // $ticket->tags;

            $isNotMyTicket = AssignedTicket::where('ticket_id', $request->ticket_id)->where('user_id', '!=', Auth::id())->count();

            /*$category = Category::where('parent_category_id', null)->get()->toArray();
            dump($c);

            $key = array_search('LISTING/SUPPLIER', array_column($c, 'name'));

            dump($key);

            $c[$key]['sub_categories'][] = Array(5,4,3,2,1);
            dd($c[$key]['sub_categories']);*/

            //insert sub categories under a category
            if ( $ticket->categories->count() )
            {
                $c = Category::where('parent_category_id', null)->get()->toArray();

                foreach($ticket->categories as $category)
                {
                    // dump($category->toArray());
                    $_category                   = $category;
                    $key                         = array_search($category->parent_category_id, array_column($c, 'id'));
                    $c[$key]['sub_categories'][] = $_category->toArray();

                }

                $c = array_map(function ($_categories) {

                    if ( isset($_categories['sub_categories']) )
                    {
                        return $_categories;
                    }
                    else
                    {
                        return false;
                    }


                }, $c);

                $c = array_filter($c);

                $ticket->custom_categories = $c;
            }

            $messagesDate  = Array();
            $_files        = [];
            $imgExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            foreach ($ticket->messages as $message) {

                $ticket->is_my_ticket = (isset($ticket->assignedTo->user_id) && $ticket->assignedTo->user_id == Auth::user()->id) ? true : false;

                //decode message, remove style if exists, remove progress-bar class(which messed the html/text email color) in case the email has one
                // $decodedMessage = $this->decodeMessage($message->message);
                $decodedMessage = ($ticket->origin_id == TicketOrigin::ORIGIN_EBAY) ? $this->setOutgoingLinksToTarget($message->message) : $this->decodeMessage($message->message);
                $decodedMessage = preg_replace('/<\s*style.+?<\s*\/\s*style.*?>/si', ' ', $decodedMessage );
                // $decodedMessage = preg_replace('/<\s*meta.+?<\s*\/\s*meta.*?\>/si', ' ', $decodedMessage );
                $decodedMessage = str_replace("progress-bar", "", $decodedMessage);
                $message->message = mb_convert_encoding($decodedMessage, 'UTF-8', 'UTF-8');


                //temporary fix for plain text, for some reason on some messages the gmail only returns text/plain and not text/html
                //if message has no html, automatically add <p> on every white/breaklines
                if ( $message->message == strip_tags($message->message)  )
                {
                    $message->message = preg_replace("/[\r\n]/","<p></br>",$message->message);
                }

                /*if( Auth::id() == 1 && $ticket->id == 60049 && $message->message_id == '226b5f5b8d418ac7' )
                {
                    dump($message->message);
                    echo $message->message;
                }*/

                $message->plainText = strip_tags($message->message);
                $message->plainText = str_replace(array("\n", "\r"), '&nbsp;', $message->plainText);
                
                // dd($message);
                // $messagesDate[$message->message_id] = date('M d h:i', strtotime($message->created_at));
                $messagesDate[$message->message_id] = date('M d H:i', strtotime($message->internal_date));

                if ( !empty($message->file_ids) )
                {

                    $files = \App\File::whereIn('id', json_decode($message->file_ids))->get();

                    foreach($files as $file)
                    {

                        if ( in_array($file->extension, $imgExtensions) )
                        {
                            $file->link = '';
                            $file->data = base64_encode( \Storage::get('public/attachments/'.$file->name) );
                        }
                        else
                        {
                            $file->data = '';
                            $file->link = URL::to('/') . Storage::url('public/attachments/' . $file->name);
                        }

                        $_files[$message->message_id][] = $file;

                    }

                }

            }
            
        }

        return response()->json(['success'=> true, 'ticket' => $ticket, 'messagesDate' => $messagesDate, 'files' => $_files, 'isNotMyTicket' => $isNotMyTicket]);

    }

    public function setOutgoingLinksToTarget($message)
    {

        // $html     = base64_decode($message);
        $html     = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message));
        $hostName = parse_url(url('/'))['host'];

        $doc = new \DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        // @$doc->loadHTML($html);

        foreach ($doc->getElementsByTagName('a') as $link) {

            $href = $link->getAttribute('href');

            if ( !Str::contains($href, $hostName) )
            {
                $link->setAttribute('target', '_blank');
            }

        }

        $result =  $doc->saveHTML();

        return str_replace(['&Acirc;','&acirc;'], '', $result);

    }

    public function decodeMessage($message)
    {

        $_message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message)); 
        $_message = quoted_printable_decode($_message);
        /*if (strpos($_message, '<div class="gmail_quote">'))
        {
            $_message = substr($_message, 0, strpos($_message, '<div class="gmail_quote">'));
        }*/

        return $_message;

    }

    public function get_time_ago( $time )
    {
        $time_difference = time() - $time;

        
        if( $time_difference < 1 ) { return 'less than 1 second ago'; }
        $condition = array(
                            12 * 30 * 24 * 60 * 60 => ' year',
                            30 * 24 * 60 * 60      => ' month',
                            24 * 60 * 60           => ' days ago',
                            60 * 60                => ' hours ago',
                            60                  => ' minutes ago',
                            1                   => ' seconds ago'
                        );
        
        foreach( $condition as $secs => $str )
        {
            $d = $time_difference / $secs;
            
            if( $d >= 1 )
            {
                $t = round( $d );
                return $t . $str;
            }
        }
    }

    protected function create(array $data) {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    protected function validator(array $data) {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:5', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);
    }

}
