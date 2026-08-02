<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Invoice #' . $this->invoice->invoice_number . ' from Tenant Trust',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-simple',
            with: [
                "invoice" => $this->invoice,
                "url" => url("/tenant/invoices"),
                "amount" => number_format($this->invoice->amount, 2),
                "dueDate" => $this->invoice->due_date?->format("F j, Y"),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
