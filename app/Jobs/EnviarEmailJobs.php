<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarEmailJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $text;
    public $title;
    public $emails;
    /**
     * Create a new job instance.
     */
    public function __construct($title,$text,$emails)
    {
        $this->text = $text;
        $this->title = $title;
        $this->emails = $emails;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
        $asunto = $this->title;
        $params = ['title'=>$this->title,'text'=>$this->text];
        foreach ($this->emails as $email) {
            $datos = [
                'email'=>$email,
                'asunto'=>$asunto
            ];
            Mail::send('email.notificacion', $params, function ($message) use($datos) {
                $message->subject($datos['asunto']);
                $message->to($datos['email']);
            });
        }



        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
