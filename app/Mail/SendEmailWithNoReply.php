<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\EmailNotification;
use App\Ticket;

class SendEmailWithNoReply extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    /*protected $user;
    protected $ticket;
    protected $emailContent;

    public function __construct(User $user, Ticket $ticket, $emailContent)
    {
        $this->user         = $user;
        $this->ticket       = $ticket;
        $this->emailContent = $emailContent;
    }*/

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Request Received')
            ->from('no-reply@frankiesautoelectrics.com.au', 'Frankies Auto Electrics')    
            ->view('email.email_user_using_no_reply_mail');
            // ->with([
            //     'ticket' => $this->ticket,
            //     'emailContent' => $this->emailContent,
            // ]);
    }
}
