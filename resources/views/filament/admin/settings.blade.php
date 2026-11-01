<x-filament-panels::page>
    <form wire:submit="save" class="max-w-2xl space-y-6">

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">GST/HST on management fees</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    When enabled, HST is added on top of the management fee and withheld from the owner payout
                    (<span class="font-mono">net&nbsp;=&nbsp;rent − fee − HST</span>). Leave off until your CRA
                    GST/HST registration is active.
                </p>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model="hst_enabled"
                       class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" style="width:18px;height:18px;">
                <span class="font-medium text-gray-800 dark:text-gray-100">Charge HST on management fees</span>
            </label>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">HST rate (%)</label>
                <input type="number" step="0.01" min="0" max="30" wire:model="hst_rate"
                       class="w-40 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ontario HST = 13%. Applied to the management fee only; residential rent itself is exempt.</p>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Outgoing mail (PostGrid)</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Controls the <span class="font-medium">Outgoing Mail</span> section of the admin panel.
                    <span class="font-medium">Test</span> never mails anything — it just generates a PDF proof from PostGrid.
                    <span class="font-medium">Live</span> prints and mails real letters through Canada Post and charges real postage.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sending mode</label>
                <select wire:model="postgrid_mode"
                        class="w-72 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                    <option value="test">Test — nothing is mailed</option>
                    <option value="live">Live — real letters are mailed</option>
                </select>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Keep on Test until you've confirmed a payment/postage method in PostGrid.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Test API key</label>
                    <input type="password" wire:model="postgrid_test_key" placeholder="test_sk_…" autocomplete="off"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Live API key</label>
                    <input type="password" wire:model="postgrid_live_key" placeholder="live_sk_…" autocomplete="off"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm font-mono">
                </div>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">From PostGrid: Dashboard → Settings → API Keys.</p>
        </div>

        <x-filament::button type="submit">
            Save settings
        </x-filament::button>

    </form>
</x-filament-panels::page>
