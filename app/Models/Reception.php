<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reception extends Model
{
    
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'vehicle_id',
        'service_type',
        'reception_date',
        'estimated_delivery_date',
        'mileage',
        'fuel_level',
        'problem_description',
        'observations',
        'status',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'reception_date' => 'date',
        'estimated_delivery_date' => 'date',
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

    public function checkList(): HasOne
    {
        return $this->hasOne(ReceptionCheckList::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ReceptionPhoto::class);
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

}
