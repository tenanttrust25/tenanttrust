@php
    $dueReminders = \App\Models\Reminder::query()
        ->due()
        ->with('tenant')
        ->orderBy('remind_date')
        ->get();
@endphp

@if ($dueReminders->isNotEmpty())
    <div class="fi-reminders-due-bar" style="background:#b91c1c;color:#fff;padding:10px 16px;font-size:14px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-weight:700;">⚠️ {{ $dueReminders->count() }} reminder{{ $dueReminders->count() > 1 ? 's' : '' }} due</span>
        <span style="opacity:.9;">
            @foreach ($dueReminders->take(3) as $reminder)
                <span style="margin-right:12px;">
                    {{ $reminder->title }}
                    @if ($reminder->tenant)
                        — {{ $reminder->tenant->name }}
                    @endif
                    (due {{ $reminder->due_date?->format('M j, Y') ?? $reminder->remind_date->format('M j, Y') }})
                </span>
            @endforeach
            @if ($dueReminders->count() > 3)
                +{{ $dueReminders->count() - 3 }} more
            @endif
        </span>
        <a href="{{ url('/admin/reminders') }}" style="color:#fff;font-weight:600;text-decoration:underline;margin-left:auto;">View reminders →</a>
    </div>
@endif
