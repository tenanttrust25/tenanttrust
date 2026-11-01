<?php

namespace App\Filament\Admin\Resources\MailItemResource\Pages;

use App\Filament\Admin\Resources\MailItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailItem extends CreateRecord
{
    protected static string $resource = MailItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['country'] = $data['country'] ?? 'CA';
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        return $data;
    }
}
