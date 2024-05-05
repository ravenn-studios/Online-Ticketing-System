<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\EmailNotification;
use App\Reminder;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    protected $user;
    protected $reminder;
    protected $ticketsViewUrl;

    public function __construct(User $user, Reminder $reminder, $ticketsViewUrl)
    {
        $this->user           = $user;
        $this->reminder       = $reminder;
        $this->ticketsViewUrl = $ticketsViewUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->reminder->title)
            ->from('ots@blackedgedigital.com', 'OTS - Black Edge')    
            // ->from('no-reply@frankiesautoelectrics.com.au', 'Do Not Reply')    
            ->view('email.notify_user_for_pending_tickets')
            ->with([
                'user'           => $this->user,
                'reminder'       => $this->reminder,
                'ticketsViewUrl' => $this->ticketsViewUrl,
            ]);
    }
}
