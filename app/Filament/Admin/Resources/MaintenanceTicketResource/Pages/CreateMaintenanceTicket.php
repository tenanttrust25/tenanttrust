<?php

namespace App\Filament\Admin\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Admin\Resources\MaintenanceTicketResource;
use App\Models\MaintenanceTicket;
use App\Notifications\OwnerMaintenanceTicketCreatedNotification;
use App\Notifications\TenantMaintenanceTicketCreatedNotification;
use App\Services\PushNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateMaintenanceTicket extends CreateRecord
{
    protected static string $resource = MaintenanceTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data["ticket_number"] = MaintenanceTicket::generateTicketNumber();
        $data["status"] = "open";

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("edit", ["record" => $this->record]);
    }

    protected function afterCreate(): void
    {
        $tenant = $this->record->tenant;

        if (! $tenant) {
            return;
        }

        // Email the tenant a direct link to this ticket. Filament's login
        // honors the intended URL, so they land on the ticket after signing in.
        try {
            $tenant->notify(new TenantMaintenanceTicketCreatedNotification($this->record));
        } catch (\Exception $e) {
            Log::warning("Tenant ticket email failed: " . $e->getMessage());
        }

        try {
            app(PushNotificationService::class)->sendToUser(
                $tenant,
                "New maintenance ticket",
                "Ticket #" . $this->record->ticket_number . ": " . $this->record->title,
                url("/tenant/maintenance-tickets/" . $this->record->id)
            );
        } catch (\Exception $e) {
            Log::warning("Tenant ticket push failed: " . $e->getMessage());
        }

        // Keep the landlord in the loop too — portal visibility alone means
        // they only find out if they happen to log in.
        $owner = $this->record->property?->owner;

        if ($owner) {
            try {
                $owner->notify(new OwnerMaintenanceTicketCreatedNotification($this->record));
            } catch (\Exception $e) {
                Log::warning("Owner ticket email failed: " . $e->getMessage());
            }

            try {
                app(PushNotificationService::class)->sendToUser(
                    $owner,
                    "New maintenance ticket",
                    "Ticket #" . $this->record->ticket_number . ": " . $this->record->title,
                    url("/owner/maintenance/" . $this->record->id)
                );
            } catch (\Exception $e) {
                Log::warning("Owner ticket push failed: " . $e->getMessage());
            }
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Ticket created — tenant notified by email";
    }
}
