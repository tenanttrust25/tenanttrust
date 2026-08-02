<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Models\Payment;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->required()
                        ->default(fn () => $this->record->amount)
                        ->prefix('$'),
                    \Filament\Forms\Components\Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash' => 'Cash',
                            'cheque' => 'Cheque',
                            'interac' => 'Interac',
                            'credit_card' => 'Credit Card',
                            'bank_transfer' => 'Bank Transfer',
                        ])
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('transaction_id')
                        ->label('Transaction / Reference #')
                        ->maxLength(255),
                    \Filament\Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Payment Date')
                        ->default(now()),
                ])
                ->action(function (array $data) {
                    Payment::create([
                        'invoice_id' => $this->record->id,
                        'tenant_id' => $this->record->tenant_id,
                        'amount' => $data['amount'],
                        'payment_method' => $data['payment_method'],
                        'transaction_id' => $data['transaction_id'] ?? null,
                        'status' => 'completed',
                        'paid_at' => $data['paid_at'],
                    ]);

                    Notification::make()
                        ->title('Payment recorded successfully')
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
