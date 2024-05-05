<?php

namespace App\Http\Controllers;

use Auth;
use App\Ticket;
use App\TicketStatus;
use App\TicketPriority;
use App\TicketType;
use App\TicketOrigin;
use App\CustomVariable;
use App\EmailTemplate;
use App\Chat;
use Illuminate\Http\Request;
use App\User;
use App\Role;

class ChatController extends Controller {

   public function index(Request $request)
   {

      $chats            = Chat::where('status_id', TicketStatus::STATUS_UNASSIGNED)->orderBy('updated_at', 'DESC')->get();
      $ticketPriorities = TicketPriority::all();
      $ticketTypes      = TicketType::all();
      $ticketStatus     = TicketStatus::all();
      $customVariables  = CustomVariable::all();
      $emailTemplates   = EmailTemplate::all();
      $user             = Auth::user();
      $agents           = User::allAgents()->get();
      

      return view('chat.chat', compact(['chats','ticketPriorities','ticketTypes', 'ticketStatus', 'customVariables', 'emailTemplates', 'user', 'agents']));

   }

}
