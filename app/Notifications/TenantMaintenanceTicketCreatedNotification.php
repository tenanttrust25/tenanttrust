<?php

namespace App\Notifications;

use App\Models\MaintenanceTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantMaintenanceTicketCreatedNotification extends Notification
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
        $url = url("/tenant/maintenance-tickets/" . $ticket->id);
        $unit = $ticket->propertyUnit?->unit ? " — Unit " . $ticket->propertyUnit->unit : "";

        return (new MailMessage)
            ->subject("Maintenance ticket #" . $ticket->ticket_number . " created")
            ->greeting("Hello " . $notifiable->name . ",")
            ->line("A maintenance ticket has been created for your unit:")
            ->line("**" . $ticket->title . "**")
            ->line(($ticket->property?->title ?? "") . $unit)
            ->line("")
            ->line($ticket->description)
            ->action("View Your Ticket", $url)
            ->line("You can reply to this ticket, add photos, and follow updates from your Tenant Portal.");
    }
}
