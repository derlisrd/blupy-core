<?php

namespace App\Jobs;

use App\Services\WaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SolicitudAprobadaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $celular;
    /**
     * Create a new job instance.
     */
    public function __construct($text,$email,$celular)
    {
        $this->email = $email;
        $this->celular = $celular;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
        $params = [];
        $titulo = '¡Crédito aprobado, felicidades! 🎉 ';
        $descripcion = '¡Recuerda! Tienes hasta 30 días para activar tu línea. Puedes hacerlo en el Punto Farma más cercano. ¡Te esperamos!';
        $numeroTelefonoWa = '595' . substr($this->celular, 1);
        (new WaService())->send($numeroTelefonoWa, $titulo . $descripcion);

        $datos = ['asunto'=>'Contrato de línea de crédito Blupy.','email'=>$this->email];
        Mail::send('email.contrato', $params, function ($message) use($datos) {
                $message->subject($datos['asunto']);
                $message->to($datos['email']);
        });
        Log::info('Correo enviado');
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            throw $th;

        }
    }
}
