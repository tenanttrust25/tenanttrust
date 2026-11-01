<x-filament-widgets::widget>
    @php
        $color = $outstanding > 0 ? 'from-red-500 to-rose-600' : 'from-emerald-500 to-green-600';
        $label = $outstanding > 0 ? 'You currently owe' : 'You are all caught up';
    @endphp
    <div class="rounded-xl bg-gradient-to-r {{ $color }} p-6 text-white shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-white/80 uppercase tracking-wide font-medium">{{ $label }}</p>
                <p class="mt-1 text-3xl font-bold">${{ number_format($outstanding, 2) }}</p>
            </div>
            @if($outstanding > 0)
                <a href="{{ \App\Filament\Tenant\Resources\InvoiceResource::getUrl('index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-white/20 px-4 py-2 text-sm font-medium transition hover:bg-white/30">
                    <span>&#128176;</span> Pay Now
                </a>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/20 px-4 py-2 text-sm font-medium">
                    <span>&#9989;</span> No balance due
                </span>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
