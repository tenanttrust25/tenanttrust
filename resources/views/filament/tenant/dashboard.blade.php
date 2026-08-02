<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-8 text-gray-900">
            <h1 class="text-3xl font-bold mb-2">Welcome back, {{ $user->name }}!</h1>
            <p class="text-gray-900">Your tenant portal at Tenant Trust. Manage your rent, maintenance, and documents all in one place.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ url("/tenant/invoices") }}" class="block">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Invoices Due</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $invoicesDue }}</p>
                        </div>
                        <div class="w-12 h-12 bg-amber-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-currency-dollar class="w-6 h-6 text-amber-600" />
                        </div>
                    </div>
                    <p class="text-sm text-amber-600 dark:text-white mt-4 font-medium">View invoices &rarr;</p>
                </div>
            </a>

            <a href="{{ url("/tenant/maintenance-tickets") }}" class="block">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Open Tickets</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $openTickets }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-wrench-screwdriver class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                    <p class="text-sm text-blue-600 dark:text-white mt-4 font-medium">View tickets &rarr;</p>
                </div>
            </a>

            <a href="{{ url("/tenant/tenant-files") }}" class="block">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Your Files</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $availableFiles }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-document-text class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                    <p class="text-sm text-green-600 dark:text-white mt-4 font-medium">View files &rarr;</p>
                </div>
            </a>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

<a href="{{ url("/tenant/profile") }}" class="text-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-purple-300 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-user class="w-8 h-8 mx-auto text-purple-600 dark:text-white mb-2" />
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">My Profile</p>
                </a>                <a href="{{ url("/tenant/invoices") }}" class="text-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-amber-300 dark:hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-credit-card class="w-8 h-8 mx-auto text-amber-600 dark:text-white mb-2" />
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">Pay Rent</p>
                </a>
                <a href="{{ url("/tenant/maintenance-tickets/create") }}" class="text-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-wrench class="w-8 h-8 mx-auto text-blue-600 dark:text-white mb-2" />
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">New Request</p>
                </a>
                <a href="{{ url("/tenant/tenant-files") }}" class="text-center p-4 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-green-300 dark:hover:border-green-500 hover:bg-green-50 dark:hover:bg-gray-700 transition-colors">
                    <x-heroicon-o-document-arrow-down class="w-8 h-8 mx-auto text-green-600 dark:text-white mb-2" />
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">My Documents</p>
                </a>
                
            </div>
        </div>
    </div>
</x-filament-panels::page>
