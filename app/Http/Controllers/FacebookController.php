<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Facebook;
use App\FacebookPage;
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
use Log;
use DateTime;
use DateTimeZone;

class FacebookController extends Controller
{
    
    public function index(Request $request)
    {

      $facebookPages = FacebookPage::orderBy('id', 'DESC')->paginate(10);
      $facebook      = Facebook::find(1);

      return view('channels.facebook', compact(['facebook','facebookPages']));
      
    }

    public function oauth(Request $request)
    {

        $fb = new \Facebook\Facebook([
            'app_id' => '765592150703088',
            'app_secret' => 'af06bdf5e2018a0830b1eaad693c7477',
            'default_graph_version' => 'v2.4',
          ]);

          $helper = $fb->getRedirectLoginHelper();
          $helper->getPersistentDataHandler()->set('state', $request->query->get('state'));
  
          try {
                $accessToken = $helper->getAccessToken();
                \Session::put('fb-access-token', $accessToken);

                $facebook = Facebook::where('deleted_at', NULL)->first();
                $facebook->access_token = $accessToken;
                $facebook->save();

                Facebook::facebookInstance();
                Facebook::syncAccountPages();

          } catch(\Facebook\Exceptions\FacebookResponseException $e) {
            // When Graph returns an error
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
          } catch(\Facebook\Exceptions\FacebookSDKException $e) {
            // When validation fails or other local issues
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
          }
          return redirect()->back();

    }

    public function chat(Request $request)
    {

      $ticketPriorities = TicketPriority::all();
      $ticketTypes      = TicketType::all();
      $ticketStatus     = TicketStatus::all();
      $customVariables  = CustomVariable::all();
      $emailTemplates   = EmailTemplate::all();
      $user             = Auth::user();
      $agents           = User::allAgents()->get();

      // $tickets = Ticket::where('origin_id', TicketOrigin::ORIGIN_FACEBOOK)->orderBy('updated_at', 'DESC')->paginate(10);
      $tickets = Ticket::where('origin_id', TicketOrigin::ORIGIN_FACEBOOK)->orderBy('updated_at', 'DESC')->get();

      return view('chat.facebook', compact(['tickets','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents']));

    }

    public function syncConversations()
    {

      Log::info('Syncing Facebook Conversations');

      $start = date('h:i:s');
      
      Facebook::facebookInstance();
      Facebook::syncConversations();
      
      $end = date('h:i:s');

      Log::info('Synced Facebook Conversations Start - End Time: '.$start.' - '.$end);

    }
}
