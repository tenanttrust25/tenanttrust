<?php

namespace App\Filament\Admin\Resources\FormResource\Pages;

use App\Filament\Admin\Resources\FormResource;
use Filament\Resources\Pages\ListRecords;

class ListForms extends ListRecords
{
    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
