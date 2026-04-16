<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'active'
    ];

    public function models()
    {
        return $this->hasMany(VehicleModel::class);
    }
}
