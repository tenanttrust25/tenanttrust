<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            if ($payment->status === 'completed' && $payment->invoice_id) {
                $invoice = $payment->invoice()->first();
                if (! $invoice) {
                    return;
                }

                // Only mark the invoice as paid once the full amount has been covered.
                // Partial payments (e.g. on an overdue balance) keep the current status.
                $totalPaid = (float) $invoice->payments()
                    ->where('status', 'completed')
                    ->sum('amount');

                if ($totalPaid >= (float) $invoice->amount) {
                    $invoice->update(['status' => 'paid']);
                }
            }
        });
    }

    protected $fillable = [
        'invoice_id',
        'tenant_id',
        'amount',
        'payment_method',
        'transaction_id',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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
