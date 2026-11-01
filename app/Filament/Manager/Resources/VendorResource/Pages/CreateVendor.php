<?php

namespace App\Filament\Manager\Resources\VendorResource\Pages;

use App\Filament\Manager\Resources\VendorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Vendor added!";
    }
}
