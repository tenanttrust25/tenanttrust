<x-mail::message>
Hi {{ $name }},

Great news — your application for **{{ $address }}** has been approved.

Here is what happens next.

**1. Deposit — to hold the unit**

First and last month's rent must be paid in full before keys are handed over. The last month's rent is held as a deposit to hold the unit.

@if ($rent)
- Monthly rent: **${{ number_format($rent, 2) }}**
- First month's rent: **${{ number_format($rent, 2) }}**
- Last month's rent deposit (held): **${{ number_format($rent, 2) }}**
- **Total due before keys: ${{ number_format($totalDue, 2) }}**
@endif

For the deposit, please send an Interac e-transfer to **{{ $etransferEmail }}** ({{ $etransferName }}).

Going forward, it is your decision: you can continue to pay by e-transfer, or pay through the Tenant Portal using a debit or credit card — log in at [{{ $portalUrl }}]({{ $portalUrl }}), open **Rent & Billing → Invoices**, and click **Pay Now**. We will set up your portal account as soon as you confirm.

**2. Tenant insurance — required**

You will need tenant insurance before move-in. We recommend {{ $insuranceProvider }} ([{{ $insuranceUrl }}]({{ $insuranceUrl }})). Please send us either your proof of insurance or your policy number.

**3. Confirm your application**

Please confirm by replying to this email. Once you confirm, we will set up your tenant portal account and send your lease for signature.

Welcome to Tenant Trust — we look forward to having you.

Warm regards,<br>
**Tenant Trust**<br>
info@tenanttrust.ca · tenanttrust.ca
</x-mail::message>
