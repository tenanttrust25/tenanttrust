<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-8 text-white">
            <h1 class="text-3xl font-bold mb-2">Year End Summary</h1>
            <p class="text-emerald-100">View your annual rent summary and download your official rent receipt.</p>
        </div>

        {{-- Year Selector + Download --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Select Year:</label>
                    <select onchange="window.location.href='?year=' + this.value"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $y === $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ $this->getReceiptUrl() }}"
                   class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <x-heroicon-m-arrow-down-tray class="w-5 h-5 mr-2" />
                    Download Rent Receipt (PDF)
                </a>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Year</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $selectedYear }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Paid</p>
                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($totalPaid, 2) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Payments Made</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ count($paidInvoices) }}</p>
            </div>
        </div>

        {{-- Invoice Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Paid Invoices - {{ $selectedYear }}</h2>
            </div>
            @if(count($paidInvoices) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Invoice #</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Description</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Property</th>
                                <th class="px-6 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Due Date</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($paidInvoices as $inv)
                                <tr class="hover:bg-gray-50 dark:bg-gray-900">
                                    <td class="px-6 py-4 font-medium">{{ $inv->invoice_number }}</td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $inv->description ?? 'Rent' }}</td>
                                    <td class="px-6 py-4">{{ $inv->property?->title ?? 'N/A' }}</td>
                                    <td class="px-6 py-4">{{ $inv->due_date->format('M j, Y') }}</td>
                                    <td class="px-6 py-4 text-right font-semibold">${{ number_format($inv->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">Total Paid:</td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-600">${{ number_format($totalPaid, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <x-heroicon-o-document-text class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No paid invoices for {{ $selectedYear }}</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Once you pay your rent, your receipts will appear here.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
