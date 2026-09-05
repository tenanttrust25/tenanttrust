<?php

namespace App\Filament\Admin\Resources\ReminderResource\Pages;

use App\Filament\Admin\Resources\ReminderResource;
use Filament\Resources\Pages\EditRecord;

class EditReminder extends EditRecord
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
