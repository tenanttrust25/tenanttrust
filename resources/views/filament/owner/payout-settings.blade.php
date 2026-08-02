<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Connection Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Bank Account Connection</h3>

            @if($onboardingComplete)
                <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-green-800 dark:text-green-200">Bank Account Connected</p>
                        <p class="text-sm text-green-600 dark:text-green-400">
                            Your bank account is linked. Payouts will be sent automatically.
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex gap-3">
                    <a href="https://dashboard.stripe.com/express/{{ $accountId }}"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Manage Bank Account on Stripe
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                    <button wire:click="refreshStatus"
                            class="inline-flex items-center px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                        Refresh Status
                    </button>
                </div>
            @elseif($accountId)
                <div class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 dark:bg-yellow-800 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-yellow-800 dark:text-yellow-200">Onboarding Incomplete</p>
                        <p class="text-sm text-yellow-600 dark:text-yellow-400">
                            You started linking a bank account but haven't finished. Please complete the setup.
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <button wire:click="startOnboarding"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg font-semibold text-sm text-gray-900"
                            style="background: #DCA54A;">
                        Continue Bank Account Setup
                    </button>
                </div>
            @else
                <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/20 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex-shrink-0 w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 dark:text-gray-300">No Bank Account Connected</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Link a bank account to receive automatic payouts when rent is collected.
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <button wire:click="startOnboarding"
                            class="inline-flex items-center px-5 py-2.5 rounded-lg font-semibold text-sm text-gray-900"
                            style="background: #DCA54A;">
                        Connect Bank Account
                    </button>
                </div>
            @endif
        </div>

        {{-- Pending Payouts Info --}}
        @if($pendingPayouts > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Pending Payouts</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    You have <strong>{{ $pendingPayouts }} pending payout(s)</strong> totaling
                    <strong>${{ number_format($pendingTotal, 2) }}</strong>.
                    @if(!$onboardingComplete)
                        Connect your bank account above to receive these automatically.
                    @else
                        These will be paid out to your connected bank account.
                    @endif
                </p>
                <a href="{{ url('/owner/payouts') }}"
                   class="text-sm font-medium hover:underline"
                   style="color: #DCA54A;">
                    View Payout History &rarr;
                </a>
            </div>
        @endif

        {{-- Info Box --}}
        <div class="bg-blue-50 dark:bg-blue-900/10 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-700 dark:text-blue-300">
                <strong>How it works:</strong> When your tenants pay rent, Tenant Trust deducts the management fee
                and sends the remaining balance to your connected bank account. Payouts are processed
                automatically through Stripe, usually arriving within 2-5 business days.
            </p>
        </div>

    </div>
</x-filament-panels::page>
