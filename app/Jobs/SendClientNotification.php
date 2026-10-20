<?php

namespace App\Jobs;

use App\Services\ClientNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envía un aviso registrado. Sin reintentos automáticos: un fallo queda
 * registrado (failed) y se reenvía a mano.
 */
class SendClientNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly int $clientNotificationId
    ) {
    }

    public function handle(ClientNotificationService $service): void
    {
        $service->deliver($this->clientNotificationId);
    }
}
