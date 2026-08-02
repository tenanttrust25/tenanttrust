<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>📊 Portfolio Overview</span>
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    {{ $totalProperties }} properties · {{ $totalUnits }} total units · ${{ number_format($monthlyRentRoll) }}/mo
                </span>
            </div>
        </x-slot>

        {{-- Filters --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Property Type</label>
                <select wire:model.live="filterPropertyType" class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                    <option value="">All Types</option>
                    @foreach($propertyTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">City</label>
                <select wire:model.live="filterCity" class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                    <option value="">All Cities</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
                <select wire:model.live="filterStatus" class="fi-input block w-full rounded-lg border-gray-300 text-sm shadow-sm">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Occupancy Rate</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-bold {{ $occupancyRate >= 80 ? "text-success-600" : ($occupancyRate >= 60 ? "text-warning-600" : "text-danger-600") }}">{{ $occupancyRate }}%</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">({{ $occupiedProps }}/{{ $totalProperties }})</span>
                </div>
                <div class="mt-1 h-2 w-full rounded-full bg-gray-200">
                    <div class="h-2 rounded-full {{ $occupancyRate >= 80 ? "bg-success-500" : ($occupancyRate >= 60 ? "bg-warning-500" : "bg-danger-500") }}" style="width: {{ $occupancyRate }}%"></div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Monthly Rent Roll</div>
                <div class="mt-1 text-2xl font-bold text-success-600">${{ number_format($monthlyRentRoll) }}</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $activeLeasesCount }} active leases</div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Portfolio Value</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalValue) }}</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Across {{ $totalProperties }} properties</div>
            </div>

            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Vacant Properties</div>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $vacantProps }}</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">available</span>
                </div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Avg time to fill: {{ $avgDaysToFill }} days</div>
            </div>
        </div>

        {{-- Properties Table --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Property</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">City</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500 dark:text-gray-400">Units</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Total Rent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($properties as $p)
                        <tr class="hover:bg-gray-50 dark:bg-gray-900">
                            <td class="px-4 py-3 font-medium">{{ $p["title"] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $p["city"] }}</td>
                            <td class="px-4 py-3 text-center">{{ $p["unit_count"] }}</td>
                            <td class="px-4 py-3 text-right font-semibold">${{ number_format($p["total_rent"]) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-gray-300">No properties match the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
