<x-filament-panels::page>
    <div class="space-y-6">

        <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-8 text-white">
            <h1 class="text-2xl font-bold mb-2" style="font-family: Manrope, sans-serif;">Portfolio</h1>
            <p class="text-amber-100">View your properties, tenants, and documents.</p>
        </div>

        @php $properties = $this->getProperties(); @endphp

        @if(count($properties) === 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-sm border border-gray-100 dark:border-gray-700 text-center">
                <p class="text-gray-500 dark:text-gray-400">No properties in your portfolio yet.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($properties as $property)
                    @php
                        $tenants = $this->getTenantsForProperty($property->id);
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        {{-- Property Header --}}
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-bold" class="text-gray-900 dark:text-white">{{ $property->title }}</h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $property->address }}, {{ $property->city }}, {{ $property->province }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold" class="text-gray-900 dark:text-white">${{ number_format($this->getRentAmount($property->id), 2) }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Monthly Rent</p>
                                </div>
                            </div>
                            <div class="flex gap-4 mt-3 text-sm text-gray-600 dark:text-gray-300">
                                <span>{{ $property->bedrooms }} Bed</span>
                                <span>{{ $property->bathrooms }} Bath</span>
                                <x-filament::badge>{{ ucfirst($property->status) }}</x-filament::badge>
                            </div>
                        </div>

                        {{-- Tenants --}}
                        <div class="p-6">
                            <h3 class="font-bold text-sm uppercase tracking-wide mb-4" class="text-gray-900 dark:text-white">
                                Tenants ({{ count($tenants) }})
                            </h3>

                            @if(count($tenants) === 0)
                                <p class="text-sm text-gray-500 dark:text-gray-400">No tenants currently assigned.</p>
                            @else
                                <div class="space-y-4">
                                    @foreach($tenants as $tenant)
                                        @php $files = $this->getFilesForTenant($tenant->id); @endphp

                                        <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4">
                                            @php
                                                $outstanding = $this->getOutstandingBalance($tenant->id);
                                                $balanceClass = $outstanding > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400';
                                            @endphp
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Name</p>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $tenant->name }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</p>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $tenant->email }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</p>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $tenant->phone ?? "N/A" }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Current Outstanding Balance</p>
                                                    <p class="font-medium {{ $balanceClass }}">${{ number_format($outstanding, 2) }}</p>
                                                </div>
                                            </div>

                                            {{-- Tenant Files --}}
                                            @if(count($files) > 0)
                                                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold mb-2">Files</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($files as $file)
                                                            <a href="{{ asset("storage/" . $file->file_path) }}" target="_blank"
                                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition"
                                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition text-gray-900 dark:text-white" style="background: #DCA54A;">
                                                                <span>&#128196;</span>
                                                                {{ $file->name }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</x-filament-panels::page>
