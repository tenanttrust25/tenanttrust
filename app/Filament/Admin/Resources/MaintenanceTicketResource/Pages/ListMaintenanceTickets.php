<?php

namespace App\Filament\Admin\Resources\MaintenanceTicketResource\Pages;

use App\Filament\Admin\Resources\MaintenanceTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceTickets extends ListRecords
{
    protected static string $resource = MaintenanceTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label("Add New Ticket")
                ->icon("heroicon-o-plus"),
        ];
    }
}
