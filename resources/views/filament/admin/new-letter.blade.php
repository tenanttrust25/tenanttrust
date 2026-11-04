<x-filament-panels::page>
    <form wire:submit="generate" class="max-w-3xl space-y-6">

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recipient</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pick a tenant to auto-fill the name and address, or type it in manually.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tenant</label>
                <select wire:model.live="tenant_id"
                        class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                    <option value="">— choose a tenant —</option>
                    @foreach ($this->tenants() as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
                @error('tenant_id') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recipient name</label>
                    <input type="text" wire:model="recipient_name"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                    @error('recipient_name') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type / label</label>
                    <input type="text" wire:model="form_label" placeholder="Letter"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address line 1</label>
                    <input type="text" wire:model="address_line1"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                    @error('address_line1') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address line 2 (unit)</label>
                    <input type="text" wire:model="address_line2"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                    <input type="text" wire:model="city"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Province</label>
                    <input type="text" wire:model="province"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Postal code</label>
                    <input type="text" wire:model="postal_code"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Letter</h3>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject (RE:)</label>
                <input type="text" wire:model="subject" placeholder="Outstanding Balance — Unit 5, 5 Fairview Avenue"
                       class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Salutation</label>
                <input type="text" wire:model="salutation" placeholder="Dear Ms. Jedyk,"
                       class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Body</label>
                <textarea wire:model="body" rows="14"
                          class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm font-normal"
                          placeholder="Type the letter body here. Each line becomes a paragraph."></textarea>
                @error('body') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Signer</label>
                    <input type="text" wire:model="signer_name"
                           class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 text-sm">
                </div>
            </div>
        </div>

        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" wire:model="send_now"
                   class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" style="width:18px;height:18px;">
            <span class="text-sm text-gray-700 dark:text-gray-300">Send via PostGrid immediately (uses the current Test/Live mode)</span>
        </label>

        <div class="flex items-center gap-4">
            <x-filament::button type="submit">
                Generate letter
            </x-filament::button>

            @if ($last_pdf)
                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('files.signed', now()->addMinutes(10), ['encoded' => rtrim(strtr(base64_encode($last_pdf), '+/', '-_'), '=')]) }}" target="_blank"
                   class="text-sm text-primary-600 underline">
                    Open last generated PDF
                </a>
            @endif
        </div>

    </form>
</x-filament-panels::page>
