<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect,Response,Exception;
use App\EmailSupportAddress;
use App\Ticket;
use App\Message;
use App\TicketType;
use App\TicketPriority;
use App\TicketStatus;
use App\CustomVariable;

class EmailSupportAddressController extends Controller
{
    
    public function index(Request $request)
    {
        $emailSupportAddresses = EmailSupportAddress::orderBy('created_at', 'DESC')->paginate(10);

        // return view('ticketing.index');
        return view('channels.index', compact(['emailSupportAddresses']));
    }

}
