<?php

namespace App\Filament\Manager\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Manager\Resources\MaintenanceTicketResource;
use App\Models\TicketReply;
use App\Services\PushNotificationService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EditMaintenanceTicket extends EditRecord
{
    protected static string $resource = MaintenanceTicketResource::class;

    public ?string $newReply = "";

    public ?string $oldStatus = null;

    protected function getSavedNotificationTitle(): ?string
    {
        return "Ticket updated!";
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldStatus = $this->record->getOriginal("status");

        // Handle the reply if one was added
        if (!empty($this->newReply)) {
            TicketReply::create([
                "maintenance_ticket_id" => $this->record->id,
                "user_id" => Auth::id(),
                "message" => $this->newReply,
            ]);
            $this->newReply = "";
        }

        // Remove non-database fields
        unset($data["new_reply"]);

        return $data;
    }

    protected function afterSave(): void
    {
        // Reload replies
        $this->record->load("replies");

        // Push notification to tenant on status change
        if ($this->oldStatus && $this->oldStatus !== $this->record->status) {
            try {
                app(PushNotificationService::class)->sendToUser(
                    $this->record->tenant,
                    'Maintenance ticket status updated',
                    "Your ticket #{$this->record->ticket_number} is now " . str_replace("_", " ", $this->record->status) . ".",
                    url('/tenant/maintenance-tickets')
                );
            } catch (\Exception $e) {
                Log::warning("Ticket push failed: " . $e->getMessage());
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("save_and_reply")
                ->label("Save & Reply")
                ->color("warning")
                ->icon("heroicon-o-chat-bubble-left-right")
                ->form([
                    Textarea::make("reply_message")
                        ->label("Add a Reply")
                        ->rows(3)
                        ->required()
                        ->placeholder("Type your message..."),
                ])
                ->action(function (array $data) {
                    TicketReply::create([
                        "maintenance_ticket_id" => $this->record->id,
                        "user_id" => Auth::id(),
                        "message" => $data["reply_message"],
                    ]);

                    // Push notification to tenant
                    try {
                        app(PushNotificationService::class)->sendToUser(
                            $this->record->tenant,
                            'New reply on your maintenance ticket',
                            "There's a new reply on ticket #{$this->record->ticket_number}.",
                            url('/tenant/maintenance-tickets')
                        );
                    } catch (\Exception $e) {
                        Log::warning("Reply push failed: " . $e->getMessage());
                    }

                    $this->form->fill();

                    Notification::make()
                        ->success()
                        ->title("Reply sent to tenant!")
                        ->send();
                }),
        ];
    }
}
