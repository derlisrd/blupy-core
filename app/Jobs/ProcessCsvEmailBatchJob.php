<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessCsvEmailBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;

    public string $periodo;

    public function __construct(string $filePath, string $periodo)
    {
        $this->filePath = $filePath;
        $this->periodo = $periodo;
    }
    public function handle()
    {
        $fullPath = Storage::path($this->filePath);

        if (!file_exists($fullPath)) {
            return;
        }

        if (($handle = fopen($fullPath, 'r')) !== false) {
            // Obtener headers y limpiar caracteres invisibles o espacios
            $headers = fgetcsv($handle, 1000, ',');
            $headers = array_map(fn($header) => trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $header)), $headers);

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($headers) !== count($data)) {
                    continue; // Saltar filas mal formateadas
                }

                $row = array_combine($headers, array_map('trim', $data));

                // Despachar cada correo a la cola
                SendIndividualEmailJob::dispatch($row, $this->periodo);
            }
            fclose($handle);
        }

        // Eliminar el archivo temporal tras procesarlo
        Storage::delete($this->filePath);
    }
}
