<?php

namespace App\Filament\Manager\Resources\ComplianceItemResource\Pages;

use App\Filament\Manager\Resources\ComplianceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListComplianceItems extends ListRecords
{
    protected static string $resource = ComplianceItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
