<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class EmailSenderJob implements ShouldQueue
{
    use Queueable;

    private $email;
    private $params;
    private $subject;
    private $view;
    
    public function __construct($email, $params, $subject, $view)
    {
        $this->email = $email;
        $this->params = $params;
        $this->subject = $subject;
        $this->view = $view;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = $this->email;
        Mail::send($this->view, $this->params, function ($message) use($email) {
            $message->subject($this->subject);
            $message->to($email);
        });
    }
}
