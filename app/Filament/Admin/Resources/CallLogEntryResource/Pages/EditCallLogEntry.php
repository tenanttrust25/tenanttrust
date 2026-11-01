<?php

namespace App\Filament\Admin\Resources\CallLogEntryResource\Pages;

use App\Filament\Admin\Resources\CallLogEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCallLogEntry extends EditRecord
{
    protected static string $resource = CallLogEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
