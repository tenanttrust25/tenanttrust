<x-mail::message>
# New Rental Application

**Applicant:** {{ $name }}
**Email:** {{ $email }}
**Phone:** {{ $phone }}
**Date of Birth:** {{ $date_of_birth }}

---

**Applying For:**
{{ $address }}

**Previous Rental History:**
**Previous Address:** {{ $previous_address }}
**Landlord Name:** {{ $landlord_name }}
**Landlord Contact:** {{ $landlord_contact }}

**Government Issued Photo ID:** {{ $gov_id }}
**Proof of Employment:** {{ $proof_of_employment }}

**Pets:** {{ $pets }}
**Smoker:** {{ $smoker }}

**Soft Credit Check:** {{ $credit_check }}
**Criminal Background Check:** {{ $background_check }}

<x-mail::button :url="url('/admin/rental-applications')">
View Application in Admin
</x-mail::button>

<x-mail::subcopy>
This applicant has consented to a soft credit check and criminal background check as part of the tenant screening process.
</x-mail::subcopy>
</x-mail::message>
