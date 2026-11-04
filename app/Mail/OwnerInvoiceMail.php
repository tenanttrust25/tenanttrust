<?php

namespace App\Mail;

use App\Models\OwnerInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OwnerInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public OwnerInvoice $invoice;

    public function __construct(OwnerInvoice $invoice)
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
            markdown: 'emails.owner-invoice',
            with: [
                "invoice" => $this->invoice,
                "ownerName" => $this->invoice->owner?->name,
                "amount" => number_format($this->invoice->amount, 2),
                "dueDate" => $this->invoice->due_date?->format("F j, Y"),
                "payUrl" => $this->invoice->payment_token
                    ? url('/invoice/pay/' . $this->invoice->payment_token)
                    : null,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->invoice->file_path && Storage::disk('documents')->exists($this->invoice->file_path)) {
            $attachments[] = Attachment::fromStorageDisk('public', $this->invoice->file_path);
        }

        return $attachments;
    }
}
