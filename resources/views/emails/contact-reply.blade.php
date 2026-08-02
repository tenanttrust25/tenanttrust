<x-mail::message>
# Re: Your Inquiry

Hi **{{ $submission->name }}**,

{{ $replyMessage }}

<x-mail::panel>
**Your original message:**  
{{ $submission->message }}
</x-mail::panel>

If you have any further questions, feel free to reply to this email or contact us anytime.

Thanks,<br>
**Tenant Trust**
</x-mail::message>
