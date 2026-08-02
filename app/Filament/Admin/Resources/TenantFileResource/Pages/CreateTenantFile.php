<?php

namespace App\Filament\Admin\Resources\TenantFileResource\Pages;

use App\Filament\Admin\Resources\TenantFileResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTenantFile extends CreateRecord
{
    protected static string $resource = TenantFileResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data["uploaded_by"] = Auth::id();
        return $data;
    }
}
