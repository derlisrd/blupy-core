<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Device;
use App\Models\Notificacion;
use App\Services\EmailService;
use App\Services\PushExpoService;
use App\Services\SupabaseService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class CumpleaniosJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(){

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hoy = Carbon::now()->format('m-d');

        // Buscar clientes cuya fecha de nacimiento coincide con hoy
        $cumpleanieros = Cliente::whereRaw("DATE_FORMAT(fecha_nacimiento, '%m-%d') = ?", [$hoy])
        ->join('users as u','u.cliente_id','=','clientes.id')
        ->select('u.email','celular','u.id as uid','u.email','clientes.nombre_primero as nombre')
        ->get();
        $noti = new PushExpoService();
        $emailService = new EmailService();
        foreach ($cumpleanieros as $cliente) {
            $noti = Notificacion::create([
                'user_id' => $cliente->uid,
                'title' => 'Por tu Cumpleaños tenes un 30% de descuento',
                'body' => 'En Blupy, queremos desearte un feliz cumpleaños y agradecerte por ser parte de nuestra familia, y comunicarte que por tu cumpleaños tenes un 30% de descuento en tus compras de hoy!'
            ]);
            $devices = Device::where('user_id',$noti->user_id)->pluck('notitoken')->toArray();
            $emailService->enviarEmail($cliente->email,'Feliz cumpleaños','email.cumpleanios',['nombre'=>$cliente->nombre]);
           
            
        }
        SupabaseService::LOG('cumpleanios','Job');


    }
}
