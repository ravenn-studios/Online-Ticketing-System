<?php

namespace App\Listeners;

use App\Events\CheckExportStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailPerformanceReport;

class SendEmailExportNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        logger('Send Email Export Notification listener..');
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(CheckExportStatus $event)
    {

        // $filePath = Storage::url('laravel-excel' . $event->filename);
        // $filePath = Storage::disk('public')->get('Agents Performance - 1658990735.xls');
        // dd($filePath);

        logger('Sending Performance Report to: ' . $event->email . ' , with filename: ' . $event->filename);

        $filePath = Storage::disk('baseStorage')->get('app/laravel-excel/' . $event->filename);

        $r = Mail::to($event->email)->send(new MailPerformanceReport($event->subject, $event->filename, $filePath));

        return ['success' => true, 'filename' => $event->filename];
    }
}
