<?php

namespace App\Filament\Admin\Resources\MailCampaignResource\Pages;

use App\Filament\Admin\Resources\MailCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMailCampaigns extends ListRecords
{
    protected static string $resource = MailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MailCampaignResource\Widgets\CampaignStatsOverview::class,
        ];
    }
}
