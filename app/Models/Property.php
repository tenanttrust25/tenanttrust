<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        "owner_id",
        "title",
        "slug",
        "description",
        "price",
        "market_value",
        "address",
        "city",
        "province",
        "postal_code",
        "bedrooms",
        "bathrooms",
        "square_feet",
        "unit_count",
        "management_fee_type",
        "management_fee_value",
        "tags",
        "property_type",
        "property_subtype",
        "year_built",
        "status",
        "featured",
        "photo",
        "latitude",
        "longitude",
    ];

    protected $casts = [
        "featured" => "boolean",
        "price" => "decimal:2",
        "market_value" => "decimal:2",
        "unit_count" => "integer",
        "management_fee_value" => "decimal:2",
        "tags" => "array",
        "year_built" => "integer",
        "latitude" => "decimal:7",
        "longitude" => "decimal:7",
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, "owner_id");
    }

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class);
    }

    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class);
    }

    public function activeTenancy()
    {
        return $this->hasOne(Tenancy::class)->where("status", "active");
    }

    public function maintenanceTickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    public function scopeFeatured($query)
    {
        return $query->where("featured", true);
    }

    public function scopeForRent($query)
    {
        return $query->where("property_type", "for-rent");
    }

    public function scopeForSale($query)
    {
        return $query->where("property_type", "for-sale");
    }

    public function scopeOccupied($query)
    {
        return $query->where("status", "occupied");
    }

    public function scopeVacant($query)
    {
        return $query->where("status", "vacant");
    }

    public function managementFeeTotal(): ?float
    {
        // Property-level fee takes precedence over per-unit fees
        if ($this->management_fee_type && $this->management_fee_value !== null) {
            if ($this->management_fee_type === 'percentage') {
                $totalRent = (float) $this->units()->sum('rent_amount');
                return round($totalRent * ($this->management_fee_value / 100), 2);
            }

            return round((float) $this->management_fee_value, 2);
        }

        $unitFees = (float) $this->units()->sum('monthly_management_fee');
        if ($unitFees > 0) {
            return round($unitFees, 2);
        }

        return null;
    }

    public function getFormattedPriceAttribute(): string
    {
        $prefix = "$";
        $suffix = $this->property_type === "for-rent" ? "/mo" : "";

        if ($this->price >= 1000000) {
            return $prefix . number_format($this->price / 1000000, 1) . "M" . $suffix;
        }

        return $prefix . number_format($this->price) . $suffix;
    }

    public function getIsOccupiedAttribute(): bool
    {
        return $this->status === "occupied";
    }
}
