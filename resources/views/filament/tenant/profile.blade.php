<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-8 text-white">
            <h1 class="text-2xl font-bold mb-2">My Profile</h1>
            <p class="text-amber-100">Manage your account settings and password.</p>
        </div>

        {{ $this->form }}
        
        <div class="flex justify-end mt-6">
            <x-filament::button wire:click="save" color="primary" tag="button">
                Save Changes
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
