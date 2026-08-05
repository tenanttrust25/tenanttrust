<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OwnerInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'property_id',
        'invoice_number',
        'amount',
        'description',
        'due_date',
        'status',
        'file_path',
        'payment_token',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OwnerInvoice $invoice) {
            if (empty($invoice->payment_token)) {
                $invoice->payment_token = Str::random(40);
            }
        });
    }

    public function getPayUrlAttribute(): string
    {
        return url('/invoice/pay/' . $this->payment_token);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
