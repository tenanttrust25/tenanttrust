<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payout;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function calculateFee(Invoice $invoice): array
    {
        $property = $invoice->property;
        $propertyUnit = $invoice->propertyUnit;
        $rentAmount = $invoice->amount;

        $feeType = null;
        $feeRate = null;
        $feeAmount = 0;

        if ($property) {
            if ($property->management_fee_type && $property->management_fee_value !== null) {
                $feeType = $property->management_fee_type;
                $feeRate = $property->management_fee_value;

                if ($feeType === "percentage") {
                    $feeAmount = round($rentAmount * ($feeRate / 100), 2);
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

        return [
            "type" => $feeType,
            "rate" => $feeRate,
            "amount" => $feeAmount,
            "net" => round($rentAmount - $feeAmount, 2),
        ];
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
