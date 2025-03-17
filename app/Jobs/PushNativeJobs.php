<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PushNativeJobs implements ShouldQueue
{
    use Queueable;
    public $text;
    public $title;
    public $tokens;
    public $type;

    /**
     * Create a new job instance.
     */
    public function __construct($title,$text,$tokens,$type)
    {
        $this->text = $text;
        $this->title = $title;
        $this->tokens = $tokens;
        $this->type = $type;
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
