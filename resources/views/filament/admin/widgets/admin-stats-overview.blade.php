<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>🏢 Site Overview</span>
            </div>
        </x-slot>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 shadow-sm">
                <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Properties</div>
                <div class="mt-0.5 text-xl font-bold text-gray-900 dark:text-white">{{ $totalProperties }}</div>
                <div class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ $occupiedUnits }} occupied</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 shadow-sm">
                <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tenants</div>
                <div class="mt-0.5 text-xl font-bold text-gray-900 dark:text-white">{{ $totalTenants }}</div>
                <div class="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400">{{ $totalOwners }} owners · {{ $previousTenants }} previous</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 shadow-sm">
                <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Monthly Revenue</div>
                <div class="mt-0.5 text-xl font-bold text-success-600">${{ number_format($monthlyRevenue) }}</div>
            </div>
            <div class="rounded-xl border border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-gray-800 p-3">
                <div class="text-[11px] font-medium uppercase tracking-wide text-purple-600 dark:text-purple-300">Management Fees</div>
                <div class="mt-0.5 text-xl font-bold text-purple-600 dark:text-purple-300">${{ number_format($monthlyManagementFees, 2) }}</div>
                <div class="mt-0.5 text-[11px] text-purple-500 dark:text-purple-400">${{ number_format($ytdManagementFees, 2) }} YTD</div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-4">
            <div class="rounded-xl border border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-gray-800 p-3">
                <div class="text-[11px] font-medium uppercase tracking-wide text-orange-600 dark:text-orange-300">Pending Invoices</div>
                <div class="mt-0.5 text-xl font-bold text-orange-600 dark:text-orange-300">{{ $pendingInvoices }}</div>
            </div>
            <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-gray-800 p-3">
                <div class="text-[11px] font-medium uppercase tracking-wide text-red-600 dark:text-red-300">Overdue</div>
                <div class="mt-0.5 text-xl font-bold text-red-600 dark:text-red-300">{{ $overdueInvoices }}</div>
                <div class="mt-0.5 text-[11px] text-red-500 dark:text-red-400">${{ number_format($overdueAmount) }} total</div>
            </div>
            <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-gray-800 p-3">
                <div class="text-[11px] font-medium uppercase tracking-wide text-blue-600 dark:text-blue-300">Open Tickets</div>
                <div class="mt-0.5 text-xl font-bold text-blue-600 dark:text-blue-300">{{ $openTickets }}</div>
            </div>
            <div class="rounded-xl border border-{{ $dueReminders > 0 ? 'red-200 dark:border-red-800 bg-red-50' : 'gray-200 dark:border-gray-700 bg-white dark:bg-gray-800' }} p-3">
                <div class="text-[11px] font-medium uppercase tracking-wide {{ $dueReminders > 0 ? 'text-red-600 dark:text-red-300' : 'text-gray-500 dark:text-gray-400' }}">Reminders</div>
                <div class="mt-0.5 text-xl font-bold {{ $dueReminders > 0 ? 'text-red-600 dark:text-red-300' : 'text-gray-900 dark:text-white' }}">{{ $totalReminders }}</div>
                <div class="mt-0.5 text-[11px] {{ $dueReminders > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $dueReminders }} due now</div>
            </div>
        </div>
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Recent Invoices</h3>
            @if(count($recentInvoices) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Invoice #</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tenant</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Property</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($recentInvoices as $inv)
                                <tr class="hover:bg-gray-50 dark:bg-gray-900">
                                    <td class="px-4 py-3 font-medium">{{ $inv->invoice_number }}</td>
                                    <td class="px-4 py-3">{{ $inv->tenant?->name }}</td>
                                    <td class="px-4 py-3">{{ $inv->property?->title }}</td>
                                    <td class="px-4 py-3 text-right">${{ number_format($inv->amount, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ $inv->status === "paid" ? "bg-green-100 text-green-700" : "" }}
                                            {{ $inv->status === "pending" ? "bg-yellow-100 text-yellow-700" : "" }}
                                            {{ $inv->status === "overdue" ? "bg-red-100 text-red-700" : "" }}
                                            {{ $inv->status === "cancelled" ? "bg-gray-100 text-gray-500" : "" }}">
                                            {{ $inv->status === "pending" ? "Unpaid" : ucfirst($inv->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="py-4 text-center text-sm text-gray-400 dark:text-gray-500">No invoices yet.</p>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
