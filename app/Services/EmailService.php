<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {

    }

    public function enviarEmail(string $email,$asunto, $view, array $params){
        try {
            $datos = [
                'email'=>$email,
                'asunto'=>$asunto
            ];
            Mail::send($view, $params, function ($message) use($datos) {
                $message->subject($datos['asunto']);
                $message->to($datos['email']);
            });
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
