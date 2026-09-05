<?php

namespace App\Filament\Admin\Resources\ReminderResource\Pages;

use App\Filament\Admin\Resources\ReminderResource;
use Filament\Resources\Pages\ListRecords;

class ListReminders extends ListRecords
{
    protected static string $resource = ReminderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
