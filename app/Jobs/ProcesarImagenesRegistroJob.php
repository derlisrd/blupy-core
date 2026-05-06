<?php

namespace App\Jobs;

use App\Services\SupabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProcesarImagenesRegistroJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        private string $imagenBase64,
        private string $imageName,
        private string $path,
    ) {}

    public function handle(): void
    {
        try {
            if (!preg_match('/^data:image\/(\w+);base64,/', $this->imagenBase64, $matches)) {
                throw new \Exception("Formato base64 no válido");
            }

            $originalExtension = strtolower($matches[1]);
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($originalExtension, $allowedExtensions)) {
                throw new \Exception("Formato de imagen no permitido: {$originalExtension}");
            }

            $imageData   = substr($this->imagenBase64, strpos($this->imagenBase64, ',') + 1);
            $decodedImage = base64_decode($imageData);

            if ($decodedImage === false) {
                throw new \Exception("Error al decodificar la imagen base64");
            }

            $filename    = $this->imageName . '.webp';
            $fullDirectory = public_path($this->path);

            if (!file_exists($fullDirectory)) {
                mkdir($fullDirectory, 0755, true);
            }

            $publicPath = public_path($this->path . '/' . $filename);

            $manager = new ImageManager(new Driver());
            $imageProcessor = $manager->read($decodedImage);
            $imageProcessor->scaleDown(width: 800, height: 800);
            $imageProcessor->toWebp(quality: 90)->save($publicPath);
        } catch (\Throwable $th) {
            SupabaseService::LOG('Error ProcesarImagenesRegistroJob: ' . $this->imageName, $th->getMessage());
            throw $th; // re-throw para que el job reintente
        }
    }

    public function failed(\Throwable $exception): void
    {
        SupabaseService::LOG('Job falló definitivamente: ' . $this->imageName, $exception->getMessage());
    }
}
