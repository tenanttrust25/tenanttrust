<x-mail::message>
# New Contact Form Submission

**From:** {{ $name }}
**Email:** {{ $email }}
**Phone:** {{ $phone }}

**Message:**
{{ $message }}

<x-mail::button :url="url('/admin/contact-submissions')">
View in Admin
</x-mail::button>
</x-mail::message>
