<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Pending Payouts</p>
                <p class="text-3xl font-bold mt-1 text-amber-600 dark:text-amber-400">
                    ${{ number_format($pendingTotal, 2) }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total Paid Out</p>
                <p class="text-3xl font-bold mt-1 text-green-600 dark:text-green-400">
                    ${{ number_format($paidTotal, 2) }}
                </p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Management Fees Paid</p>
                <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">
                    ${{ number_format($totalFees, 2) }}
                </p>
            </div>
        </div>

        {{-- Payout Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Payout History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Date</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Property</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Invoice</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 text-right">Rent Collected</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 text-right">Mgmt Fee</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400 text-right">Net to You</th>
                            <th class="px-4 py-3 font-semibold text-gray-600 dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($payouts as $payout)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ $payout->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $payout->property?->title ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    #{{ $payout->invoice?->invoice_number ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-medium">
                                    ${{ number_format($payout->rent_amount, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                    ${{ number_format($payout->management_fee, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white font-bold">
                                    ${{ number_format($payout->net_amount, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($payout->status === 'pending')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                            Pending
                                        </span>
                                    @elseif($payout->status === 'paid')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Paid {{ $payout->paid_at ? $payout->paid_at->format('M d') : '' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No payouts yet. Payouts are created automatically when rent payments are collected.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
