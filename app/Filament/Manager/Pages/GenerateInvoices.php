<?php

namespace App\Filament\Manager\Pages;

use App\Models\Invoice;
use App\Models\Tenancy;
use App\Models\User;
use App\Services\PushNotificationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class GenerateInvoices extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-document-plus";
    protected static ?string $navigationLabel = "Generate Invoices";
    protected static ?string $navigationGroup = "Rent & Billing";
    protected static ?string $title = "Generate Monthly Invoices";
    protected static ?string $slug = "generate-invoices";
    protected static ?int $navigationSort = 2;

    protected static string $view = "filament.manager.pages.generate-invoices";

    public function getViewData(): array
    {
        $tenancies = Tenancy::with(["tenant", "property"])
            ->where("status", "active")
            ->get()
            ->map(function ($t) {
                $unitText = $t->unit ? "Unit {$t->unit} - " : "";
                return [
                    "tenant_name" => $t->tenant->name,
                    "tenant_email" => $t->tenant->email,
                    "property_name" => $t->property?->title ?? "N/A",
                    "property_address" => $t->property ? "{$t->property->address}, {$t->property->city}" : "",
                    "unit" => $t->unit,
                    "amount" => $t->rent_amount,
                    "description" => "Monthly Rent for {$unitText}{$t->property?->title}, {$t->property?->address}, {$t->property?->city}",
                    "tenancy_id" => $t->id,
                    "tenant_id" => $t->tenant_id,
                    "property_id" => $t->property_id,
                ];
            });

        return [
            "tenancies" => $tenancies,
            "totalAmount" => $tenancies->sum("amount"),
            "count" => $tenancies->count(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make("generate")
                ->label("Generate All Invoices")
                ->icon("heroicon-o-document-plus")
                ->color("success")
                ->size("lg")
                ->requiresConfirmation()
                ->modalHeading("Generate Monthly Invoices")
                ->modalDescription("This will create invoices for all active tenants. Continue?")
                ->action(function () {
                    $tenancies = Tenancy::with(["tenant", "property"])
                        ->where("status", "active")
                        ->get();

                    if ($tenancies->isEmpty()) {
                        Notification::make()
                            ->warning()
                            ->title("No active tenancies found")
                            ->body("There are no tenants with active tenancies to invoice.")
                            ->send();
                        return;
                    }

                    $created = 0;
                    $errors = [];
                    $now = now();
                    $dueDate = $now->copy()->addMonth()->startOfMonth();
                    $notifiedTenantIds = [];

                    foreach ($tenancies as $tenancy) {
                        try {
                            $unitText = $tenancy->unit ? "Unit {$tenancy->unit} - " : "";
                            $prop = $tenancy->property;
                            $description = "Monthly Rent for {$unitText}{$prop->title}, {$prop->address}, {$prop->city}";

                            Invoice::create([
                                "tenant_id" => $tenancy->tenant_id,
                                "property_id" => $tenancy->property_id,
                                "invoice_number" => "INV-" . date("Y") . "-" . strtoupper(Str::random(8)),
                                "type" => "rent",
                                "amount" => $tenancy->rent_amount,
                                "description" => $description,
                                "due_date" => $dueDate,
                                "status" => "pending",
                            ]);

                            $created++;
                            $notifiedTenantIds[$tenancy->tenant_id] = true;
                        } catch (\Exception $e) {
                            $errors[] = "{$tenancy->tenant->name}: {$e->getMessage()}";
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
                        ->success()
                        ->title("{$created} invoice(s) generated!")
                        ->body($created > 0
                            ? "Invoices created with due date of {$dueDate->format('F j, Y')}."
                            : "No invoices were created.")
                        ->send();

                    if (!empty($errors)) {
                        Notification::make()
                            ->danger()
                            ->title("Errors")
                            ->body(implode("\n", $errors))
                            ->send();
                    }
                }),
        ];
    }
}
