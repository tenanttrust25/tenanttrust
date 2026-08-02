<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $fillable = [
        'property_id',
        'owner_id',
        'invoice_id',
        'rent_amount',
        'management_fee',
        'net_amount',
        'management_fee_type',
        'management_fee_rate',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'rent_amount' => 'decimal:2',
        'management_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'management_fee_rate' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
