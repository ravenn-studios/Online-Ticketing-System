<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\GmailApi;

class SendMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $strRawMessage;
    private $threadId;
    private $isComposedMessage;
    private $emailTo;
    private $userId;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    
    //queue timeout < retry_after time to avoid running the jobs twice

    // public $timeout = 360;
    // public $tries   = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($strRawMessage, $threadId, $emailTo = null, $isComposedMessage, $userId)
    {
        $this->strRawMessage     = $strRawMessage;
        $this->threadId          = $threadId;
        $this->isComposedMessage = $isComposedMessage;
        $this->emailTo           = $emailTo;
        $this->userId            = $userId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if ( $this->isComposedMessage == false )
        {
            $result = GmailApi::sendMessage($this->strRawMessage, $this->threadId);
        }
        else
        {
            $result = GmailApi::sendComposedMessage($this->strRawMessage, $this->emailTo, $this->userId);
        }

    }
}
