<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenancy extends Model
{
    protected $fillable = [
        "property_id",
        "property_unit_id",
        "unit",
        "tenant_id",
        "lease_start",
        "lease_end",
        "rent_amount",
        "security_deposit",
        "status",
        "notice_given_at",
        "move_out_date",
        "notes",
    ];

    protected $casts = [
        "lease_start" => "date",
        "lease_end" => "date",
        "notice_given_at" => "date",
        "move_out_date" => "date",
        "rent_amount" => "decimal:2",
        "security_deposit" => "decimal:2",
    ];

    protected static function booted(): void
    {
        static::creating(function (Tenancy $tenancy) {
            // Admin unit repeater creates tenancies with only tenant_id + property_unit_id;
            // backfill the NOT NULL columns so those saves don't 500.
            if (empty($tenancy->property_id) && $tenancy->property_unit_id) {
                $unit = $tenancy->propertyUnit;
                $tenancy->property_id = $unit?->property_id;

                if ($tenancy->lease_start === null) {
                    $tenancy->lease_start = now()->toDateString();
                }

                if ($tenancy->rent_amount === null && $unit) {
                    $tenancy->rent_amount = $unit->rent_amount;
                }
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, "property_unit_id");
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, "tenant_id");
    }

    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    public function scopeExpiringSoon($query, $days = 60)
    {
        return $query->where("status", "active")
            ->whereNotNull("lease_end")
            ->whereBetween("lease_end", [now(), now()->addDays($days)]);
    }
}
