<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceItem extends Model
{
    protected $fillable = [
        'property_id',
        'property_unit_id',
        'type',
        'title',
        'due_date',
        'completed_at',
        'recurrence',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function propertyUnit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    public function scopeDue($query)
    {
        return $query->whereNull('completed_at')
            ->where('due_date', '<=', now()->toDateString());
    }

    public function scopeUpcoming($query, int $days = 60)
    {
        return $query->whereNull('completed_at')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->completed_at === null && $this->due_date && $this->due_date->isPast();
    }
}
