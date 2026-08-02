<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Stripe\Event;
use Stripe\Stripe;
use Stripe\Account;

class StripeConnectWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.connect_webhook_secret');

        if ($endpointSecret) {
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload, $sigHeader, $endpointSecret
                );
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid signature.'], 400);
            }
        } else {
            // No secret configured — parse raw for development
            $event = json_decode($payload);
            if (!$event || !isset($event->type)) {
                return response()->json(['error' => 'Invalid payload.'], 400);
            }
        }

        switch ($event->type) {
            case 'account.updated':
                $this->handleAccountUpdated($event->data->object);
                break;

            case 'account.application.deauthorized':
                $this->handleAccountDeauthorized($event->data->object);
                break;

            default:
                // Unhandled event type
                break;
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleAccountUpdated(object $account): void
    {
        $user = User::where('stripe_account_id', $account->id)->first();
        if (!$user) {
            return;
        }

        $chargesEnabled = $account->charges_enabled ?? false;
        $payoutsEnabled = $account->payouts_enabled ?? false;
        $detailsSubmitted = $account->details_submitted ?? false;

        $onboardingComplete = $detailsSubmitted && ($chargesEnabled || $payoutsEnabled);

        if ($onboardingComplete !== $user->stripe_onboarding_complete) {
            $user->update([
                'stripe_onboarding_complete' => $onboardingComplete,
                'stripe_account_created_at' => $user->stripe_account_created_at ?? now(),
            ]);
        }
    }

    protected function handleAccountDeauthorized(object $account): void
    {
        User::where('stripe_account_id', $account->id)
            ->update([
                'stripe_onboarding_complete' => false,
            ]);
    }
}
