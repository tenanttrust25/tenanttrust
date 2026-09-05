<?php

namespace App\Mail;

use App\Models\Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RemindersDueMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var \Illuminate\Support\Collection<int, Reminder> */
    public $reminders;

    public function __construct($reminders)
    {
        $this->reminders = $reminders;
    }

    public function envelope(): Envelope
    {
        $count = $this->reminders->count();

        return new Envelope(
            subject: $count === 1
                ? '1 reminder is due — check the Tenant Trust admin'
                : $count . ' reminders are due — check the Tenant Trust admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reminders-due',
            with: [
                'reminders' => $this->reminders->map(fn (Reminder $r): array => [
                    'title' => $r->title,
                    'tenant' => $r->tenant?->name ?? '—',
                    'remind_date' => $r->remind_date?->format('M j, Y'),
                    'due_date' => $r->due_date?->format('M j, Y') ?? '—',
                ]),
            ],
        );
    }
}
