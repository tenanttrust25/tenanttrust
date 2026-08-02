<?php

namespace App\Filament\Tenant\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Tenant\Resources\MaintenanceTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceTickets extends ListRecords
{
    protected static string $resource = MaintenanceTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
