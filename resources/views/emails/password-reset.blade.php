<x-mail::message>
# Password Reset Request

Hi **{{ $user->name }}**,

A password reset has been requested for your Tenant Portal account at Tenant Trust.

Click the button below to choose a new password:

<x-mail::button :url="$resetUrl">
Reset My Password
</x-mail::button>

This link will expire in 60 minutes.

If you did not request this reset, please ignore this email — your password will remain unchanged.

For security, never share this link with anyone.

If you have any questions, contact us at info@tenanttrust.ca.

Thanks,<br>
**Tenant Trust**
</x-mail::message>
