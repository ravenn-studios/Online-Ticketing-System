<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyUnattendedTickets extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($ticketId, $ticketSubject, $lastMessage, $lastMessageFrom, $lastMessageCreatedAt)
    {
        $this->ticket_id               = $ticketId;
        $this->ticket_subject          = $ticketSubject;
        $this->last_message            = $lastMessage;
        $this->last_message_from       = $lastMessageFrom;
        $this->last_message_created_at = $lastMessageCreatedAt;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject('Unattended Ticket #'.$this->ticket_id)
            // ->from('no-reply@frankiesautoelectrics.com.au', 'Frankies Auto Electrics')
            ->from('ots@blackedgedigital.com', 'OTS')
            ->view('email.email_unattended_ticket')
            ->with([
                'ticket_id'               => $this->ticket_id,
                'ticket_subject'          => $this->ticket_subject,
                'content'                 => $this->last_message,
                'last_message_from'       => $this->last_message_from,
                'ticket_view_url'         => url('/tickets/my-tickets?ticket_ids='.$this->ticket_id.''),
                'last_message_created_at' => $this->last_message_created_at,
            ]);
    }
}
