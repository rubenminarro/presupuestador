<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'document_number',
        'first_name',
        'last_name',
        'phone',
        'email',
        'notifications_enabled',
        'notes',
    ];

    protected $attributes = [
        'notifications_enabled' => true,
    ];

    protected function casts(): array
    {
        return [
            'notifications_enabled' => 'boolean',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }

    public function clientNotifications(): HasMany
    {
        return $this->hasMany(ClientNotification::class);
    }
}