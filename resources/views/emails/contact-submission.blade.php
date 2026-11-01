<x-mail::message>
# New Contact Form Submission

**From:** {{ $name }}
**Email:** {{ $email }}
**Phone:** {{ $phone }}
@if(!empty($propertyContext))
**Property:** {{ $propertyContext }}
@endif

**Message:**
{{ $message }}

<x-mail::button :url="url('/admin/contact-submissions')">
View in Admin
</x-mail::button>
</x-mail::message>
