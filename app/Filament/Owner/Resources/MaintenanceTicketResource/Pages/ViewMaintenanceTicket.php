<?php

namespace App\Filament\Owner\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Owner\Resources\MaintenanceTicketResource;
use App\Models\MaintenanceTicket;
use App\Models\TicketReply;
use App\Notifications\TicketReplyNotification;
use App\Services\PushNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewMaintenanceTicket extends ViewRecord
{
    protected static string $resource = MaintenanceTicketResource::class;
    protected static string $view = "filament.owner.view-ticket";

    public MaintenanceTicket $ticket;
    public string $reply_message = "";

    public function mount($record): void
    {
        $this->ticket = MaintenanceTicket::with(["tenant", "property", "replies.user"])->findOrFail($record);
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make("updateStatus")
                ->label("Update Status")
                ->icon("heroicon-o-arrow-path")
                ->form([
                    Select::make("status")
                        ->label("New Status")
                        ->options([
                            "open" => "Open",
                            "in_progress" => "In Progress",
                            "resolved" => "Resolved",
                            "closed" => "Closed",
                        ])
                        ->default($this->ticket->status)
                        ->required(),
                    Textarea::make("note")
                        ->label("Note (optional)")
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    $oldStatus = $this->ticket->status;
                    $this->ticket->update(["status" => $data["status"]]);
                    if (!empty($data["note"])) {
                        $reply = TicketReply::create([
                            "maintenance_ticket_id" => $this->ticket->id,
                            "user_id" => Auth::id(),
                            "message" => "Status updated to: " . str_replace("_", " ", ucfirst($data["status"])) . "\n\n" . $data["note"],
                        ]);
                        // Notify tenant
                        try {
                            $this->ticket->tenant->notify(new TicketReplyNotification($this->ticket, $reply->message));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning("Failed to email tenant: " . $e->getMessage());
                        }
                    }

                    // Push notification to tenant on status change
                    if ($oldStatus !== $this->ticket->status) {
                        try {
                            app(PushNotificationService::class)->sendToUser(
                                $this->ticket->tenant,
                                'Maintenance ticket status updated',
                                "Your ticket #{$this->ticket->ticket_number} is now " . str_replace("_", " ", $this->ticket->status) . ".",
                                url('/tenant/maintenance-tickets')
                            );
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning("Ticket push failed: " . $e->getMessage());
                        }
                    }

                    Notification::make()->title("Status updated")->success()->send();
                    $this->redirect(MaintenanceTicketResource::getUrl("view", ["record" => $this->ticket]));
                }),
        ];
    }

    public function getTicket()
    {
        return $this->ticket;
    }

    public function getReplies()
    {
        return $this->ticket->replies()->with("user")->orderBy("created_at", "desc")->get();
    }

    public function sendReply(): void
    {
        $this->validate([
            "reply_message" => "required|string|min:1",
        ]);

        $reply = TicketReply::create([
            "maintenance_ticket_id" => $this->ticket->id,
            "user_id" => Auth::id(),
            "message" => $this->reply_message,
        ]);

        // Push notification to tenant
        try {
            app(PushNotificationService::class)->sendToUser(
                $this->ticket->tenant,
                'New reply on your maintenance ticket',
                "There's a new reply on ticket #{$this->ticket->ticket_number}.",
                url('/tenant/maintenance-tickets')
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Reply push failed: " . $e->getMessage());
        }

        // Send email notification to tenant
        try {
            $this->ticket->tenant->notify(new TicketReplyNotification($this->ticket, $this->reply_message));
            Notification::make()
                ->title("Reply sent — tenant notified by email")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title("Reply saved but email failed: " . $e->getMessage())
                ->warning()
                ->send();
            \Illuminate\Support\Facades\Log::warning("Failed to email tenant: " . $e->getMessage());
        }

        $this->reply_message = "";
        $this->ticket->refresh();
    }
}
