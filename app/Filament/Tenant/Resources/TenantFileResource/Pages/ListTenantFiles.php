<?php

namespace App\Filament\Tenant\Resources\TenantFileResource\Pages;

use App\Filament\Tenant\Resources\TenantFileResource;
use Filament\Resources\Pages\ListRecords;

class ListTenantFiles extends ListRecords
{
    protected static string $resource = TenantFileResource::class;
}
