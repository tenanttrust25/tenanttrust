<?php

namespace App\Mail;

use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSubmissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public ContactSubmission $submission;

    public function __construct(ContactSubmission $submission)
    {
        $this->submission = $submission;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Form Submission from ' . $this->submission->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-submission',
            with: [
                "name" => $this->submission->name,
                "email" => $this->submission->email,
                "phone" => $this->submission->phone ?? "N/A",
                "message" => $this->submission->message,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
