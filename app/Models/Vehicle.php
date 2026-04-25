<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'client_id',
        'brand_id',
        'brand_model_id',
        'chassis',
        'plate',
        'no_plate',
        'color',
        'notes',
        'active',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

     public function brandModel()
    {
        return $this->belongsTo(
            BrandModel::class,
            'brand_model_id'
        );
    }
}
