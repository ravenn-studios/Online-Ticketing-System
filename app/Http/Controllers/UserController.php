<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\Role;
use App\Signature;
use App\UserCustomPage;
use App\CustomPageCondition;
use App\Chat;
use App\Setting;
use App\Ticket;
use App\Reminder;
use App\UserSchedule;
use DB, Redirect, Exception;
use App\Exports\UsersExport;
use App\Exports\UsersArrayExport;
use App\Exports\AgentPerformanceExport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{

    public function index()
    {
        // $user = User::find(7);
        // $user->password = bcrypt('Hj2:m;/9jSTN5vU~');
        // $user->save();
        
        $users = User::orderBy('created_at', 'DESC')->paginate(10);

        // return view('ticketing.index');
        return view('users.index', compact(['users']));
    }

    public function schedules()
    {

        $user = Auth::user();

        if ( $user->roles->first()->id != Role::ADMIN && $user->roles->first()->id != Role::DEVELOPER && $user->roles->first()->id != Role::MANAGER && $user->id != 9 ) // give anne access to this page
        {
            return redirect('user/settings');
        }

        // dd( strtolower( \Carbon\Carbon::now()->format('D') ) );
        $users       = User::faeAgents()->get(['id'])->toArray();
        $faeAgentsId = array_column($users, 'id');

        // $user             = Auth::user();
        $usersSchedule    = UserSchedule::all();
        // $usersSchedule    = UserSchedule::whereIn('user_id', $faeAgentsId)->get();
        // $authUserSchedule = $user->userSchedule;

        //function for fetching users to be used for auto assignment
        // dd( array_column(UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray(),'user_id') );
        // $userIds = array_column(UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray(),'user_id')
        // $users = User::whereIn('id', $userIds)->withCount('tickets')->orderBy('tickets_count', 'asc')->get();
        // dd($users);
        // dd( UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('user_id')->toArray() );
        // dd( UserSchedule::where(strtolower( \Carbon\Carbon::now()->format('D') ), true)->get('id') );

        // only faeAgents - on load users/schedule create an faeAgent schedule if not yet exists.
        foreach( $faeAgentsId as $faeAgentId )
        {
            UserSchedule::firstOrCreate(['user_id' => $faeAgentId], [
                'user_id' => $faeAgentId,
                'mon'     => true,
                'tue'     => true,
                'wed'     => true,
                'thu'     => true,
                'fri'     => true,
                'sat'     => true,
                'sun'     => true,
            ]);
        }
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

        return view('users.users_schedules', compact(['arrDateDay','usersSchedule','user']));
    }

    public function ratings()
    {
        // $users = User::orderBy('created_at', 'DESC')->paginate(10);
        $users = User::orderBy('created_at', 'DESC')->paginate(10);

        // foreach ($users as $user)
        // {
 
        //     $chatLogs = $user->chatLogs()->where('ended_at','<>', NULL)->get('chat_id');
            
        //     // dump($user->id);
        //     if ( $chatLogs->count() )
        //     {
        //         $countChats   = $chatLogs->count();
        //         $sumOfRatings = 0;
        //         $avgRatings   = 0;

        //         foreach($chatLogs as $chatLog)
        //         {
                    
        //             $chat = Chat::find($chatLog->chat_id);
        //             dump($user->id . ': ' .$chat);
        //             $sumOfRatings += $chat->rating;

        //         }

        //         $avgRatings = $sumOfRatings / $countChats;

        //         // dump($user->id . ': ' .$avgRatings);

        //     }

        // }
        // dd();

        // return view('ticketing.index');
        return view('users.ratings', compact(['users']));
    }

    public function settings()
    {
        // $users = User::orderBy('created_at', 'DESC')->paginate(10);

        $user                   = Auth::user();
        $signatures             = Signature::where('user_id', $user->id)->orderBy('created_at', 'DESC')->paginate(10);
        $customPages            = $user->customPages()->orderBy('created_at', 'DESC')->paginate(10);
        $autoTicketDistribution = Setting::active()->where('name', Setting::AUTO_TICKET_DISTRIBUTION)->count();

        $users = User::faeAgents()->orderBy('created_at', 'DESC')->paginate(10);

        $ticket = new Ticket;

        foreach($users as $_user)
        {
            $_user->ticketLimit;
            $_user->assignedTickets = $ticket->count_user_tickets($_user->id);
        }

        return view('users.settings', compact(['signatures', 'customPages', 'autoTicketDistribution', 'users']));
    }

    public function reminders()
    {

        //user
        if ( !Auth::user()->rolesByIdExists([\App\Role::MANAGER, \App\Role::ADMIN, \App\Role::DEVELOPER]) )
        {

            $reminders = Reminder::where('for_user', Auth::id())
                                ->where('read', false)
                                ->where('status_id', Reminder::STATUS_PENDING)
                                ->orWhere('type', Reminder::TYPE_USER_GENERATED)
                                ->orderBy('created_at', 'DESC')
                                ->paginate(10);  
        }
        else
        {
            // admin,dev,manager - also show system reminders
            $reminders = Reminder::where('for_user', Auth::id())
                                ->where('read', false)
                                ->where('status_id', Reminder::STATUS_PENDING)
                                ->orWhere('type', Reminder::TYPE_SYSTEM_GENERATED)
                                ->orderBy('created_at', 'DESC')
                                ->orderBy('type', 'ASC')
                                ->paginate(10);
        }
        
        // dd($reminders);

        return view('users.reminders', compact(['reminders']));
    }

    public function createSignature(Request $request)
    {

        $user               = Auth::user();
        // $request->signature = base64_encode( $request->signature );
        // $request->signature = rtrim(strtr(base64_encode($request->signature), '+/', '-_'), '=');
        $request->signature = base64_encode($request->signature);
        $request->status    = (int)$request->status;

        DB::beginTransaction();

        try {

            $signatureNameExists = (Signature::where('name', $request->name)->count());

            if ( $signatureNameExists )
            {
                throw new Exception("Email signature name already exists.");
            }
            else if ( empty($request->name) )
            {
                throw new Exception("Email signature name is required.");
            }
            else if ( empty($request->signature) )
            {
                throw new Exception("Email signature is required.");
            }
            else
            {

                $signature = Signature::create([
                                'user_id' => $user->id,
                                'name'    => $request->name,
                                'content' => $request->signature,
                                'active'  => $request->status,
                            ]);

                if ( $request->status == Signature::ACTIVE )
                {
                    $userSignatures = Signature::where('user_id', $user->id)
                                                ->where('id', '<>', $signature->id)
                                                ->update(['active' => Signature::INACTIVE]);
                }

            }

            DB::commit();

        }
        catch(exception $e) {

            DB::rollback();

            return response()->json(['error' => $e->getMessage()]);

        }

        return response()->json(['success' => 'Email Signature has been created.']);

    }

    public function updateSignature(Request $request)
    {
        $user                  = Auth::user();
        $request->signature_id = (int)$request->signature_id;
        $request->status       = (int)$request->status;
        $request->signature    = base64_encode( $request->signature );

        DB::beginTransaction();

        try {

            $_signature          = (Signature::find($request->signature_id));
            $signatureNameExists = (Signature::where('name', $request->name)->count());

            if ( $signatureNameExists && $_signature->name != $request->name )
            {
                throw new Exception("Email signature name already exists.");
            }
            else if ( empty($request->name) )
            {
                throw new Exception("Email signature name is required.");
            }
            else if ( empty($request->signature) )
            {
                throw new Exception("Email signature is required.");
            }
            else
            {

                $signature = Signature::where('id', $request->signature_id)
                                        ->update([
                                            'name'    => $request->name,
                                            'content' => $request->signature,
                                            'active'  => $request->status,
                                        ]);

                if ( $request->status == Signature::ACTIVE )
                {
                    $userSignatures = Signature::where('user_id', $user->id)
                                                ->where('id', '<>', $request->signature_id)
                                                ->update(['active' => Signature::INACTIVE]);
                }

            }

            DB::commit();

        }
        catch(exception $e) {

            DB::rollback();

            return response()->json(['error' => $e->getMessage()]);

        }

        return response()->json(['success' => 'Email Signature has been updated.']);

    }

    public function getSignatureDetails( Request $request)
    {
        $signature = Signature::find($request->signature_id);

        $signature->content = $this->decodeMessage($signature->content);

        return response()->json(['signature' => $signature]);
    }

    public function deleteSignature( Request $request)
    {

        $signature = Signature::find($request->signature_id)->delete();

        if ( !$signature )
        {
            return response()->json(['error' => 'Something went wrong. Please try again']);
        }

        return response()->json(['success' => 'Email Signature has been deleted.']);

    }

    public function decodeMessage($message)
    {

        $_message = base64_decode(str_replace(array('-', '_'), array('+', '/'), $message)); 
        $_message = quoted_printable_decode($_message);
        if (strpos($_message, '<div class="gmail_quote">'))
        {
            $_message = substr($_message, 0, strpos($_message, '<div class="gmail_quote">'));
        }

        return $_message;

    }

    public function export() 
    {
        // return Excel::download(new UsersExport, 'users.xlsx');
        /*$export = new AgentPerformanceExport([

            [
                'id'  => 4,
                'name'  => 'rodney',
                'email' => 'rodney@frankiesautoelectrics.com.au'
            ],
            [
                'id'  => 12,
                'name'  => 'rodney2',
                'email' => 'rodney2@frankiesautoelectrics.com.au'
            ],
        ]);*/

        $export = new AgentPerformanceExport(User::all());

        return Excel::download($export, 'users-array-export.xlsx');

    }

}
