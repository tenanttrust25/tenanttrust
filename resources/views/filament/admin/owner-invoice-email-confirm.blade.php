<div class="space-y-4">
    <div class="rounded-lg bg-blue-50 dark:bg-gray-700 border border-blue-200 p-4 text-sm text-blue-800">
        <strong>✉️ Send Invoice #{{ $invoice->invoice_number }}</strong>
        <p class="mt-1">This invoice will be emailed to <strong>{{ $invoice->owner?->email }}</strong>.</p>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-sm text-gray-600 dark:text-gray-300">
        <table class="w-full">
            <tr>
                <td class="py-1 font-medium text-gray-500 dark:text-gray-400">To:</td>
                <td class="py-1 text-right">{{ $invoice->owner?->name }} &lt;{{ $invoice->owner?->email }}&gt;</td>
            </tr>
            <tr>
                <td class="py-1 font-medium text-gray-500 dark:text-gray-400">Subject:</td>
                <td class="py-1 text-right">Invoice #{{ $invoice->invoice_number }} from Tenant Trust</td>
            </tr>
            <tr>
                <td class="py-1 font-medium text-gray-500 dark:text-gray-400">Amount Due:</td>
                <td class="py-1 text-right font-bold">${{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="py-1 font-medium text-gray-500 dark:text-gray-400">Due Date:</td>
                <td class="py-1 text-right">{{ $invoice->due_date?->format('F j, Y') }}</td>
            </tr>
        </table>
    </div>
</div>
