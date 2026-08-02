<?php

namespace App\Filament\Admin\Resources\TenantFileResource\Pages;

use App\Filament\Admin\Resources\TenantFileResource;
use Filament\Resources\Pages\ListRecords;

class ListTenantFiles extends ListRecords
{
    protected static string $resource = TenantFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
