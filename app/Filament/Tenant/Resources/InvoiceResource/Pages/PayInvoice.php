<?php

namespace App\Filament\Tenant\Resources\InvoiceResource\Pages;

use App\Filament\Tenant\Resources\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class PayInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = "filament.tenant.pay";

    protected static ?string $title = "Pay Invoice";
}
