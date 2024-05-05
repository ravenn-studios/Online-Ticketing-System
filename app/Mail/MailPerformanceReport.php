<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MailPerformanceReport extends Mailable
{
    use Queueable, SerializesModels;

     /**
     * Create a new message instance.
     *
     * @return void
     */
    // protected $subject;
    // protected $filePath;
    // protected $filename;

    public function __construct($subject, $filename, $filePath)
    {
        $this->subject  = $subject;
        $this->filename = $filename;
        $this->filePath = $filePath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
            ->from('no-reply@frankiesautoelectrics.com.au', 'Frankies Auto Electrics')      
            ->view('email.email_performance_report')
            ->attachFromStorageDisk('baseStorage', 'app/laravel-excel/' . $this->filename);
            // ->attachData($this->filePath, $this->filename, [
            //     'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            // ]);
    }
}
