<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>🔧 Maintenance Workflows</span>
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    {{ $openCount }} open · {{ $emergencyCount }} emergencies · {{ $resolvedCount }} resolved
                </span>
            </div>
        </x-slot>

        {{-- Stats Row --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Avg Resolution Time</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $avgResolutionHours }} hrs</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">From opened to resolved</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Repair Costs</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalRepairCost) }}</div>
                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Across {{ $resolvedCount }} resolved tickets</div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Priority Breakdown</div>
                <div class="mt-2 flex gap-2">
                    @foreach(["emergency" => "red", "high" => "orange", "medium" => "yellow", "low" => "gray"] as $p => $c)
                        @php $count = $priorityCounts[$p] ?? 0; @endphp
                        <span class="inline-flex items-center rounded-full bg-{{ $c }}-100 px-2 py-0.5 text-xs font-medium text-{{ $c }}-800">
                            {{ ucfirst($p) }}: {{ $count }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Open Work Orders Table --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Open Work Orders</h3>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Ticket</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Title</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Property</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Tenant</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Priority</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Days Open</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($openTickets as $t)
                            <tr class="hover:bg-gray-50 dark:bg-gray-900">
                                <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $t["ticket_number"] }}</td>
                                <td class="px-4 py-3 font-medium">
                                    {{ $t["title"] }}
                                    @if($t["is_repeat"])
                                        <span class="ml-1 inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800">repeat</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $t["property"] }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $t["tenant"] }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $pcolors = [
                                            "emergency" => "bg-red-100 text-red-800",
                                            "high" => "bg-orange-100 text-orange-800",
                                            "medium" => "bg-yellow-100 text-yellow-800",
                                            "low" => "bg-gray-100 text-gray-800",
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $pcolors[$t["priority"]] ?? "bg-gray-100" }}">
                                        {{ ucfirst($t["priority"]) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $scolors = [
                                            "open" => "bg-blue-100 text-blue-800",
                                            "in_progress" => "bg-yellow-100 text-yellow-800",
                                        ];
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $scolors[$t["status"]] ?? "bg-gray-100" }}">
                                        {{ str_replace("_", " ", ucfirst($t["status"])) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 {{ $t["days_open"] > 7 ? "text-red-600 font-medium" : "text-gray-600 dark:text-gray-300" }}">
                                    {{ $t["days_open"] }} days
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-300">No open work orders. Everything's running smoothly! 🎉</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
