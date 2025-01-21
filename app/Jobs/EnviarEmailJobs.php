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
    public $email;
    /**
     * Create a new job instance.
     */
    public function __construct($title,$text,$email)
    {
        $this->text = $text;
        $this->title = $title;
        $this->email = $email;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
        $asunto = $this->title;
        $params = ['title'=>$this->title,'text'=>$this->text];
        $datos = ['asunto'=>$asunto,'email'=>$this->email];
        Mail::send('email.notificacion', $params, function ($message) use($datos) {
                $message->subject($datos['asunto']);
                $message->to($datos['email']);
        });

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
