<?php

namespace App\Filament\Admin\Resources\CallLogEntryResource\Pages;

use App\Filament\Admin\Resources\CallLogEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallLogEntries extends ListRecords
{
    protected static string $resource = CallLogEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
