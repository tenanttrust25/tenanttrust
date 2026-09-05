<?php

namespace App\Mail;

use App\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class FormSubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public FormSubmission $submission;

    public function __construct(FormSubmission $submission)
    {
        $this->submission = $submission;
    }

    public function envelope(): Envelope
    {
        $unit = $this->submission->unit_address ? ' — ' . $this->submission->unit_address : '';

        return new Envelope(
            subject: $this->subjectLine() . $unit,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.form-submission',
            with: [
                'submission' => $this->submission,
                'categoryLabel' => $this->categoryLabel(),
                'tenantName' => $this->submission->tenant_names
                    ?? $this->submission->tenant?->name
                    ?? 'Tenant',
                'landlordName' => $this->submission->landlord_name ?? 'Tenant Trust',
                'unitAddress' => $this->submission->unit_address ?? 'N/A',
                'generatedAt' => $this->submission->created_at?->format('F j, Y'),
            ],
        );
    }

    public function attachments(): array
    {
        $path = $this->submission->file_path;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return [];
        }

        return [
            Attachment::fromPath(Storage::disk('public')->path($path))
                ->as(basename($path))
                ->withMime('application/pdf'),
        ];
    }

    protected function subjectLine(): string
    {
        return match ($this->submission->form_category) {
            'n1' => 'Your N1 — Notice of Rent Increase from Tenant Trust',
            'n4' => 'Your N4 — Notice to End Tenancy (Non-payment of Rent)',
            'n5' => 'Your N5 — Notice to End Tenancy (Interference / Damage)',
            'n11' => 'Your N11 — Agreement to End the Tenancy',
            'n9' => 'Your N9 — Tenant\'s Notice to End the Tenancy',
            default => 'Notice from Tenant Trust' . ($this->submission->form?->name ? ' — ' . $this->submission->form->name : ''),
        };
    }

    protected function categoryLabel(): string
    {
        return match ($this->submission->form_category) {
            'n1' => 'N1 — Notice of Rent Increase',
            'n4' => 'N4 — Notice to End Tenancy Early (Non-payment of Rent)',
            'n5' => 'N5 — Notice to End Tenancy Early (Interference / Damage)',
            'n11' => 'N11 — Agreement to End the Tenancy',
            'n9' => 'N9 — Tenant\'s Notice to End the Tenancy',
            default => $this->submission->form?->name ?? 'Notice',
        };
    }
}
