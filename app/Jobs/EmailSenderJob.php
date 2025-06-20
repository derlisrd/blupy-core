<?php

namespace App\Jobs;


use Illuminate\Support\Facades\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class EmailSenderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
