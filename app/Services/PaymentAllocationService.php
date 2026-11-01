<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;

/**
 * Rolls an over-payment forward onto the tenant's later unpaid invoices.
 *
 * A tenant who pays more than a single month's rent (e.g. a lump-sum transfer)
 * gets the surplus applied to their other outstanding invoices, oldest first,
 * until the surplus runs out. Invoices settled this way are tagged with
 * `credit_source_payment_id` so the move is fully reversible when the payment
 * is edited or deleted.
 *
 * IMPORTANT: this is deliberately *event driven* — it only ever looks at the
 * one payment that changed, never the tenant's whole history. Tenants with
 * duplicate / mis-recorded payment rows can therefore never have their real
 * arrears wiped out by a blanket reconciliation.
 */
class PaymentAllocationService
{
    protected static bool $running = false;

    /** Payment created or updated. */
    public function handle(Payment $payment): void
    {
        if (static::$running) {
            return;
        }

        static::$running = true;
        try {
            $this->reverseSpillFor($payment);
            $this->syncOwnInvoice($payment);
            $this->spill($payment);
        } finally {
            static::$running = false;
        }
    }

    /** Payment deleted. */
    public function handleDeleted(Payment $payment): void
    {
        if (static::$running) {
            return;
        }

        static::$running = true;
        try {
            $this->reverseSpillFor($payment);
            $this->syncOwnInvoice($payment);
        } finally {
            static::$running = false;
        }
    }

    /**
     * Called when a new invoice is created: let any unapplied payment credit
     * settle it, so future months keep showing "paid" until the credit is used.
     */
    public function applyCreditToInvoice(Invoice $invoice): void
    {
        if (static::$running) {
            return;
        }

        if (! in_array($invoice->status, ['pending', 'overdue'], true)) {
            return;
        }

        $amount = (float) $invoice->amount;
        if ($amount <= 0) {
            return;
        }

        static::$running = true;
        try {
            $payments = Payment::where('tenant_id', $invoice->tenant_id)
                ->where('credit_remaining', '>', 0)
                ->orderBy('paid_at')
                ->orderBy('id')
                ->get();

            foreach ($payments as $payment) {
                if ((float) $payment->credit_remaining + 0.0001 < $amount) {
                    continue;
                }

                $invoice->status = 'paid';
                $invoice->credit_source_payment_id = $payment->id;
                $invoice->saveQuietly();

                $payment->credit_remaining = round((float) $payment->credit_remaining - $amount, 2);
                $payment->saveQuietly();

                break;
            }
        } finally {
            static::$running = false;
        }
    }

    /** Undo any invoices previously settled by rolled-forward credit from $payment. */
    protected function reverseSpillFor(Payment $payment): void
    {
        $invoices = Invoice::where('credit_source_payment_id', $payment->id)->get();

        foreach ($invoices as $invoice) {
            $invoice->credit_source_payment_id = null;

            $coverage = (float) $invoice->payments()->where('status', 'completed')->sum('amount');
            $invoice->status = $coverage >= (float) $invoice->amount
                ? 'paid'
                : $this->baseStatus($invoice);

            $invoice->saveQuietly();
        }
    }

    /** What an invoice's status should be ignoring any rolled-forward credit. */
    protected function baseStatus(Invoice $invoice): string
    {
        if ($invoice->status === 'cancelled') {
            return 'cancelled';
        }

        if ($invoice->due_date && $invoice->due_date->lt(now()->startOfDay())) {
            return 'overdue';
        }

        return 'pending';
    }

    /** Mark the payment's own invoice paid when covered; revert a normal "paid" when not. */
    protected function syncOwnInvoice(Payment $payment): void
    {
        if (! $payment->invoice_id) {
            return;
        }

        $invoice = Invoice::find($payment->invoice_id);
        if (! $invoice || $invoice->status === 'cancelled') {
            return;
        }

        $coverage = (float) $invoice->payments()->where('status', 'completed')->sum('amount');

        if ($coverage >= (float) $invoice->amount) {
            if ($invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->saveQuietly();
            }
        } elseif ($invoice->status === 'paid' && ! $invoice->credit_source_payment_id) {
            $invoice->status = $this->baseStatus($invoice);
            $invoice->saveQuietly();
        }
    }

    /** Apply this payment's surplus (over its own invoice) to later unpaid invoices. */
    protected function spill(Payment $payment): void
    {
        if ($payment->status !== 'completed' || ! $payment->invoice_id) {
            $this->setRemaining($payment, 0.0);

            return;
        }

        $own = Invoice::find($payment->invoice_id);
        if (! $own) {
            $this->setRemaining($payment, 0.0);

            return;
        }

        $others = (float) $own->payments()
            ->where('status', 'completed')
            ->where('id', '!=', $payment->id)
            ->sum('amount');

        $ownOutstanding = max(0.0, (float) $own->amount - $others);
        $remaining = max(0.0, (float) $payment->amount - $ownOutstanding);

        if ($remaining > 0) {
            $candidates = Invoice::where('tenant_id', $payment->tenant_id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->whereNull('credit_source_payment_id')
                ->where('id', '!=', $own->id)
                ->orderBy('due_date')
                ->orderBy('id')
                ->get();

            foreach ($candidates as $invoice) {
                $amount = (float) $invoice->amount;

                if ($amount <= 0) {
                    continue;
                }

                if ($remaining + 0.0001 < $amount) {
                    break;
                }

                $invoice->status = 'paid';
                $invoice->credit_source_payment_id = $payment->id;
                $invoice->saveQuietly();

                $remaining = round($remaining - $amount, 2);
            }
        }

        $this->setRemaining($payment, $remaining);
    }

    protected function setRemaining(Payment $payment, float $remaining): void
    {
        $remaining = round(max(0.0, $remaining), 2);

        if ((float) $payment->credit_remaining !== $remaining) {
            $payment->credit_remaining = $remaining;
            $payment->saveQuietly();
        }
    }
}
