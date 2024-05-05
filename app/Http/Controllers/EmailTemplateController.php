<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect,Response,Exception;
use App\EmailTemplate;
use App\Ticket;
use App\Message;
use App\TicketType;
use App\TicketPriority;
use App\TicketStatus;
use App\CustomVariable;
use DateTime;
use DateTimeZone;

class EmailTemplateController extends Controller
{
    
    public function index()
    {
        $emailTemplates = EmailTemplate::orderBy('created_at', 'DESC')->paginate(10);
        $customVariables  = CustomVariable::all();

        // return view('ticketing.index');
        return view('emailTemplates.index', compact(['emailTemplates','customVariables']));
    }

}
