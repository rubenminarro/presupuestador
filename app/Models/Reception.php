<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reception extends Model
{
    protected $fillable = [
        'client_id',
        'vehicle_id',
        'reception_date',
        'estimated_delivery_date',
        'mileage',
        'fuel_level',
        'problem_description',
        'observations',
        'status',
        'created_by',
        'approved_by',
        'active',
    ];

    protected $casts = [
        'reception_date' => 'date',
        'estimated_delivery_date' => 'date',
        'active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
