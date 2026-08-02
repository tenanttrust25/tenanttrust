<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'tenant' => $invoice->tenant,
            'property' => $invoice->property,
        ]);

        $filename = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    public function download(Invoice $invoice)
    {
        if (!$invoice->file_path || !Storage::disk('public')->exists($invoice->file_path)) {
            $invoice->file_path = $this->generate($invoice);
            $invoice->save();
        }

        return Storage::disk('public')->download($invoice->file_path);
    }
}
