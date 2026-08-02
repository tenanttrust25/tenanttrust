<?php

namespace App\Filament\Manager\Resources\UserResource\Pages;

use App\Filament\Manager\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("edit", ["record" => $this->record]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return "Tenant created! Now add their lease documents below.";
    }
}
