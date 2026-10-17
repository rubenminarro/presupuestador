<?php

namespace App\Models;

use App\Enums\FuelLevel;
use App\Enums\VehicleDeliveryStatus;
use App\Exceptions\VehicleDeliveryException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Entrega del vehículo al cliente. Es un comprobante: no se edita ni se
 * elimina, solo se anula.
 */
class VehicleDelivery extends Model
{
    /**
     * Campos que pueden cambiar una vez registrada (solo la anulación).
     */
    private const CANCELLATION_FIELDS = ['status', 'cancelled_at', 'cancelled_by', 'cancellation_reason', 'updated_at'];

    protected $fillable = [
        'reception_id',
        'vehicle_id',
        'client_id',
        'delivery_sequence',
        'delivery_number',
        'status',
        'delivered_at',
        'delivered_by',
        'final_mileage',
        'fuel_level',
        'received_by_name',
        'received_by_document',
        'customer_accepted',
        'signature_path',
        'observations',
        'previous_reception_status',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => VehicleDeliveryStatus::class,
            'fuel_level' => FuelLevel::class,
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'final_mileage' => 'integer',
            'customer_accepted' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (VehicleDelivery $delivery) {
            $original = VehicleDeliveryStatus::from($delivery->getRawOriginal('status'));
            $changed = array_diff(array_keys($delivery->getDirty()), self::CANCELLATION_FIELDS);

            if ($original === VehicleDeliveryStatus::CANCELLED || $changed !== []) {
                throw new VehicleDeliveryException(
                    'La entrega registrada no puede modificarse; solo puede anularse.',
                    409,
                    'DELIVERY_NOT_EDITABLE'
                );
            }
        });

        static::deleting(function () {
            throw new VehicleDeliveryException(
                'Las entregas no se eliminan; deben anularse.',
                409,
                'DELIVERY_NOT_EDITABLE'
            );
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VehicleDeliveryStatus::DELIVERED);
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null;
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(Reception::class)->withTrashed();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function deliverer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
