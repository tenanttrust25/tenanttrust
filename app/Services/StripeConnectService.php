<?php

namespace App\Services;

use App\Models\Payout;
use App\Models\User;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;
use Stripe\Transfer;

class StripeConnectService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Express connected account for an owner.
     */
    public function createAccount(User $owner): ?Account
    {
        if ($owner->stripe_account_id) {
            try {
                return Account::retrieve($owner->stripe_account_id);
            } catch (\Exception $e) {
                // Account might be deleted, create a new one
            }
        }

        try {
            $account = Account::create([
                'type' => 'express',
                'country' => 'CA',
                'email' => $owner->email,
                'business_type' => 'individual',
                'business_profile' => [
                    'url' => config('app.url'),
                    'mcc' => '6513', // Real estate agents and managers
                ],
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'metadata' => [
                    'user_id' => $owner->id,
                    'user_name' => $owner->name,
                ],
            ]);

            $owner->update([
                'stripe_account_id' => $account->id,
                'stripe_account_created_at' => now(),
            ]);

            return $account;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Generate an onboarding link for the owner to complete Stripe Express setup.
     */
    public function getOnboardingLink(User $owner): ?string
    {
        $account = $this->createAccount($owner);
        if (!$account) {
            return null;
        }

        try {
            $link = AccountLink::create([
                'account' => $account->id,
                'refresh_url' => url('/owner/payout-settings'),
                'return_url' => url('/owner/payout-settings?onboarding=complete'),
                'type' => 'account_onboarding',
            ]);

            return $link->url;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    /**
     * Check if the owner's Stripe account is fully onboarded.
     */
    public function isOnboardingComplete(User $owner): bool
    {
        if (!$owner->stripe_account_id) {
            return false;
        }

        if ($owner->stripe_onboarding_complete) {
            return true;
        }

        try {
            $account = Account::retrieve($owner->stripe_account_id);

            $complete = $account->charges_enabled && $account->payouts_enabled;

            if ($complete) {
                $owner->update(['stripe_onboarding_complete' => true]);
            }

            return $complete;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Transfer funds from platform to connected account.
     * Returns the transfer object or null on failure.
     */
    public function transferToOwner(Payout $payout): ?Transfer
    {
        $owner = $payout->owner;
        if (!$owner || !$owner->stripe_account_id) {
            return null;
        }

        $amountInCents = (int) round($payout->net_amount * 100);
        if ($amountInCents <= 0) {
            return null;
        }

        try {
            $transfer = Transfer::create([
                'amount' => $amountInCents,
                'currency' => 'cad',
                'destination' => $owner->stripe_account_id,
                'transfer_group' => 'payout_' . $payout->id,
                'metadata' => [
                    'payout_id' => $payout->id,
                    'property_id' => $payout->property_id,
                    'invoice_id' => $payout->invoice_id,
                    'management_fee' => $payout->management_fee,
                ],
                'description' => 'Rent payout for ' . ($payout->property?->title ?? 'property') . ' - Invoice #' . ($payout->invoice?->invoice_number ?? 'N/A'),
            ]);

            $payout->update([
                'status' => 'paid',
                'paid_at' => now(),
                'notes' => ($payout->notes ? $payout->notes . "\n" : '') . 'Stripe transfer: ' . $transfer->id,
            ]);

            return $transfer;
        } catch (\Exception $e) {
            report($e);
            $payout->update([
                'notes' => ($payout->notes ? $payout->notes . "\n" : '') . 'Transfer failed: ' . $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Process multiple pending payouts for an owner.
     */
    public function processOwnerPayouts(User $owner): array
    {
        $results = [];
        $payouts = Payout::where('owner_id', $owner->id)
            ->where('status', 'pending')
            ->get();

        foreach ($payouts as $payout) {
            $transfer = $this->transferToOwner($payout);
            $results[] = [
                'payout_id' => $payout->id,
                'amount' => $payout->net_amount,
                'success' => $transfer !== null,
                'transfer_id' => $transfer?->id,
            ];
        }

        return $results;
    }
}
