<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, SoftDeletes;
    
    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name'
    ];

    protected $hidden = [
        'password',
    ];

    public function mechanic(): HasOne
    {
        return $this->hasOne(Mechanic::class);
    }

}
