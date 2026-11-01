<?php

namespace App\Filament\Admin\Resources\MailItemResource\Pages;

use App\Filament\Admin\Resources\MailItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMailItems extends ListRecords
{
    protected static string $resource = MailItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add letter'),
        ];
    }
}
