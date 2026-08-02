<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalApplication extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'email',
        'date_of_birth',
        'address_applying_for',
        'property_unit_id',
        'rental_history',
        'previous_address',
        'landlord_name',
        'landlord_contact',
        'gov_id_path',
        'proof_of_employment_path',
        'has_pets',
        'pets_details',
        'is_smoker',
        'consent_credit_check',
        'consent_background_check',
        'status',
    ];

    protected $casts = [
        'has_pets' => 'boolean',
        'is_smoker' => 'boolean',
        'consent_credit_check' => 'boolean',
        'consent_background_check' => 'boolean',
        'date_of_birth' => 'date',
    ];

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
