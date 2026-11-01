<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payout;
use App\Models\Property;
use App\Support\TenantTrustSettings;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function calculateFee(Invoice $invoice): array
    {
        $property = $invoice->property;
        $propertyUnit = $invoice->propertyUnit;
        $rentAmount = $invoice->amount;

        // Management fee applies to RENT invoices only. Parking/Other carry no fee.
        if (($invoice->type ?? 'rent') !== 'rent') {
            return [
                "type" => "none",
                "rate" => 0,
                "amount" => 0.0,
                "tax_rate" => null,
                "tax" => 0.0,
                "total" => 0.0,
                "net" => round($rentAmount, 2),
            ];
        }

        $feeType = null;
        $feeRate = null;
        $feeAmount = 0;

        if ($property) {
            if ($property->management_fee_type && $property->management_fee_value !== null) {
                $feeType = $property->management_fee_type;
                $feeRate = $property->management_fee_value;

                if ($feeType === "percentage") {
                    $feeAmount = round($rentAmount * ($feeRate / 100), 2);
                } elseif ($feeType === "flat_monthly") {
                    // Per-property flat charged once per calendar month, on the
                    // first rent payment received for that property in the month.
                    // Subsequent unit invoices in the same month carry no fee.
                    $feeAmount = $this->isFirstFeeForPropertyThisMonth($property->id, $invoice)
                        ? (float) $feeRate
                        : 0.0;
                } else {
                    $feeAmount = $feeRate;
                }
            }
        }

        if ($feeType === null && $propertyUnit && $propertyUnit->monthly_management_fee > 0) {
            $feeType = "flat";
            $feeRate = $propertyUnit->monthly_management_fee;
            $feeAmount = $feeRate;
        }

        if ($feeType === null) {
            $feeType = "percentage";
            $feeRate = 8.00;
            $feeAmount = round($rentAmount * 0.08, 2);
        }

        // GST/HST on the management fee (off unless enabled in Admin > Settings).
        $hstEnabled = TenantTrustSettings::hstEnabled();
        $hstRate = TenantTrustSettings::hstRate();
        $taxAmount = ($hstEnabled && $feeAmount > 0)
            ? round($feeAmount * ($hstRate / 100), 2)
            : 0.0;

        return [
            "type" => $feeType,
            "rate" => $feeRate,
            "amount" => $feeAmount,
            "tax_rate" => $hstEnabled ? $hstRate : null,
            "tax" => $taxAmount,
            "total" => round($feeAmount + $taxAmount, 2),
            "net" => round($rentAmount - $feeAmount - $taxAmount, 2),
        ];
    }

    /**
     * True when no payout has yet been recorded for this property in the rent
     * month of the given invoice. Backs the "flat_monthly" fee type so a
     * per-property flat is charged exactly once per month.
     */
    protected function isFirstFeeForPropertyThisMonth(int $propertyId, Invoice $invoice): bool
    {
        $useDueDate = ! empty($invoice->due_date);
        $ref = $useDueDate ? $invoice->due_date : $invoice->created_at;

        if (! $ref) {
            return true;
        }

        $ref = \Illuminate\Support\Carbon::parse($ref);
        $column = $useDueDate ? 'due_date' : 'created_at';

        return ! Payout::where('property_id', $propertyId)
            ->whereHas('invoice', function ($query) use ($ref, $column) {
                $query->whereYear($column, $ref->year)
                    ->whereMonth($column, $ref->month);
            })
            ->exists();
    }

    public function processPaidInvoice(Invoice $invoice): ?Payout
    {
        $property = $invoice->property;
        if (!$property || !$property->owner_id) {
            return null;
        }

        $fee = $this->calculateFee($invoice);

        return DB::transaction(function () use ($invoice, $property, $fee) {
            $payout = Payout::create([
                "property_id" => $property->id,
                "owner_id" => $property->owner_id,
                "invoice_id" => $invoice->id,
                "rent_amount" => $invoice->amount,
                "management_fee" => $fee["amount"],
                "tax_amount" => $fee["tax"],
                "tax_rate" => $fee["tax_rate"],
                "net_amount" => $fee["net"],
                "management_fee_type" => $fee["type"],
                "management_fee_rate" => $fee["rate"],
                "status" => "pending",
            ]);

            // Auto-transfer via Stripe Connect if owner is onboarded
            $this->autoTransferIfEligible($payout);

            return $payout;
        });
    }

    public function autoTransferIfEligible(Payout $payout): bool
    {
        $owner = $payout->owner;
        if (!$owner || !$owner->stripe_account_id || !$owner->stripe_onboarding_complete) {
            return false;
        }

        $stripeService = app(StripeConnectService::class);
        $transfer = $stripeService->transferToOwner($payout);

        return $transfer !== null;
    }

    public function markAsPaid(Payout $payout, ?string $notes = null): Payout
    {
        $payout->update([
            "status" => "paid",
            "paid_at" => now(),
            "notes" => $notes ? $payout->notes . "\n" . $notes : $payout->notes,
        ]);

        return $payout->fresh();
    }

    public function getOwnerPendingTotal(int $ownerId): float
    {
        return Payout::where("owner_id", $ownerId)
            ->where("status", "pending")
            ->sum("net_amount");
    }

    public function getTotalFeesCollected($startDate = null, $endDate = null): float
    {
        $query = Payout::where("status", "paid");
        if ($startDate) $query->where("paid_at", ">=", $startDate);
        if ($endDate) $query->where("paid_at", "<=", $endDate);
        return $query->sum("management_fee");
    }
}
