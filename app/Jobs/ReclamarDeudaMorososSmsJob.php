<?php

namespace App\Jobs;

use App\Services\SupabaseService;
use App\Services\TigoSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Storage;

class ReclamarDeudaMorososSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $mensaje;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $mensaje)
    {
        $this->filePath = $filePath;
        $this->mensaje = $mensaje;
        // Puedes asignar una cola específica aquí si quieres aislar el proceso
        //$this->onQueue('sms_masivo');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // NOTA DE CORRECCIÓN: $enviarMensajesA debe ser inicializado aquí 
        // y pasado por referencia a la closure 'each'.
        $numerosParaLote = []; 
        
        try {
            LazyCollection::make(function () {
                // Lee el archivo desde el disco de almacenamiento 'local'
                $handle = Storage::disk('local')->readStream($this->filePath);

                if ($handle) {
                    // Ignoramos la primera línea si es el encabezado
                    // fgetcsv($handle); 
                    while (($line = fgetcsv($handle)) !== false) {
                        yield $line;
                    }
                    fclose($handle);
                }
            })
            ->chunk(500) // Procesa 500 cédulas a la vez
            ->each(function (LazyCollection $chunk) use (&$numerosParaLote) { // ¡Uso de &!
                
                // 1. Obtener las cédulas del chunk actual
                $cedulas = $chunk->map(fn($row) => trim($row[0])) 
                                 ->filter()
                                 ->toArray();
                
                if (empty($cedulas)) {
                    return;
                }

                // 2. Consulta a la Base de Datos para obtener celulares
                $clientes = DB::table('clientes')
                    ->select('celular', 'cedula')
                    ->whereIn('cedula', $cedulas)
                    ->get();
                
                // 3. Acumular números de celular en el array pasado por referencia
                foreach ($clientes as $cliente) {
                    if (!empty($cliente->celular)) {
                       $numerosParaLote[] = $cliente->celular;
                    }
                }
                
                // 4. Enviar el lote si se encontraron números y luego limpiar el array
                if (!empty($numerosParaLote)) {
                     // Llama al método de envío masivo con el array acumulado
                     $this->sendSms($numerosParaLote);
                     // CRUCIAL: Limpiar el array para el siguiente chunk
                     $numerosParaLote = [];
                }
            });
            
        } catch (\Throwable $e) {
            // Manejo de errores durante la lectura o consulta
             SupabaseService::LOG('Error fatal en Job ReclamarDeudaMorososSmsJob', 
                'Error: ' . $e->getMessage() 
            );
        } finally {
            // 5. Limpieza: Elimina el archivo CSV después de procesar, ¡incluso si falló!
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
                SupabaseService::LOG("Archivo CSV temporal eliminado", " {$this->filePath}");
            }
        }
    }


    private function sendSms(array $numeros): array
    {
        $tigoSmsService = new TigoSmsService();
        $enviados = 0;
        $fallidos = 0;

        try {
            // Rate limiting antes de enviar
            $this->rateLimit(count($numeros));
            
            // Usar el método masivo con concurrencia
            $respuestas = $tigoSmsService->enviarSmsMasivo($numeros, $this->mensaje, 10);
            
            // Procesar respuestas
            foreach ($respuestas as $index => $respuesta) {
                if ($respuesta->successful()) {
                    $enviados++;
                } else {
                    $fallidos++;
                    SupabaseService::LOG("JOBS morosos sms","SMS fallido al número index {$index}");
                }
            }
            
        } catch (\Throwable $th) {
            $fallidos = count($numeros);
            SupabaseService::LOG(
                'Error enviando lote de SMS', 
                'Total números: ' . count($numeros) . ', Error: ' . $th->getMessage() 
            );
        }

        SupabaseService::LOG('Lote de SMS procesado', 
            'total: ' . count($numeros) .
            ' enviados: ' . $enviados .
            ' fallidos: ' . $fallidos
        );

        return [$enviados, $fallidos];
    }


    private function rateLimit(int $cantidad = 1)
    {
        $key = 'sms_rate_limit';
        $maxPerMinute = 30;
        
        $attempts = Cache::get($key, 0);
        
        // Si excedemos el límite con esta cantidad, esperamos
        if (($attempts + $cantidad) > $maxPerMinute) {
            $waitSeconds = 10;
            Log::info("Rate limit alcanzado, esperando {$waitSeconds} segundos...");
            sleep($waitSeconds);
            Cache::put($key, $cantidad, 60);
        } else {
            Cache::increment($key, $cantidad);
            
            if ($attempts === 0) {
                Cache::put($key, $cantidad, 60);
            }
        }
    }
}