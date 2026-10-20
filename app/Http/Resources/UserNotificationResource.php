<?php

namespace App\Http\Resources;

use App\Enums\InternalNotificationType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Aviso de la bandeja interna (DatabaseNotification).
 */
class UserNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = InternalNotificationType::tryFrom($this->type);

        return [
            'id' => $this->id,
            'type' => [
                'value' => $this->type,
                'label' => $type?->label(),
            ],
            'title' => $this->data['title'] ?? null,
            'message' => $this->data['message'] ?? null,
            'subject' => $this->data['subject'] ?? null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
