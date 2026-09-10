<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReclamarDeudaMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;
    public string $periodo;

    public function __construct(array $data, string $periodo)
    {
        $this->data = $data;
        $this->periodo = $periodo;
    }

    public function build()
    {
        return $this->subject('Deuda a abonar en BLUPY periodo: ' . $this->periodo)
                    ->view('email.reclamopagoextracto')
                    ->with([
                        'nombre' => $this->data['nombre_completo'],
                        'periodo' => $this->periodo,
                        'pago_minimo' => number_format((float)$this->data['pago_minimo'], 0, ',', '.'),
                        'saldo' => number_format((float)$this->data['saldo'], 0, ',', '.'),
                        'atraso' => $this->data['atraso'],
                    ]);
    }
}