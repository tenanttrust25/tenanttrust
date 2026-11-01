<?php

namespace App\Notifications;

use App\Models\MaintenanceTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OwnerMaintenanceTicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MaintenanceTicket $ticket
    ) {
    }

    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->ticket;
        $url = url("/owner/maintenance/" . $ticket->id);
        $unit = $ticket->propertyUnit?->unit ? " — Unit " . $ticket->propertyUnit->unit : "";
        $propertyName = $ticket->property?->title ?? "your property";

        return (new MailMessage)
            ->subject("New maintenance ticket #" . $ticket->ticket_number . " — " . $propertyName)
            ->greeting("Hello " . $notifiable->name . ",")
            ->line("A new maintenance ticket has been created for " . $propertyName . $unit . ".")
            ->line("**" . $ticket->title . "**")
            ->line("Tenant: " . ($ticket->tenant?->name ?? "—"))
            ->line("")
            ->line($ticket->description)
            ->action("View Ticket", $url)
            ->line("You can follow the conversation and any repair costs from your Client Portal.");
    }
}
