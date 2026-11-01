@php
    $dueReminders = \App\Models\Reminder::query()
        ->due()
        ->with('tenant')
        ->orderBy('remind_date')
        ->get();
@endphp

@if ($dueReminders->isNotEmpty())
    <style>[x-cloak]{display:none !important;}</style>
    <div
        x-data="{ open: false }"
        x-init="if (! sessionStorage.getItem('tt_reminders_popup_seen')) { sessionStorage.setItem('tt_reminders_popup_seen', '1'); open = true; }"
    >
        {{-- Always-visible red bar (persists on every admin page while reminders are due) --}}
        <div class="fi-reminders-due-bar" style="background:#b91c1c;color:#fff;padding:10px 16px;font-size:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span style="font-weight:700;">⚠️ {{ $dueReminders->count() }} reminder{{ $dueReminders->count() > 1 ? 's' : '' }} due</span>
            <button type="button" @click="open = true" style="background:#fff;color:#b91c1c;border:0;border-radius:6px;padding:4px 10px;font-weight:700;cursor:pointer;">Review now</button>
            <a href="{{ url('/admin/reminders') }}" style="color:#fff;font-weight:600;text-decoration:underline;margin-left:auto;">All reminders →</a>
        </div>

        {{-- Pop-up: opens once per browser session, links straight to each due reminder --}}
        <div x-show="open" x-cloak @keydown.escape.window="open = false"
             style="position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.55);display:flex;align-items:flex-start;justify-content:center;padding:72px 16px;">
            <div style="background:#fff;color:#111827;width:100%;max-width:620px;border-radius:12px;box-shadow:0 24px 60px rgba(0,0,0,.45);overflow:hidden;">
                <div style="background:#b91c1c;color:#fff;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
                    <strong style="font-size:15px;">Reminders due — {{ $dueReminders->count() }}</strong>
                    <button type="button" @click="open = false" aria-label="Close" style="background:transparent;border:0;color:#fff;font-size:22px;line-height:1;cursor:pointer;">&times;</button>
                </div>
                <div style="max-height:56vh;overflow:auto;">
                    @foreach ($dueReminders as $reminder)
                        <a href="{{ url('/admin/reminders/' . $reminder->id . '/edit') }}"
                           style="display:block;padding:12px 18px;border-bottom:1px solid #eef2f7;text-decoration:none;color:#111827;">
                            <div style="font-weight:600;">{{ $reminder->title }}@if ($reminder->tenant) <span style="color:#6b7280;font-weight:400;">— {{ $reminder->tenant->name }}</span>@endif</div>
                            <div style="font-size:12px;color:#b91c1c;margin-top:2px;">
                                Due {{ ($reminder->due_date ?? $reminder->remind_date)?->format('M j, Y') }} · click to open
                            </div>
                        </a>
                    @endforeach
                </div>
                <div style="padding:12px 18px;background:#f8fafc;display:flex;justify-content:space-between;align-items:center;gap:10px;">
                    <button type="button" @click="open = false" style="background:#e5e7eb;color:#111827;border:0;border-radius:6px;padding:8px 14px;font-weight:600;cursor:pointer;">Dismiss for now</button>
                    <a href="{{ url('/admin/reminders') }}" style="color:#b91c1c;font-weight:700;text-decoration:none;">Open Reminders →</a>
                </div>
            </div>
        </div>
    </div>
@endif
