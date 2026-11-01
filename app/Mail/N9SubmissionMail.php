<?php

namespace App\Mail;

use App\Models\N9Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class N9SubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public N9Submission $submission;

    public function __construct(N9Submission $submission)
    {
        $this->submission = $submission;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New N9 Move-Out Notice — ' . ($this->submission->tenant_names ?? 'Tenant'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.n9-submission',
            with: [
                'submission' => $this->submission,
                'tenant' => $this->submission->tenant?->name ?? 'N/A',
                'tenantNames' => $this->submission->tenant_names ?? 'N/A',
                'unitAddress' => $this->submission->unit_address ?? 'N/A',
                'terminationDate' => $this->submission->termination_date?->format('F j, Y') ?? 'N/A',
                'phone' => $this->submission->phone ?? 'N/A',
                'signature' => $this->submission->signature ?? 'N/A',
            ],
        );
    }

    public function attachments(): array
    {
        if (! $this->submission->file_path) {
            return [];
        }

        return [
            Attachment::fromPath(Storage::disk('public')->path($this->submission->file_path)),
        ];
    }
}
