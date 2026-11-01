<?php

namespace App\Filament\Manager\Resources\ComplianceItemResource\Pages;

use App\Filament\Manager\Resources\ComplianceItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateComplianceItem extends CreateRecord
{
    protected static string $resource = ComplianceItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Compliance item created!";
    }
}
