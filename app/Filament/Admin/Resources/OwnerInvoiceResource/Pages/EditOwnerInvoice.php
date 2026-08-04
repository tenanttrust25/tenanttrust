<?php

namespace App\Filament\Admin\Resources\OwnerInvoiceResource\Pages;

use App\Filament\Admin\Resources\OwnerInvoiceResource;
use App\Mail\OwnerInvoiceMail;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditOwnerInvoice extends EditRecord
{
    protected static string $resource = OwnerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("email")
                ->label("Email Invoice")
                ->icon("heroicon-o-envelope")
                ->color("warning")
                ->requiresConfirmation()
                ->modalHeading("Send Invoice to Client")
                ->modalContent(fn () => view("filament.admin.owner-invoice-email-confirm", [
                    "invoice" => $this->record,
                ]))
                ->action(function () {
                    $invoice = $this->record;
                    $owner = $invoice->owner;

                    if (!$owner?->email) {
                        Notification::make()
                            ->danger()
                            ->title("Client has no email address")
                            ->body("Invoice #{$invoice->invoice_number} can't be emailed — the client has no email on file.")
                            ->send();
                        return;
                    }

                    try {
                        Mail::to($owner->email)
                            ->send(new OwnerInvoiceMail($invoice));

                        Notification::make()
                            ->success()
                            ->title("Invoice emailed!")
                            ->body("Invoice #{$invoice->invoice_number} sent to {$owner->email}")
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title("Failed to send email")
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
