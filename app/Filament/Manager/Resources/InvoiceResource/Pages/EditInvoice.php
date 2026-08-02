<?php

namespace App\Filament\Manager\Resources\InvoiceResource\Pages;

use App\Filament\Manager\Resources\InvoiceResource;
use App\Mail\InvoiceMail;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl("index");
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return "Invoice updated!";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("preview")
                ->label("Preview Invoice")
                ->icon("heroicon-o-eye")
                ->color("gray")
                ->modalHeading("Invoice Preview")
                ->modalWidth("7xl")
                ->modalContent(function () {
                    $invoice = $this->record;
                    return view("filament.manager.invoice.invoice-preview", [
                        "tenant_name" => $invoice->tenant?->name,
                        "tenant_email" => $invoice->tenant?->email,
                        "property_name" => $invoice->property?->title,
                        "amount" => $invoice->amount,
                        "description" => $invoice->description,
                        "invoice_number" => $invoice->invoice_number,
                        "due_date" => $invoice->due_date,
                        "status" => $invoice->status,
                    ]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel("Close")
                ->modalFooterActions(function () {
                    $invoice = $this->record;
                    if (!$invoice->tenant?->email) return [];

                    return [
                        Actions\Action::make("emailInvoice")
                            ->label("E-mail Invoice")
                            ->icon("heroicon-o-envelope")
                            ->color("warning")
                            ->requiresConfirmation()
                            ->modalHeading("Send Invoice to Tenant")
                            ->modalContent(view("filament.manager.invoice.invoice-email-confirm", [
                                "invoice" => $invoice,
                            ]))
                            ->action(function () use ($invoice) {
                                try {
                                    Mail::to($invoice->tenant->email)
                                        ->send(new InvoiceMail($invoice));

                                    Notification::make()
                                        ->success()
                                        ->title("Invoice emailed!")
                                        ->body("Invoice #{$invoice->invoice_number} sent to {$invoice->tenant->email}")
                                        ->send();
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title("Failed to send email")
                                        ->body($e->getMessage())
                                        ->send();
                                }
                            }),
                    ];
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
