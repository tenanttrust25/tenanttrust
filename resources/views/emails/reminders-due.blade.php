<x-mail::message>
# Reminders Due

The following reminders are now due. Log in to the admin panel to review and complete them.

@foreach ($reminders as $reminder)
**{{ $reminder['title'] }}**  
Tenant: {{ $reminder['tenant'] }}  
Remind date: {{ $reminder['remind_date'] }}  
Due date: {{ $reminder['due_date'] }}

---
@endforeach

<x-mail::button :url="url('/admin/reminders')">
Open Reminders
</x-mail::button>

Thanks,<br>
**Tenant Trust**
</x-mail::message>
