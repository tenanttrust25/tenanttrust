<?php

namespace App\Mail;

use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentalApplicationDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Reasons that are NEVER put to the applicant in writing (credit /
     * character grounds). Everything else may be listed in the email.
     * Keep these values identical to the labels in
     * RentalApplicationResource::declineReasons().
     */
    public const INTERNAL_ONLY_REASONS = [
        'Credit concerns',
        'Eviction history',
        'Rental history concerns',
        'Insufficient income',
    ];

    public RentalApplication $application;

    public function __construct(RentalApplication $application)
    {
        $this->application = $application;
    }

    /**
     * The subset of decline reasons that are safe to show the applicant.
     */
    public function visibleReasons(): array
    {
        $reasons = $this->application->decline_reasons ?? [];

        if (!is_array($reasons)) {
            return [];
        }

        return array_values(array_filter(
            $reasons,
            fn ($reason) => !in_array($reason, self::INTERNAL_ONLY_REASONS, true)
        ));
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your rental application — Tenant Trust',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.rental-application-declined',
            with: [
                'application' => $this->application,
                'name' => $this->application->first_name ?: $this->application->full_name,
                'address' => $this->application->address_applying_for,
                'reasons' => $this->visibleReasons(),
            ],
        );
    }
}
