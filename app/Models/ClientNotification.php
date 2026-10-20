<?php

namespace App\Models;

use App\Enums\ClientNotificationStatus;
use App\Enums\ClientNotificationType;
use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Aviso a un cliente (registro de cada envío). No se reintenta: se reenvía
 * creando otro registro (resent_from_id).
 */
class ClientNotification extends Model
{
    protected $fillable = [
        'client_id',
        'type',
        'channel',
        'subject_type',
        'subject_id',
        'recipient',
        'status',
        'error',
        'attempts',
        'sent_at',
        'dedupe_key',
        'resent_from_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ClientNotificationType::class,
            'channel' => NotificationChannel::class,
            'status' => ClientNotificationStatus::class,
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function resentFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'resent_from_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
