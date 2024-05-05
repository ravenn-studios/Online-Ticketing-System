<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyAccountCreation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name, $email, $password)
    {
        $this->name     = $name;
        $this->email    = $email;
        $this->password = $password;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject('Account password update - '.$this->email)
            // ->from('no-reply@frankiesautoelectrics.com.au', 'Frankies Auto Electrics')
            ->from('ots@blackedgedigital.com', 'OTS')
            ->view('email.email_account_creation')
            ->with([
                'name'     => $this->name,
                'email'    => $this->email,
                'password' => $this->password,
            ]);
    }
}
