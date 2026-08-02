<?php

namespace App\Mail;

use App\Models\RentalApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RentalApplicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public RentalApplication $application;

    public function __construct(RentalApplication $application)
    {
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Rental Application from ' . $this->application->full_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.rental-application',
            with: [
                "application" => $this->application,
                "name" => $this->application->full_name,
                "email" => $this->application->email,
                "phone" => $this->application->phone,
                "date_of_birth" => $this->application->date_of_birth?->format('F j, Y') ?: 'Not provided',
                "address" => $this->application->address_applying_for,
                "previous_address" => $this->application->previous_address ?: "Not provided",
                "landlord_name" => $this->application->landlord_name ?: "Not provided",
                "landlord_contact" => $this->application->landlord_contact ?: "Not provided",
                "gov_id" => $this->application->gov_id_path ? 'Uploaded' : 'Not uploaded',
                "proof_of_employment" => $this->application->proof_of_employment_path ? 'Uploaded' : 'Not uploaded',
                "pets" => $this->application->has_pets
                    ? ($this->application->pets_details ?: "Yes")
                    : "No",
                "smoker" => $this->application->is_smoker ? "Yes" : "No",
                "credit_check" => $this->application->consent_credit_check ? "Consented" : "Not consented",
                "background_check" => $this->application->consent_background_check ? "Consented" : "Not consented",
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
