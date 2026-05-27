<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'brand_id',
        'vehicle_model_id',
        'chassis',
        'plate',
        'no_plate',
        'year',
        'color',
        'engine_number',
        'mileage',
        'fuel_type',
        'transmission',
        'notes',
    ];

    protected $casts = [
        'no_plate' => 'boolean',
        'year' => 'integer',
        'mileage' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    public function receptions(): HasMany
    {
        return $this->hasMany(Reception::class);
    }
}
