<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use App\Services\PushNotificationService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use App\Models\PropertyUnit;
use App\Models\Invoice;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('generateAll')
                ->label('Generate All Invoices')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $units = PropertyUnit::with('property')->where(function ($q) {
                        $q->whereNotNull('tenant_id');
                    })->get();

                    $count = 0;
                    $nextMonth = now()->addMonth()->startOfMonth();
                    $notifiedTenantIds = [];

                    foreach ($units as $unit) {
                        $tenantIds = [$unit->tenant_id];
                        if ($unit->tenant2_id) $tenantIds[] = $unit->tenant2_id;

                        foreach ($tenantIds as $tenantId) {
                            if (!$tenantId || !$unit->rent_amount) continue;

                            Invoice::create([
                                'tenant_id' => $tenantId,
                                'property_id' => $unit->property_id,
                                'property_unit_id' => $unit->id,
                                'invoice_number' => 'INV-' . now()->format('Ym') . '-' . str_pad($unit->id, 4, '0', STR_PAD_LEFT) . '-' . ($count + 1),
                                'amount' => $unit->rent_amount,
                                'description' => 'Monthly Rent - Unit ' . ($unit->unit ?? '') . ($unit->property ? ' - ' . $unit->property->title : ''),
                                'due_date' => $nextMonth,
                                'status' => 'pending',
                            ]);
                            $count++;
                            $notifiedTenantIds[$tenantId] = true;
                        }
                    }

                    // Push notification to each tenant
                    $push = app(PushNotificationService::class);
                    foreach (array_keys($notifiedTenantIds) as $tenantId) {
                        try {
                            $push->sendToUser(
                                \App\Models\User::find($tenantId),
                                'New rent invoice ready',
                                "Your rent invoice for " . $nextMonth->format('F j, Y') . " is ready to view and pay.",
                                url('/tenant/invoices')
                            );
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::warning("Invoice push failed for tenant {$tenantId}: " . $e->getMessage());
                        }
                    }

                    Notification::make()
                        ->title("$count invoices generated successfully")
                        ->success()
                        ->send();
                }),
        ];
    }
}
