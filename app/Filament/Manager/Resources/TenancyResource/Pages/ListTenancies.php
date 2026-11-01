<?php

namespace App\Filament\Manager\Resources\TenancyResource\Pages;

use App\Filament\Manager\Resources\TenancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenancies extends ListRecords
{
    protected static string $resource = TenancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
