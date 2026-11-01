<?php

namespace App\Models;

use App\Services\PaymentAllocationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(fn (Payment $payment) => app(PaymentAllocationService::class)->handle($payment));
        static::updated(fn (Payment $payment) => app(PaymentAllocationService::class)->handle($payment));
        static::deleted(fn (Payment $payment) => app(PaymentAllocationService::class)->handleDeleted($payment));
    }

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'credit_remaining',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'credit_remaining' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
