<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'notes',
        'active',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
