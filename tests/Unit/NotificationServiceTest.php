<?php

namespace Tests\Unit;

use App\Services\NotificationService;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;

class NotificationServiceTest extends TestCase
{
    #[Test]
    public function it_can_send_notification()
    {
        // Instanciar el servicio
        $service = new NotificationService();
    }

    #[Test]
    public function it_handles_error_when_sending_notification()
    {
        // Mockear un error de respuesta
    }
}