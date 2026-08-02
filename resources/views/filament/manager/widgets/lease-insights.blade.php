<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>📋 Tenant & Lease Insights</span>
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    {{ $totalActive }} active leases · {{ $expiringCount }} expiring within 60 days
                </span>
            </div>
        </x-slot>

        {{-- Lease Status Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Upcoming Move-Ins</div>
                <div class="mt-1 text-2xl font-bold text-success-600">{{ $upcomingMoveIns->count() }}</div>
                @foreach($upcomingMoveIns as $m)
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $m["tenant"] }} → {{ $m["property"] }} ({{ $m["date"] }})</div>
                @endforeach
                @if($upcomingMoveIns->isEmpty())
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-300">No upcoming move-ins</div>
                @endif
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Leases Expiring ≤ 60 Days</div>
                <div class="mt-1 text-2xl font-bold text-orange-600">{{ $expiringCount }}</div>
                @foreach($expiringSoon as $e)
                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $e["tenant"] }} — {{ $e["property"] }} ({{ $e["days"] }} days)</div>
                @endforeach
                @if($expiringSoon->isEmpty())
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-300">No leases expiring soon</div>
                @endif
            </div>
            <div class="rounded-xl border border-blue-200 bg-blue-50 dark:bg-gray-700 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-blue-600">Total Monthly Rent</div>
                <div class="mt-1 text-2xl font-bold text-blue-600">$65,050</div>
                <div class="mt-1 text-xs text-blue-500">{{ $totalActive }} active leases</div>
            </div>
        </div>

        {{-- Active Leases Table --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Active Leases <span class="text-gray-400 dark:text-gray-300 font-normal">(page {{ $activeTenancies->currentPage() }} of {{ $activeTenancies->lastPage() }})</span></h3>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Property</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Rent</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Lease End</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($activeTenancies as $t)
                            <tr class="hover:bg-gray-50 dark:bg-gray-900">
                                <td class="px-4 py-3 font-medium">{{ $t["tenant"] }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $t["tenant_email"] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $t["property"] }} @if($t["unit"]) (Unit {{ $t["unit"] }}) @endif</td>
                                <td class="px-4 py-3 font-medium">{{ $t["rent"] }}</td>
                                <td class="px-4 py-3">
                                    @if($t["days_remaining"] !== null)
                                        <span class="{{ $t["days_remaining"] <= 30 ? "text-red-600 font-medium" : ($t["days_remaining"] <= 60 ? "text-orange-600" : "text-gray-600 dark:text-gray-300") }}">
                                            {{ $t["lease_end"] }}
                                            @if($t["days_remaining"] <= 60)
                                                <span class="ml-1 text-xs">({{ $t["days_remaining"] }}d)</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Ongoing</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-300">No active leases found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $activeTenancies->links() }}
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
