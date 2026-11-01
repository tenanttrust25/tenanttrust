<?php

namespace App\Filament\Admin\Resources\OwnerFileResource\Pages;

use App\Filament\Admin\Resources\OwnerFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerFile extends EditRecord
{
    protected static string $resource = OwnerFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
