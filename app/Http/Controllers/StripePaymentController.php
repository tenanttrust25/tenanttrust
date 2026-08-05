<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PayoutService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class StripePaymentController extends Controller
{
    protected function canPayInvoice(Invoice $invoice): bool
    {
        $tenantIds = Auth::user()->getUnitTenantIds();
        return in_array($invoice->tenant_id, $tenantIds);
    }

    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            "invoice_id" => "required|integer|exists:invoices,id",
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        if (!$this->canPayInvoice($invoice)) {
            return response()->json(["error" => "Unauthorized."], 403);
        }

        if (!in_array($invoice->status, ["pending", "overdue"])) {
            return response()->json(["error" => "Invoice already paid or cancelled."], 400);
        }

        try {
            Stripe::setApiKey(config("services.stripe.secret"));

            $amountInCents = (int) round($invoice->amount * 100);

            $intent = PaymentIntent::create([
                "amount" => $amountInCents,
                "currency" => "cad",
                "metadata" => [
                    "invoice_id" => $invoice->id,
                    "invoice_number" => $invoice->invoice_number,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error("Stripe payment intent creation failed for invoice #{$invoice->invoice_number}: " . $e->getMessage());

            return response()->json([
                "error" => "Payment service is temporarily unavailable. Please try again later.",
            ], 503);
        }

        return response()->json([
            "client_secret" => $intent->client_secret,
            "amount" => $invoice->amount,
        ]);
    }

    public function confirmPayment(Request $request)
    {
        $request->validate([
            "invoice_id" => "required|integer|exists:invoices,id",
            "payment_intent_id" => "required|string",
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);

        if (!$this->canPayInvoice($invoice)) {
            return response()->json(["error" => "Unauthorized."], 403);
        }

        try {
            Stripe::setApiKey(config("services.stripe.secret"));
            $intent = PaymentIntent::retrieve($request->payment_intent_id);
        } catch (\Throwable $e) {
            Log::error("Stripe payment intent retrieval failed: " . $e->getMessage());

            return response()->json([
                "error" => "Payment service is temporarily unavailable. Please try again later.",
            ], 503);
        }

        if ($intent->status !== "succeeded") {
            return response()->json([
                "success" => false,
                "message" => "Payment not successful. Status: " . $intent->status,
            ], 422);
        }

        // Verify amount matches
        $expectedCents = (int) round($invoice->amount * 100);
        if ($intent->amount !== $expectedCents) {
            return response()->json([
                "success" => false,
                "message" => "Amount mismatch.",
            ], 422);
        }

        // Record payment
        Payment::create([
            "invoice_id" => $invoice->id,
            "tenant_id" => Auth::id(),
            "amount" => $invoice->amount,
            "payment_method" => "credit",
            "transaction_id" => $intent->id,
            "status" => "completed",
            "paid_at" => now(),
        ]);

        $invoice->update(["status" => "paid"]);

        // Push notification to tenant
        try {
            app(PushNotificationService::class)->sendToUser(
                Auth::user(),
                'Payment received — thank you!',
                "Your payment of $" . number_format($invoice->amount, 2) . " for invoice {$invoice->invoice_number} was received.",
                url('/tenant/invoices')
            );
        } catch (\Exception $e) {
            Log::warning("Payment push failed: " . $e->getMessage());
        }

        // Auto-generate owner payout
        app(PayoutService::class)->processPaidInvoice($invoice);

        return response()->json([
            "success" => true,
            "message" => "Invoice {$invoice->invoice_number} has been paid.",
        ]);
    }
}
