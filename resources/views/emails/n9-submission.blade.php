<x-mail::message>
# New N9 Move-Out Notice Filed

A tenant has submitted an **N9 — Notice to End Tenancy** through the portal.

**Tenant:** {{ $tenantNames }}
**Landlord:** {{ $submission->landlord_name ?? 'N/A' }}
**Rental Unit:** {{ $unitAddress }}
**Tenancy Ends:** {{ $terminationDate }}
**Phone:** {{ $phone }}
**Signed:** {{ $signature }}

The filled N9 form is attached to this email for your records.

<x-mail::button :url="url('/admin/n9-submissions')">
View All N9 Submissions
</x-mail::button>

Thanks,<br>
**Tenant Trust Portal**
</x-mail::message>
