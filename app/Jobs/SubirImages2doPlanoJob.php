<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class SubirImages2doPlanoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $imagenBase64;
    public $imageName;
    public $path;
    public $timeout = 120; // 2 minutos de timeout
    public $tries = 3; // Reintentar hasta 3 veces

    /**
     * Create a new job instance.
     */
    public function __construct(string $imagenBase64, string $imageName, string $path)
    {
        $this->imagenBase64 = $imagenBase64;
        $this->imageName = $imageName;
        $this->path = $path;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $filename = $this->processAndSaveImage();
            
        } catch (\Throwable $th) {
            
            throw $th; // Re-lanzar para que Laravel maneje los reintentos
        }
    }



    /**
     * Procesar y guardar la imagen
     */
    private function processAndSaveImage(): string
    {
        // Validar que sea una imagen base64 válida
        if (!preg_match('/^data:image\/(\w+);base64,/', $this->imagenBase64, $matches)) {
            throw new \Exception("Formato base64 no válido");
        }

        $originalExtension = strtolower($matches[1]);

        // Validar que la extensión sea permitida
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        if (!in_array($originalExtension, $allowedExtensions)) {
            throw new \Exception("Formato de imagen no permitido: {$originalExtension}");
        }

        // Remover el prefijo data:image/...;base64,
        $imageData = substr($this->imagenBase64, strpos($this->imagenBase64, ',') + 1);

        // Decodificar la imagen base64
        $decodedImage = base64_decode($imageData);

        if ($decodedImage === false) {
            throw new \Exception("Error al decodificar la imagen base64");
        }

        // Nombre del archivo con extensión .webp
        $filename = $this->imageName . '.webp';

        // Crear el directorio si no existe
        $fullDirectory = public_path($this->path);
        if (!file_exists($fullDirectory)) {
            if (!mkdir($fullDirectory, 0755, true) && !is_dir($fullDirectory)) {
                throw new \Exception("No se pudo crear el directorio: {$fullDirectory}");
            }
        }

        // Ruta completa del archivo
        $publicPath = public_path($this->path . '/' . $filename);

        // Procesar y convertir la imagen a WebP usando Intervention Image v3
        $manager = new ImageManager(new Driver());
        $imageProcessor = $manager->read($decodedImage);

        // Redimensionar manteniendo proporción (máximo 800 en cualquier lado)
        $imageProcessor->scaleDown(width: 800, height: 800);

        // Guardar la imagen procesada directamente como WebP
        $imageProcessor->toWebp(quality: 85)->save($publicPath);

        return $filename;
    }
}