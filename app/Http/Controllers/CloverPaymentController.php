<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\CloverService;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CloverPaymentController extends Controller
{
    protected function canPayInvoice(Invoice $invoice): bool
    {
        $tenantIds = Auth::user()->getUnitTenantIds();
        return in_array($invoice->tenant_id, $tenantIds);
    }

    public function processCharge(Request $request)
    {
        $request->validate([
            "source_token" => "required|string",
            "invoice_id" => "required|integer|exists:invoices,id",
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        // Security check: ensure the authenticated user is a unit-mate
        if (!$this->canPayInvoice($invoice)) {
            return response()->json([
                "success" => false,
                "message" => "Unauthorized.",
            ], 403);
        }

        // Check invoice is payable
        if (!in_array($invoice->status, ["pending", "overdue"])) {
            return response()->json([
                "success" => false,
                "message" => "This invoice is already paid or cancelled.",
            ], 400);
        }

        $clover = new CloverService();
        $amountInCents = (int) round($invoice->amount * 100);

        $result = $clover->createCharge(
            $request->source_token,
            $amountInCents,
            "cad"
        );

        if (!$result["success"]) {
            return response()->json([
                "success" => false,
                "message" => $result["message"],
            ], 422);
        }

        // Payment successful — record it
        Payment::create([
            "invoice_id" => $invoice->id,
            "tenant_id" => Auth::id(),
            "amount" => $invoice->amount,
            "payment_method" => "credit",
            "transaction_id" => $result["charge_id"] ?? ("CC-" . strtoupper(Str::random(12))),
            "status" => "completed",
            "paid_at" => now(),
        ]);

        $invoice->update(["status" => "paid"]);

        // Auto-generate owner payout
        app(PayoutService::class)->processPaidInvoice($invoice);

        return response()->json([
            "success" => true,
            "message" => "Payment approved! Invoice {$invoice->invoice_number} has been paid.",
            "charge_id" => $result["charge_id"] ?? null,
        ]);
    }
}
