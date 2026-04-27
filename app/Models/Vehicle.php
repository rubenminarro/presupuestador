<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
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
        'active',
    ];

    protected $casts = [
        'no_plate' => 'boolean',
        'active' => 'boolean',
        'year' => 'integer',
        'mileage' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

     public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class);
    }
}
