<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm font-medium text-gray-500">Ticket Number</p>
            <p class="text-sm text-gray-900">{{ $ticket->ticket_number }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Property</p>
            <p class="text-sm text-gray-900">{{ $ticket->property->title ?? "N/A" }}</p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Status</p>
            <p class="text-sm">
                <x-filament::badge :color="match($ticket->status) { 'open' => 'gray', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'info', default => 'gray' }">
                    {{ str_replace("_", " ", ucfirst($ticket->status)) }}
                </x-filament::badge>
            </p>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-500">Priority</p>
            <p class="text-sm">
                <x-filament::badge :color="match($ticket->priority) { 'low' => 'gray', 'medium' => 'warning', 'high' => 'danger', 'emergency' => 'danger', default => 'gray' }">
                    {{ ucfirst($ticket->priority) }}
                </x-filament::badge>
            </p>
        </div>
    </div>

    <div>
        <p class="text-sm font-medium text-gray-500">Title</p>
        <p class="text-sm text-gray-900">{{ $ticket->title }}</p>
    </div>

    <div>
        <p class="text-sm font-medium text-gray-500">Description</p>
        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $ticket->description }}</p>
    </div>

    <div>
        <p class="text-sm font-medium text-gray-500">Submitted</p>
        <p class="text-sm text-gray-900">{{ $ticket->created_at->format("F j, Y g:i A") }}</p>
    </div>

    @if($ticket->photos && count($ticket->photos) > 0)
        <div>
            <p class="text-sm font-medium text-gray-500 mb-2">Photos</p>
            <div class="grid grid-cols-3 gap-2">
                @foreach($ticket->photos as $photo)
                    <img src="{{ asset("storage/" . $photo) }}" class="rounded-lg w-full h-24 object-cover" alt="Ticket photo">
                @endforeach
            </div>
        </div>
    @endif
</div>
