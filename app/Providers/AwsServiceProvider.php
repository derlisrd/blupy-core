<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Aws\Rekognition\RekognitionClient;
class AwsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(RekognitionClient::class, function () {
            return new RekognitionClient([
                'credentials' => [
                    'key'    => config('services.aws.key'),
                    'secret' => config('services.aws.secret'),
                ],
                'region'  => config('services.aws.region'),
                'version' => 'latest',
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
