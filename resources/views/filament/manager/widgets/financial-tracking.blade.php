<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>💰 Financial Tracking</span>
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    YTD Net: <strong class="{{ $netIncome >= 0 ? "text-success-600" : "text-danger-600" }}">${{ number_format($netIncome) }}</strong>
                </span>
            </div>
        </x-slot>

        {{-- Rent Collection Overview --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Rent Collection — {{ $activeLeases }} active leases</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Expected Monthly Rent</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($monthlyRentRoll) }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">From {{ $activeLeases }} active lease(s)</div>
                </div>
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Collected This Month</div>
                    <div class="mt-1 text-2xl font-bold text-success-600">${{ number_format($collectedRent) }}</div>
                    <div class="mt-1 h-2 w-full rounded-full bg-gray-200">
                        <div class="h-2 rounded-full bg-success-500" style="width: {{ $collectionRate }}%"></div>
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $collectionRate }}% collection rate</div>
                </div>
                <div class="rounded-xl border border-orange-200 bg-orange-50 dark:bg-gray-700 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wide text-orange-600">Pending</div>
                    <div class="mt-1 text-2xl font-bold text-orange-600">${{ number_format($pendingAmount) }}</div>
                    <div class="mt-1 text-xs text-orange-500">{{ $pendingCount }} invoice(s)</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-gray-700 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase tracking-wide text-red-600">Overdue / Arrears</div>
                    <div class="mt-1 text-2xl font-bold text-red-600">${{ number_format($overdueAmount) }}</div>
                    <div class="mt-1 text-xs text-red-500">{{ $overdueCount }} invoice(s)</div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
