<?php

namespace App\Filament\Admin\Resources\PaymentResource\Pages;

use App\Filament\Admin\Resources\PaymentResource;
use App\Models\Payment;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Transaction'),

            Actions\Action::make('generateFromInvoices')
                ->label('Generate From Invoices')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $invoices = \App\Models\Invoice::where('status', 'pending')->get();
                    $count = 0;

                    foreach ($invoices as $invoice) {
                        $exists = Payment::where('invoice_id', $invoice->id)->exists();
                        if ($exists) continue;

                        Payment::create([
                            'invoice_id' => $invoice->id,
                            'tenant_id' => $invoice->tenant_id,
                            'amount' => $invoice->amount,
                            'payment_method' => 'cash',
                            'status' => 'pending',
                        ]);
                        $count++;
                    }

                    Notification::make()
                        ->title("$count pending payments created from invoices")
                        ->success()
                        ->send();
                }),
        ];
    }
}
