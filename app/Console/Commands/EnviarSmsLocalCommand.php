<?php

namespace App\Console\Commands;

use App\Jobs\EnviarSmsLocalJob;
use App\Models\Cliente;
use Illuminate\Console\Command;

class EnviarSmsLocalCommand extends Command
{
    protected $signature = 'sms:enviar
                            {--delay=3 : Segundos de delay entre cada Job despachado}
                            {--chunk=50 : Cuántos clientes procesar por lote}
                            {--mensaje= : Mensaje a enviar (usa la plantilla por defecto si no se pasa)}';

    protected $description = 'Despacha Jobs para enviar SMS a todos los clientes';

    private string $mensajePorDefecto = 'Hola {{NOMBRE DE CLIENTE }} Hoy miércoles, hasta 30% de descuento con Blupy en todas nuestras sucursales. ¡Te esperamos!';

    public function handle(): int
    {
        $delaySegundos = (int) $this->option('delay');
        $chunk         = (int) $this->option('chunk');
        $mensaje       = $this->option('mensaje') ?? $this->mensajePorDefecto;

        $total = Cliente::whereNotNull('celular')->count();

        if ($total === 0) {
            $this->warn('No hay clientes con celular registrado.');
            return self::SUCCESS;
        }

        $this->info("Se van a despachar Jobs para {$total} clientes.");
        $this->info("Delay entre Jobs: {$delaySegundos}s | Chunk: {$chunk}");

        if (! $this->confirm('¿Continuar?', true)) {
            $this->line('Cancelado.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $acumulador = 0;

        Cliente::whereNotNull('celular')
            ->select(['id', 'nombre_primero', 'celular'])
            ->chunk($chunk, function ($clientes) use (&$acumulador, $delaySegundos, $mensaje, $bar) {
                foreach ($clientes as $cliente) {
                    $delay = $acumulador * $delaySegundos;

                EnviarSmsLocalJob::dispatch($cliente, $mensaje)
                        ->delay(now()->addSeconds($delay));

                    $acumulador++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();

        $tiempoTotal = $acumulador * $delaySegundos;
        $this->info("✓ {$acumulador} Jobs despachados. Tiempo estimado total: ~{$tiempoTotal}s (" . gmdate('H:i:s', $tiempoTotal) . ")");

        return self::SUCCESS;
    }
}
