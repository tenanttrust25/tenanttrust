<?php

namespace App\Filament\Manager\Resources\TenancyResource\Pages;

use App\Filament\Manager\Resources\TenancyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenancy extends EditRecord
{
    protected static string $resource = TenancyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return "Lease updated!";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
