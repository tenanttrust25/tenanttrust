<?php

namespace App\Filament\Owner\Pages;

use App\Services\StripeConnectService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class PayoutSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $title = 'Payout Settings';

    protected static ?string $navigationLabel = 'Payout Settings';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.owner.payout-settings';

    public $accountId;
    public $onboardingComplete;
    public $bankInfo;
    public $pendingPayouts;
    public $pendingTotal;

    public function mount(): void
    {
        $user = Auth::user();
        $this->accountId = $user->stripe_account_id;
        $this->onboardingComplete = $user->stripe_onboarding_complete;

        // Check with Stripe if onboarding is complete
        if ($this->accountId && !$this->onboardingComplete) {
            $service = app(StripeConnectService::class);
            $this->onboardingComplete = $service->isOnboardingComplete($user);
        }

        $this->pendingPayouts = \App\Models\Payout::where('owner_id', $user->id)
            ->where('status', 'pending')->count();
        $this->pendingTotal = \App\Models\Payout::where('owner_id', $user->id)
            ->where('status', 'pending')->sum('net_amount');
    }

    public function startOnboarding(): void
    {
        $user = Auth::user();
        $service = app(StripeConnectService::class);
        $link = $service->getOnboardingLink($user);

        if ($link) {
            $this->redirect($link);
        } else {
            Notification::make()
                ->title('Failed to create onboarding link. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function refreshStatus(): void
    {
        $user = Auth::user();
        $service = app(StripeConnectService::class);
        $this->onboardingComplete = $service->isOnboardingComplete($user);

        if ($this->onboardingComplete) {
            Notification::make()
                ->title('Bank account connected!')
                ->success()
                ->send();
        }
    }
}
