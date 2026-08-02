<x-mail::message>
# Welcome to Tenant Trust!

Dear **{{ $user->name }}**,

A new **{{ $role }}** account has been created for you at Tenant Trust.

**Your Login Details:**
- **Email:** {{ $user->email }}
- **Temporary Password:** `{{ $tempPassword }}`

<x-mail::button :url="$loginUrl">
Log In to Your Account
</x-mail::button>

For security, please log in and change your password after your first login.

If you have any questions, contact us at info@tenanttrust.ca.

Thanks,<br>
**Tenant Trust**
</x-mail::message>
