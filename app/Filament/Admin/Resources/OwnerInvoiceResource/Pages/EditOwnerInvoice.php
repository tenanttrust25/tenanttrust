<?php

namespace App\Filament\Admin\Resources\OwnerInvoiceResource\Pages;

use App\Filament\Admin\Resources\OwnerInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOwnerInvoice extends EditRecord
{
    protected static string $resource = OwnerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
