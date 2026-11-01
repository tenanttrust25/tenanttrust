<?php

namespace App\Filament\Admin\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Admin\Resources\MaintenanceTicketResource;
use App\Models\TicketReply;
use App\Notifications\TicketReplyNotification;
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

    protected function getSavedNotificationTitle(): ?string
    {
        return "Ticket updated!";
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // "Add a Reply" box on the form: post a reply and notify the tenant
        // whenever a non-empty message was typed, then keep it out of the
        // model save (there is no new_reply column).
        $reply = trim((string) ($data["new_reply"] ?? ""));

        if ($reply !== "") {
            $this->saveTenantReply($reply);
        }

        unset($data["new_reply"]);

        return $data;
    }

    protected function saveTenantReply(string $message): void
    {
        TicketReply::create([
            "maintenance_ticket_id" => $this->record->id,
            "user_id" => Auth::id(),
            "message" => $message,
        ]);

        $this->notifyTenant($message);
    }

    protected function notifyTenant(string $message): void
    {
        $tenant = $this->record->tenant;

        if (! $tenant) {
            return;
        }

        try {
            app(PushNotificationService::class)->sendToUser(
                $tenant,
                "New reply on your maintenance ticket",
                "There's a new reply on ticket #{$this->record->ticket_number}.",
                url("/tenant/maintenance-tickets")
            );
        } catch (\Throwable $e) {
            Log::warning("Ticket reply push failed: " . $e->getMessage());
        }

        try {
            $tenant->notify(new TicketReplyNotification($this->record, $message));
        } catch (\Throwable $e) {
            Log::warning("Ticket reply email failed: " . $e->getMessage());
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
                        ->label("Reply to Tenant")
                        ->rows(3)
                        ->required()
                        ->placeholder("Type your message..."),
                ])
                ->action(function (array $data) {
                    $this->saveTenantReply($data["reply_message"]);

                    Notification::make()
                        ->success()
                        ->title("Reply sent to tenant!")
                        ->send();
                }),
        ];
    }
}
