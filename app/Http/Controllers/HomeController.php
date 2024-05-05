<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AuthGmail;
use App\EmailSupportAddress;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Google_Service_Gmail_Label;
use Google_Service_Gmail_Filter;
use Google_Service_Gmail_FilterCriteria;
use Google_Service_Gmail_FilterAction;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
    //     return redirect()->route('tickets.myTickets');
    // }
    // public function index(Request $request)
    // {
        $authGmail = new AuthGmail;

        //for authorizing gmail account
        if ( $authGmail->credentials_in_browser() )
        {
            $emailSupportIdToAuthAsSuffix = $request->session()->get('email_support_id_to_auth');

            //generate the token
            $authGmail->create_client($emailSupportIdToAuthAsSuffix);
            // $authGmail->create_client();

            // dump($request->session());
            // dd($emailSupportIdToAuthAsSuffix);

            //update email support address to active after generating token
            $_id                         = (int)$request->session()->get('email_support_id_to_auth');
            $emailSupportAddress         = EmailSupportAddress::find( $_id );
            $emailSupportAddress->status = EmailSupportAddress::STATUS_ACTIVE;
            $emailSupportAddress->save();
            
            $request->session()->forget('email_support_id_to_auth');

            return redirect('channels/email')->with('authSuccess', 'Authorization complete.');
        }
        else
        {
            return redirect()->route('tickets.myTickets');
        }
    }

}
