<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyUnit extends Model
{
    protected static function booted(): void
    {
        // The rent_amount column is NOT NULL (default 0.00). Guard every save
        // path so clearing the field / setting a unit vacant stores 0 instead
        // of null (which would throw an integrity constraint violation).
        static::saving(function (PropertyUnit $unit) {
            if ($unit->rent_amount === null) {
                $unit->rent_amount = 0;
            }
            if ($unit->status === null) {
                $unit->status = 'vacant';
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, "tenant_id");
    }

    public function tenant2(): BelongsTo
    {
        return $this->belongsTo(User::class, "tenant2_id");
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenancy::class, "property_unit_id");
    }

    protected $fillable = [
        'property_id',
        'unit',
        'bedrooms',
        'bathrooms',
        'square_feet',
        'rent_amount',
        'monthly_management_fee',
        'status',
        'description',
        'amenities',
        'slug',
        'tenant_id',
        'tenant2_id',
        'photo',
        'videos',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'monthly_management_fee' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'square_feet' => 'integer',
        'photo' => 'array',
        'videos' => 'array',
        'amenities' => 'array',
    ];
}
