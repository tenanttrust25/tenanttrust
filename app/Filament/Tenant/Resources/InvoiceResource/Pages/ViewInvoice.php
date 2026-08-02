<?php

namespace App\Filament\Tenant\Resources\InvoiceResource\Pages;

use App\Filament\Tenant\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = "filament.tenant.invoice-view";

    protected function getHeaderActions(): array
    {
        $record = $this->record;
        $isPayable = fn () => in_array($record->status, ["pending", "overdue"]);

        return [
            Actions\Action::make("pay")
                ->label("Pay Now")
                ->icon("heroicon-o-credit-card")
                ->color("success")
                ->button()
                ->size("lg")
                ->visible($isPayable)
                ->url(fn () => InvoiceResource::getUrl("pay", ["record" => $record])),
        ];
    }
}
