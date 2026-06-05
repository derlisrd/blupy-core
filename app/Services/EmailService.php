<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class EmailService
{

    public function __construct()
    {

    }

    public function enviarEmail(string $email,string $asunto, $view, array $params){
        $datos = [
            'email' => $email,
            'asunto' => $asunto
        ];

        try {
            
            Mail::send($view, $params, function ($message) use($datos) {
                $message->subject($datos['asunto']);
                $message->to($datos['email']);
            });
        } catch (\Throwable $th) {
            SupabaseService::LOG('Error SMTP mail: ', $th->getMessage());
            Mail::mailer('gmail')->send($view, $params, function ($message) use ($datos) {
                $message->subject($datos['asunto']);
                $message->to($datos['email']);
            });
            try {
            } catch (\Throwable $gmailError) {
                //SupabaseService::LOG('email registro', $e->getMessage());
                SupabaseService::LOG('Error SMTP Gmail: ', $gmailError->getMessage());
                throw $gmailError;
            }
        }
    }

    
}
