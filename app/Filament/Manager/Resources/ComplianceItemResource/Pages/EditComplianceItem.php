<?php

namespace App\Filament\Manager\Resources\ComplianceItemResource\Pages;

use App\Filament\Manager\Resources\ComplianceItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditComplianceItem extends EditRecord
{
    protected static string $resource = ComplianceItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return "Compliance item updated!";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
