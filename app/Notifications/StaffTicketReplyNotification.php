<?php

namespace App\Notifications;

use App\Models\MaintenanceTicket;
use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffTicketReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MaintenanceTicket $ticket,
        public TicketReply $reply
    ) {
    }

    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url("/admin/maintenance-tickets/" . $this->ticket->id . "/edit");
        $who = $this->reply->user?->name ?? "Tenant";

        return (new MailMessage)
            ->subject("Tenant reply on ticket #" . $this->ticket->ticket_number)
            ->greeting("New tenant reply")
            ->line($who . " replied to ticket #" . $this->ticket->ticket_number . " — " . $this->ticket->title)
            ->line("")
            ->line($this->reply->message)
            ->action("Open Ticket", $url)
            ->line("Reply from the admin panel to keep the conversation going.");
    }
}
