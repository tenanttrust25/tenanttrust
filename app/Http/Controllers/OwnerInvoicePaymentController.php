<?php

namespace App\Http\Controllers;

use App\Models\OwnerInvoice;
use App\Models\PropertyUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

class OwnerInvoicePaymentController extends Controller
{
    /**
     * Public invoice payment page (tokenized — no login required).
     */
    public function show(string $token)
    {
        $invoice = OwnerInvoice::where('payment_token', $token)->firstOrFail();

        return view('owner-invoice-pay', [
            'invoice' => $invoice,
            'paid' => false,
            'cancelled' => false,
            'breakdown' => $this->buildBreakdown($invoice),
        ]);
    }

    /**
     * Per-unit management fee breakdown for the invoice's property.
     * Returns null when there's no property or no units carry a fee.
     */
    private function buildBreakdown(OwnerInvoice $invoice): ?array
    {
        if (! $invoice->property) {
            return null;
        }

        $units = $invoice->property->units()
            ->whereNotNull('monthly_management_fee')
            ->orderByRaw('unit + 0')
            ->get();

        if ($units->isEmpty()) {
            return null;
        }

        return [
            'items' => $units->map(fn (PropertyUnit $u) => [
                'label' => 'Unit ' . $u->unit . ($u->tenant ? ' — ' . $u->tenant->name : ''),
                'amount' => (float) $u->monthly_management_fee,
            ])->values()->all(),
        ];
    }

    /**
     * Create a Stripe Checkout Session and redirect the client to Stripe.
     */
    public function checkout(string $token)
    {
        $invoice = OwnerInvoice::where('payment_token', $token)->firstOrFail();

        if (! in_array($invoice->status, ['pending', 'overdue'])) {
            return redirect()->route('invoice.pay.show', $token);
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $amountCents = (int) round($invoice->amount * 100);

            $session = StripeCheckoutSession::create([
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'cad',
                        'unit_amount' => $amountCents,
                        'product_data' => [
                            'name' => 'Invoice #' . $invoice->invoice_number,
                            'description' => 'Tenant Trust invoice',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'owner_invoice_id' => (string) $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
                'success_url' => route('invoice.pay.success', $token) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('invoice.pay.cancel', $token),
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout session creation failed for invoice #' . $invoice->invoice_number . ': ' . $e->getMessage());

            return redirect()->route('invoice.pay.show', $token)
                ->with('error', 'Payment is temporarily unavailable. Please try again shortly or contact Tenant Trust.');
        }

        return redirect()->away($session->url);
    }

    /**
     * Stripe redirects here after payment. Verify the session, mark invoice paid.
     */
    public function success(Request $request, string $token)
    {
        $invoice = OwnerInvoice::where('payment_token', $token)->firstOrFail();
        $paid = false;

        $sessionId = $request->query('session_id');

        if ($sessionId) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));
                $session = StripeCheckoutSession::retrieve($sessionId);

                $sessionIsForThisInvoice =
                    ($session->metadata['owner_invoice_id'] ?? null) === (string) $invoice->id;

                if ($session->payment_status === 'paid' && $sessionIsForThisInvoice) {
                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                    $paid = true;
                }
            } catch (\Throwable $e) {
                Log::warning("Owner invoice payment verification failed: " . $e->getMessage());
            }
        }

        return view('owner-invoice-pay', [
            'invoice' => $invoice,
            'paid' => $paid,
            'cancelled' => false,
            'breakdown' => $this->buildBreakdown($invoice),
        ]);
    }

    /**
     * Client abandoned/cancelled the Stripe payment.
     */
    public function cancel(string $token)
    {
        $invoice = OwnerInvoice::where('payment_token', $token)->firstOrFail();

        return view('owner-invoice-pay', [
            'invoice' => $invoice,
            'paid' => false,
            'cancelled' => true,
            'breakdown' => $this->buildBreakdown($invoice),
        ]);
    }
}
