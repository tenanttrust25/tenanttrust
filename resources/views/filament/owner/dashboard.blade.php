<x-filament-panels::page>
    <div class="space-y-8">

        {{-- Portfolio Snapshot --}}
        <div>
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white uppercase tracking-wide">Portfolio Snapshot</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total Properties / Units</p>
                    <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $this->getTotalUnits() }} / {{ $this->getTotalUnitCount() }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Occupancy Rate</p>
                    <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $this->getOccupancyRate() }}%</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Occupied</p>
                    <p class="text-3xl font-bold mt-1 text-green-600 dark:text-green-400">{{ $this->occupiedUnits }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Vacant</p>
                    <p class="text-3xl font-bold mt-1 text-red-600 dark:text-red-400">{{ $this->vacantUnits }}</p>
                </div>
            </div>
        </div>

        {{-- Financial Performance --}}
        <div>
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white uppercase tracking-wide">Financial Performance</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Expected Income</p>
                    <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">${{ number_format($this->getExpectedIncome(), 2) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Collected</p>
                    <p class="text-3xl font-bold mt-1 text-green-600 dark:text-green-400">${{ number_format($this->getCollectedIncome(), 2) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Net Income (after fees)</p>
                    <p class="text-3xl font-bold mt-1 text-blue-600 dark:text-blue-400">${{ number_format($this->getNetIncome(), 2) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Outstanding</p>
                    <p class="text-3xl font-bold mt-1 text-red-600 dark:text-red-400">${{ number_format($this->getOpenExpenses(), 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Payout Summary --}}
        <div>
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white uppercase tracking-wide">Payout Summary</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Pending Payouts</p>
                    <p class="text-3xl font-bold mt-1 text-amber-600 dark:text-amber-400">${{ number_format($this->getPendingPayouts(), 2) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Total Paid Out</p>
                    <p class="text-3xl font-bold mt-1 text-green-600 dark:text-green-400">${{ number_format($this->getTotalPayouts(), 2) }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Management Fees</p>
                    <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">${{ number_format($this->getTotalFeesPaid(), 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Maintenance Tracker --}}
        <div>
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white uppercase tracking-wide">Maintenance Tracker</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Open Work Orders</p>
                    <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $this->getOpenTickets() }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Critical / Overdue</p>
                    <p class="text-3xl font-bold mt-1
                        {{ $this->getCriticalTickets() > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ $this->getCriticalTickets() }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Available Documents</p>
                    <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $this->getTotalDocuments() }}</p>
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div>
            <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white uppercase tracking-wide">Quick Links</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-600 dark:text-gray-300 mb-4">View your payout history and pending payments.</p>
                    <a href="{{ url('/owner/payouts') }}"
                       class="inline-flex items-center px-5 py-2.5 rounded-lg font-semibold text-sm text-gray-900 dark:text-white"
                       style="background: #DCA54A;">
                        View Payouts &rarr;
                    </a>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                    <p class="text-gray-600 dark:text-gray-300 mb-4">Access your monthly owner statements, tax documents, and signed lease agreements.</p>
                    <a href="{{ url('/owner/documents') }}"
                       class="inline-flex items-center px-5 py-2.5 rounded-lg font-semibold text-sm text-gray-900 dark:text-white"
                       style="background: #DCA54A;">
                        View Documents &rarr;
                    </a>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
