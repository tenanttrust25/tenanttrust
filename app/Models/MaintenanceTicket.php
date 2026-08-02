<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        "tenant_id",
        "property_id",
        "property_unit_id",
        "assigned_to",
        "ticket_number",
        "title",
        "description",
        "priority",
        "status",
        "photos",
        "resolved_at",
        "category",
        "cost",
    ];

    protected $casts = [
        "photos" => "array",
        "resolved_at" => "datetime",
        "cost" => "decimal:2",
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, "tenant_id");
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, "property_unit_id");
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, "assigned_to");
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, "maintenance_ticket_id");
    }

    public static function generateTicketNumber(): string
    {
        $year = now()->format("Y");
        $month = now()->format("m");
        $last = static::whereYear("created_at", now()->year)
            ->whereMonth("created_at", now()->month)
            ->count();

        return sprintf("TKT-%s%s-%04d", $year, $month, $last + 1);
    }
}
