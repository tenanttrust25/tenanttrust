<x-filament-panels::page>
    @php($ticket = $this->getTicket())

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Header --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ticket #{{ $ticket->ticket_number }}</p>
                        <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $ticket->title }}</h2>
                    </div>
                    <div class="flex gap-2">
                        <x-filament::badge :color="match($ticket->status) { 'open' => 'gray', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'info', default => 'gray' }">
                            {{ str_replace("_", " ", ucfirst($ticket->status)) }}
                        </x-filament::badge>
                        <x-filament::badge :color="in_array($ticket->priority, ['high', 'emergency']) ? 'danger' : 'warning'">
                            {{ ucfirst($ticket->priority) }}
                        </x-filament::badge>
                    </div>
                </div>
            </div>

            {{-- Details --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Details</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Property</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $ticket->property->title ?? "N/A" }}</p>
                        @if($ticket->property)
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                {{ $ticket->property->address }}, {{ $ticket->property->city }}, {{ $ticket->property->province }}
                                @if($ticket->propertyUnit?->unit)
                                    — Unit {{ $ticket->propertyUnit->unit }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</p>
                        <p class="whitespace-pre-wrap text-gray-700 dark:text-gray-200">{{ $ticket->description }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Submitted</p>
                        <p class="text-gray-700 dark:text-gray-200">{{ $ticket->created_at->format("F j, Y g:i A") }}</p>
                    </div>
                </div>
            </div>

            {{-- Photos --}}
            @if($ticket->photos && count($ticket->photos) > 0)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Photos</h3>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($ticket->photos as $photo)
                            <a href="{{ asset("storage/" . $photo) }}" target="_blank" class="block">
                                <img src="{{ asset("storage/" . $photo) }}" class="h-32 w-full rounded-lg border object-cover transition hover:opacity-90" alt="Ticket photo">
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Conversation --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Conversation</h3>

                @php($replies = $this->getReplies())

                @if($replies->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">No replies yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($replies as $reply)
                            <div class="flex gap-3 {{ $reply->user_id === auth()->id() ? "flex-row-reverse" : "" }}">
                                <div class="max-w-[80%] flex-1">
                                    <div class="rounded-lg px-4 py-3 {{ $reply->user_id === auth()->id() ? "bg-amber-50 dark:bg-gray-700" : "bg-gray-50 dark:bg-gray-700" }}">
                                        <p class="mb-1 text-xs font-semibold {{ $reply->user_id === auth()->id() ? "text-amber-700 dark:text-amber-400" : "text-gray-600 dark:text-gray-400" }}">
                                            {{ $reply->user_id === auth()->id() ? "You" : ($reply->user?->name ?? "Property Manager") }}
                                            <span class="font-normal text-gray-400 dark:text-gray-500"> &middot; {{ $reply->created_at?->diffForHumans() }}</span>
                                        </p>
                                        <p class="whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">{{ $reply->message }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Reply form --}}
        <div class="space-y-6">
            <div class="sticky top-24 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white">Add a Reply</h3>

                <form wire:submit="sendReply" class="space-y-4">
                    <div>
                        <textarea wire:model="reply_message" rows="5"
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-amber-500 focus:ring-amber-500"
                            placeholder="Type your reply..."></textarea>
                        @error("reply_message")
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-filament::button type="submit" class="w-full">
                        Send Reply
                    </x-filament::button>
                </form>
            </div>
        </div>
    </div>
</x-filament-panels::page>
