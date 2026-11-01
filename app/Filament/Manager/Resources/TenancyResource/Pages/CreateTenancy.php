<?php

namespace App\Filament\Manager\Resources\TenancyResource\Pages;

use App\Filament\Manager\Resources\TenancyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenancy extends CreateRecord
{
    protected static string $resource = TenancyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Lease created!";
    }
}
