<x-mail::message>
# {{ $categoryLabel }}

Dear **{{ $tenantName }}**,

Please find attached your **{{ $categoryLabel }}** for the rental unit at **{{ $unitAddress }}**.

**Generated:** {{ $generatedAt }}  
**Landlord / Property Manager:** {{ $landlordName }}

Please review the notice carefully. If you have any questions, contact us at info@tenanttrust.ca or call (705) 249-523-3331.

<x-mail::button :url="url('/tenant/dashboard')">
Open Tenant Portal
</x-mail::button>

Thanks,<br>
**Tenant Trust**
</x-mail::message>
