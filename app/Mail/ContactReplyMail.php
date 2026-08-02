<?php

namespace App\Mail;

use App\Models\ContactSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public ContactSubmission $submission;
    public string $replySubject;
    public string $replyMessage;

    public function __construct(ContactSubmission $submission, string $replySubject, string $replyMessage)
    {
        $this->submission = $submission;
        $this->replySubject = $replySubject;
        $this->replyMessage = $replyMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replySubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-reply',
            with: [
                'submission' => $this->submission,
                'replyMessage' => $this->replyMessage,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
