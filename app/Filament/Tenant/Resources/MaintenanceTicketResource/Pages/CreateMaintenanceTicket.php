<?php

namespace App\Filament\Tenant\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Tenant\Resources\MaintenanceTicketResource;
use App\Models\Tenancy;
use App\Models\User;
use App\Services\PushNotificationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateMaintenanceTicket extends CreateRecord
{
    protected static string $resource = MaintenanceTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data["tenant_id"] = Auth::id();
        $data["status"] = "open";
        $data["ticket_number"] = "TKT-" . strtoupper(Str::random(8));

        // Auto-set property from active tenancy if not already set
        if (empty($data["property_id"])) {
            $tenancy = Tenancy::where("tenant_id", Auth::id())
                ->where("status", "active")
                ->first();
            $data["property_id"] = $tenancy?->property_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function afterCreate(): void
    {
        // Notify the property owner about the new ticket
        $owner = $this->record->property?->owner;
        if ($owner) {
            try {
                app(PushNotificationService::class)->sendToUser(
                    $owner,
                    'New maintenance request',
                    "{$this->record->tenant?->name} submitted: {$this->record->title} (#{$this->record->ticket_number})",
                    url('/owner/maintenance')
                );
            } catch (\Exception $e) {
                Log::warning("Owner ticket push failed: " . $e->getMessage());
            }
        }

        // Notify property managers about the new ticket
        try {
            $managers = User::whereIn('role', ['manager', 'admin'])->get();
            if ($managers->isNotEmpty()) {
                app(PushNotificationService::class)->sendToUsers(
                    $managers,
                    'New maintenance request',
                    "{$this->record->tenant?->name} submitted: {$this->record->title} (#{$this->record->ticket_number})",
                    url('/manager/maintenance-tickets')
                );
            }
        } catch (\Exception $e) {
            Log::warning("Manager ticket push failed: " . $e->getMessage());
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Maintenance request submitted successfully!";
    }
}
