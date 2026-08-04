<x-mail::message>
# New Invoice Available

Dear **{{ $ownerName }}**,

You have a new invoice from Tenant Trust.

**Invoice #{{ $invoice->invoice_number }}**  
**Amount:** ${{ $amount }}  
**Due Date:** {{ $dueDate }}

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
