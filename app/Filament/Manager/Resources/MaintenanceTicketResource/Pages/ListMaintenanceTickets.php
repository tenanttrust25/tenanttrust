<?php

namespace App\Filament\Manager\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Manager\Resources\MaintenanceTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceTickets extends ListRecords
{
    protected static string $resource = MaintenanceTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
