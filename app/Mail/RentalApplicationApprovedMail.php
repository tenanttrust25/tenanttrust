<?php

namespace App\Mail;

use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentalApplicationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Business details the applicant needs. Edit here to change every
     * future approval email.
     */
    public const ETRANSFER_EMAIL = 'msugrim1@gmail.com';
    public const ETRANSFER_NAME = 'Mike Sugrim';
    public const INSURANCE_PROVIDER = 'Square One';
    public const INSURANCE_URL = 'https://www.squareone.com';

    public RentalApplication $application;

    public function __construct(RentalApplication $application)
    {
        $this->application = $application;
    }

    /**
     * Monthly rent from the unit the applicant applied for (null if unknown).
     */
    public function monthlyRent(): ?float
    {
        $rent = $this->application->propertyUnit?->rent_amount;

        return ($rent && (float) $rent > 0) ? (float) $rent : null;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Good news — your Tenant Trust application has been approved',
        );
    }

    public function content(): Content
    {
        $rent = $this->monthlyRent();

        return new Content(
            markdown: 'emails.rental-application-approved',
            with: [
                'application' => $this->application,
                'name' => $this->application->first_name ?: $this->application->full_name,
                'address' => $this->application->address_applying_for,
                'rent' => $rent,
                'totalDue' => $rent ? $rent * 2 : null,
                'etransferEmail' => self::ETRANSFER_EMAIL,
                'etransferName' => self::ETRANSFER_NAME,
                'portalUrl' => url('/tenant/login'),
                'insuranceProvider' => self::INSURANCE_PROVIDER,
                'insuranceUrl' => self::INSURANCE_URL,
            ],
        );
    }
}
