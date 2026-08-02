<x-filament-panels::page>
    {{ $this->form }}

    <div class="flex justify-end">
        <x-filament::button wire:click="save">
            Save All Fees
        </x-filament::button>
    </div>
</x-filament-panels::page>
