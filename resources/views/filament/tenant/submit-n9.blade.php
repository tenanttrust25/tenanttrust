<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 rounded-xl p-8 text-white">
            <h1 class="text-2xl font-bold mb-2">Submit N9 — Notice to End Tenancy</h1>
            <p class="text-amber-100">Fill this out to give formal notice that you're ending your tenancy. Once submitted, Tenant Trust receives the notice and a copy of the filled form.</p>
        </div>

        {{ $this->form }}

        <div class="flex justify-end mt-6">
            <x-filament::button wire:click="submit" color="primary" tag="button">
                Submit N9 Notice
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
