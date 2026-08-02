<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $tempPassword;
    public string $loginUrl;

    public function __construct(User $user, string $tempPassword, string $loginUrl)
    {
        $this->user = $user;
        $this->tempPassword = $tempPassword;
        $this->loginUrl = $loginUrl;
    }

    public function envelope(): Envelope
    {
        $role = $this->user->role === "manager" ? "Property Manager" : "Client";
        return new Envelope(
            subject: "Welcome to Tenant Trust — Your {$role} Account",
        );
    }

    public function content(): Content
    {
        $role = $this->user->role === "manager" ? "Property Manager" : "Client";
        return new Content(
            markdown: "emails.user-invitation",
            with: [
                "user" => $this->user,
                "role" => $role,
                "tempPassword" => $this->tempPassword,
                "loginUrl" => $this->loginUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
