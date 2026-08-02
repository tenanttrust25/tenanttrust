<?php

namespace App\Filament\Admin\Resources\MailCampaignResource\Pages;

use App\Filament\Admin\Resources\MailCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailCampaign extends EditRecord
{
    protected static string $resource = MailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
