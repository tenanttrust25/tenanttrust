<x-filament-panels::page>

    {{-- Ticket Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Ticket ##{{ $this->getTicket()->ticket_number }}</p>
                    <h2 class="text-xl font-bold mt-1" style="font-family: Manrope, sans-serif; color: #0F172A;">{{ $this->getTicket()->title }}</h2>
                </div>
                <div class="flex gap-2">
                    <x-filament::badge>
                        {{ str_replace("_", " ", ucfirst($this->getTicket()->status)) }}
                    </x-filament::badge>
                    <x-filament::badge :color="$this->getTicket()->priority === 'emergency' ? 'danger' : ($this->getTicket()->priority === 'high' ? 'danger' : 'warning')">
                        {{ ucfirst($this->getTicket()->priority) }}
                    </x-filament::badge>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Tenant & Ticket Info --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Tenant Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="font-bold mb-4 uppercase text-sm tracking-wide" class="text-gray-900 dark:text-white" style=" font-family: Manrope, sans-serif;">Tenant Information</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Name</p>
                        <p class="font-medium">{{ $this->getTicket()->tenant->name ?? "N/A" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</p>
                        <p class="font-medium">{{ $this->getTicket()->tenant->phone ?? "N/A" }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</p>
                        <p class="font-medium">{{ $this->getTicket()->tenant->email ?? "N/A" }}</p>
                    </div>
                </div>
            </div>

            {{-- Property & Issue --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="font-bold mb-4 uppercase text-sm tracking-wide" class="text-gray-900 dark:text-white" style=" font-family: Manrope, sans-serif;">Issue Details</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Property Address</p>
                        <p class="font-medium">{{ $this->getTicket()->property->title ?? "N/A" }}</p>
                        @if($this->getTicket()->property)
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $this->getTicket()->property->address }}, {{ $this->getTicket()->property->city }}, {{ $this->getTicket()->property->province }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Description</p>
                        <p class="text-gray-700 dark:text-gray-200 whitespace-pre-wrap">{{ $this->getTicket()->description }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Submitted</p>
                        <p>{{ $this->getTicket()->created_at->format("F j, Y g:i A") }}</p>
                    </div>
                </div>
            </div>

            {{-- Photos --}}
            @if($this->getTicket()->photos && count($this->getTicket()->photos) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="font-bold mb-4 uppercase text-sm tracking-wide" class="text-gray-900 dark:text-white" style=" font-family: Manrope, sans-serif;">Photos</h3>
                <div class="grid grid-cols-3 gap-3">
                    @foreach($this->getTicket()->photos as $photo)
                    <a href="{{ asset("storage/" . $photo) }}" target="_blank" class="block">
                        <img src="{{ asset("storage/" . $photo) }}" class="w-full h-32 object-cover rounded-lg border hover:opacity-90 transition" alt="Ticket photo">
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Reply History --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="font-bold mb-4 uppercase text-sm tracking-wide" class="text-gray-900 dark:text-white" style=" font-family: Manrope, sans-serif;">Conversation History</h3>

                @php $replies = $this->getReplies(); @endphp
                @if(count($replies) === 0)
                    <p class="text-gray-500 dark:text-gray-400 text-sm">No replies yet.</p>
                @else
                    <div class="space-y-4">
                        @foreach($replies as $reply)
                        <div class="flex gap-3 {{ $reply->user_id === Auth::id() ? "flex-row-reverse" : "" }}">
                            <div class="flex-1 max-w-[80%]">
                                <div class="rounded-lg px-4 py-3 {{ $reply->user_id === Auth::id() ? "bg-amber-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200" : "bg-gray-50 dark:bg-gray-700 text-gray-800 dark:text-gray-200" }}">
                                    <p class="text-xs font-semibold mb-1 {{ $reply->user_id === Auth::id() ? "text-amber-700 dark:text-amber-400" : "text-gray-600 dark:text-gray-400" }}">
                                        {{ $reply->user->name ?? "System" }}
                                        <span class="font-normal text-gray-400 dark:text-gray-500"> &middot; {{ $reply->created_at->diffForHumans() }}</span>
                                    </p>
                                    <p class="text-sm whitespace-pre-wrap">{{ $reply->message }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        {{-- Right: Reply Form --}}
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 sticky top-24">
                <h3 class="font-bold mb-4 uppercase text-sm tracking-wide" class="text-gray-900 dark:text-white" style=" font-family: Manrope, sans-serif;">Send Reply</h3>

                <form wire:submit="sendReply" class="space-y-4">
                    <div>
                        <textarea wire:model="reply_message" rows="5"
                            class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 text-sm"
                            placeholder="Type your reply to the tenant..."></textarea>
                        @error("reply_message") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit"
                        class="w-full px-4 py-2.5 rounded-lg font-semibold text-sm transition"
                        style="background: #DCA54A; color: #0F172A;">
                        Send Reply
                    </button>
                </form>

                {{-- Quick actions --}}
                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold mb-2">Quick Status</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ \App\Filament\Owner\Resources\MaintenanceTicketResource::getUrl("view", ["record" => $this->getTicket()]) }}?action=updateStatus" class="text-xs px-3 py-1.5 rounded-lg border hover:bg-gray-50 transition" class="text-gray-900 dark:text-white" style=" border-color: #DCA54A;">Change Status</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-filament-panels::page>
