<?php

namespace App\Notifications;

use App\Enums\InternalNotificationType;
use Illuminate\Notifications\Notification;

/**
 * Aviso de la bandeja interna. Se guarda sincrónicamente (canal database)
 * dentro de la transacción del evento: si la operación falla, no queda aviso.
 */
class WorkshopNotification extends Notification
{
    /**
     * @param array{type: string, id: int, code: ?string}|null $subject
     */
    public function __construct(
        public readonly InternalNotificationType $type,
        public readonly string $title,
        public readonly string $message,
        public readonly ?array $subject = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return $this->type->value;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'subject' => $this->subject,
        ];
    }
}
