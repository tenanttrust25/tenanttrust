<x-mail::message>
# New Invoice Available

Dear **{{ $ownerName }}**,

You have a new invoice from Tenant Trust.

**Invoice #{{ $invoice->invoice_number }}**  
**Amount:** ${{ $amount }}  
**Due Date:** {{ $dueDate }}

@if ($payUrl && in_array($invoice->status, ['pending', 'overdue']))
<x-mail::button :url="$payUrl" color="success">
Pay Now
</x-mail::button>

You can also pay anytime using this link: [{{ $payUrl }}]({{ $payUrl }})
@endif

@if ($invoice->description)
**Description:** {{ $invoice->description }}
@endif

@if ($invoice->file_path)
A copy of the invoice PDF is attached to this email.
@else
If you have any questions about this invoice, contact us at info@tenanttrust.ca or call (705) 555-0123.
@endif

Thanks,<br>
**Tenant Trust**
</x-mail::message>
