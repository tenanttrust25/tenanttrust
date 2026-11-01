<?php

namespace App\Filament\Tenant\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Tenant\Resources\MaintenanceTicketResource;
use App\Models\MaintenanceTicket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\StaffTicketReplyNotification;
use App\Services\PushNotificationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ViewMaintenanceTicket extends ViewRecord
{
    protected static string $resource = MaintenanceTicketResource::class;
    protected static string $view = "filament.tenant.view-ticket";

    public string $reply_message = "";

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // The resource query already scopes by tenancy, but never trust it alone.
        abort_unless(
            in_array($this->record->tenant_id, auth()->user()->getUnitTenantIds(), true),
            403
        );

        $this->record->load(["tenant", "property", "propertyUnit", "replies.user"]);
    }

    public function getTicket(): MaintenanceTicket
    {
        return $this->record;
    }

    public function getReplies()
    {
        return $this->record->replies()->with("user")->orderBy("created_at")->get();
    }

    public function sendReply(): void
    {
        $this->validate([
            "reply_message" => "required|string|min:1|max:2000",
        ]);

        $reply = TicketReply::create([
            "maintenance_ticket_id" => $this->record->id,
            "user_id" => auth()->id(),
            "message" => $this->reply_message,
        ]);

        // Notify staff (admins + managers) by email and push.
        try {
            $staff = User::whereIn("role", ["admin", "manager"])->get();

            foreach ($staff as $member) {
                try {
                    $member->notify(new StaffTicketReplyNotification($this->record, $reply));
                } catch (\Exception $e) {
                    Log::warning("Ticket reply staff email failed: " . $e->getMessage());
                }
            }

            if ($staff->isNotEmpty()) {
                app(PushNotificationService::class)->sendToUsers(
                    $staff,
                    "Tenant reply on ticket #" . $this->record->ticket_number,
                    ($reply->user?->name ?? "Tenant") . ": " . Str::limit($this->reply_message, 90),
                    url("/admin/maintenance-tickets/" . $this->record->id . "/edit")
                );
            }
        } catch (\Exception $e) {
            Log::warning("Ticket reply staff notification failed: " . $e->getMessage());
        }

        $this->reply_message = "";
        $this->record->refresh();

        Notification::make()
            ->title("Reply sent")
            ->success()
            ->send();
    }
}
