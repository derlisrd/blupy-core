<?php

namespace App\Jobs;


use App\Mail\ReclamarDeudaMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendIndividualEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $row;
    public string $periodo;
    public int $tries = 3;

    public function __construct(array $row, string $periodo)
    {
        $this->row = $row;
        $this->periodo = $periodo;
    }

    public function handle()
    {
        $email = trim($this->row['email']);

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Mail::to($email)->send(new ReclamarDeudaMail($this->row, $this->periodo));
        }
    }
}
