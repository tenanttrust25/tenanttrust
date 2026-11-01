<x-mail::message>
# New Invoice Available

Dear **{{ $invoice->tenant?->name }}**,

You have a new invoice from Tenant Trust.

**Invoice #{{ $invoice->invoice_number }}**  
**Amount:** ${{ $amount }}  
**Due Date:** {{ $dueDate }}

<x-mail::button :url="$url">
Click Here to View Invoice
</x-mail::button>

If you have any questions, please contact us at info@tenanttrust.ca or call 249-523-3331.

Thanks,<br>
**Tenant Trust**
</x-mail::message>
