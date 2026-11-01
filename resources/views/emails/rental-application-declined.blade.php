<x-mail::message>
Hi {{ $name }},

@if (!empty($address))
Thank you for your interest in renting with Tenant Trust and for taking the time to submit your application for **{{ $address }}**.
@else
Thank you for your interest in renting with Tenant Trust and for taking the time to submit your application.
@endif

After reviewing your application, we are unable to move forward with it at this time.

@if (count($reasons))
The main reason(s) were:

@foreach ($reasons as $reason)
- {{ $reason }}
@endforeach

If you are able to address the above, you are welcome to submit a new application in the future.
@else
You are welcome to submit a new application in the future.
@endif

We appreciate your interest and wish you the best in your search.

Warm regards,<br>
**Tenant Trust**<br>
info@tenanttrust.ca · tenanttrust.ca
</x-mail::message>
