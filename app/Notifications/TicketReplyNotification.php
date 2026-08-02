<?php

namespace App\Notifications;

use App\Models\MaintenanceTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketReplyNotification extends Notification
{
    use Queueable;

    public MaintenanceTicket $ticket;
    public string $replyMessage;

    public function __construct(MaintenanceTicket $ticket, string $replyMessage)
    {
        $this->ticket = $ticket;
        $this->replyMessage = $replyMessage;
    }

    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url("/tenant/maintenance-tickets");

        return (new MailMessage)
            ->subject("New Reply on Ticket #" . $this->ticket->ticket_number)
            ->greeting("Hello " . $notifiable->name . ",")
            ->line("There is a new reply on your maintenance ticket:")
            ->line("**" . $this->ticket->title . "**")
            ->line("")
            ->line($this->replyMessage)
            ->action("View Ticket", $url)
            ->line("Please log into your Tenant Portal to view and respond.");
    }
}
