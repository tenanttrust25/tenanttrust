<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Summary --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Active Tenants</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $count }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Rent Due</div>
                <div class="mt-1 text-2xl font-bold text-success-600">${{ number_format($totalAmount, 2) }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Due Date</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ now()->addMonth()->startOfMonth()->format('M j, Y') }}</div>
            </div>
        </div>

        {{-- Tenants List --}}
        @if(count($tenancies) > 0)
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Property</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Unit</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($tenancies as $t)
                            <tr class="hover:bg-gray-50 dark:bg-gray-900">
                                <td class="px-4 py-3 font-medium">{{ $t['tenant_name'] }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $t['tenant_email'] }}</td>
                                <td class="px-4 py-3">{{ $t['property_name'] }}</td>
                                <td class="px-4 py-3">{{ $t['unit'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-right font-semibold">${{ number_format($t['amount'], 2) }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $t['description'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center">
                <div class="text-4xl mb-2">📭</div>
                <p class="text-gray-500 dark:text-gray-400 font-medium">No active tenancies found</p>
                <p class="text-gray-400 dark:text-gray-300 text-sm mt-1">Add tenancies to tenants before generating invoices.</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
