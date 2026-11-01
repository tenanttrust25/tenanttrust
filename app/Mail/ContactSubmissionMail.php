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
        $property = $this->submission->property;

        $subject = $property
            ? 'New Property Inquiry — ' . $property->title . ' (' . $this->submission->name . ')'
            : 'New Contact Form Submission from ' . $this->submission->name;

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $submission = $this->submission;
        $property = $submission->property;
        $unit = $submission->propertyUnit;

        $propertyContext = null;
        if ($property) {
            $propertyContext = $property->title . ' — ' . $property->address . ', ' . $property->city;
            if ($unit?->unit) {
                $propertyContext .= ' (Unit ' . $unit->unit . ')';
            }
        }

        return new Content(
            markdown: 'emails.contact-submission',
            with: [
                "name" => $submission->name,
                "email" => $submission->email,
                "phone" => $submission->phone ?? "N/A",
                "message" => $submission->message,
                "propertyContext" => $propertyContext,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
