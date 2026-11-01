<?php

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Models\Invoice;
use App\Models\PropertyUnit;
use App\Models\User;
use App\Services\PushNotificationService;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class GenerateInvoicesAction
{
    public static function run(): void
    {
        $units = PropertyUnit::with(['property', 'tenants'])
            ->whereHas('tenants', function ($q) {
                $q->where('status', 'active');
            })
            ->get();

        $count = 0;
        $nextMonth = now()->addMonth();
        $dueDate = $nextMonth->startOfMonth();
        $notifiedTenantIds = [];

        foreach ($units as $unit) {
            foreach ($unit->tenants as $tenancy) {
                if (!$tenancy->tenant_id || !$unit->rent_amount) continue;

                $invNumber = 'INV-' . now()->format('Ym') . '-' . Str::padLeft($tenancy->id, 4, '0');

                Invoice::create([
                    'tenant_id' => $tenancy->tenant_id,
                    'property_id' => $unit->property_id,
                    'property_unit_id' => $unit->id,
                    'invoice_number' => $invNumber,
                    'type' => 'rent',
                    'amount' => $unit->rent_amount,
                    'description' => 'Monthly Rent - ' . $unit->unit . ' - ' . ($unit->property->title ?? ''),
                    'due_date' => $dueDate,
                    'status' => 'pending',
                ]);
                $count++;
                $notifiedTenantIds[$tenancy->tenant_id] = true;
            }
        }

        // Push notification to each tenant
        $push = app(PushNotificationService::class);
        foreach (array_keys($notifiedTenantIds) as $tenantId) {
            try {
                $push->sendToUser(
                    User::find($tenantId),
                    'New rent invoice ready',
                    "Your rent invoice for " . $dueDate->format('F j, Y') . " is ready to view and pay.",
                    url('/tenant/invoices')
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Invoice push failed for tenant {$tenantId}: " . $e->getMessage());
            }
        }

        Notification::make()
            ->title("$count invoices generated")
            ->success()
            ->send();
    }
}
