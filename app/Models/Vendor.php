<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'trade',
        'contact_name',
        'phone',
        'email',
        'rate',
        'insurance_expiry',
        'wsib',
        'notes',
        'active',
    ];

    protected $casts = [
        'insurance_expiry' => 'date',
        'wsib' => 'boolean',
        'active' => 'boolean',
    ];

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getInsuranceValidAttribute(): ?bool
    {
        if (! $this->insurance_expiry) {
            return null;
        }

        return $this->insurance_expiry->isFuture();
    }
}
