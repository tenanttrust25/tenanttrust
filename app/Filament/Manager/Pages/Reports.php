<?php

namespace App\Filament\Manager\Pages;

use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Tenancy;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-chart-bar";
    protected static ?string $navigationLabel = "Reports";
    protected static ?string $navigationGroup = "Operations";
    protected static ?string $slug = "reports";
    protected static ?string $title = "Reports";
    protected static ?int $navigationSort = 2;

    protected static string $view = "filament.manager.pages.reports";

    public function getViewData(): array
    {
        // Rent roll — active leases
        $rentRoll = Tenancy::with(["tenant", "property"])
            ->where("status", "active")
            ->orderBy("property_id")
            ->get();
        $rentRollTotal = (float) $rentRoll->sum("rent_amount");

        // Arrears — total outstanding balance aggregated per tenant
        $arrears = Invoice::with(["tenant", "property"])
            ->whereIn("status", ["pending", "overdue"])
            ->get()
            ->map(function (Invoice $invoice) {
                $paid = (float) $invoice->payments()->where("status", "completed")->sum("amount");
                $invoice->outstanding = max(0, (float) $invoice->amount - $paid);

                return $invoice;
            })
            ->filter(fn (Invoice $invoice) => $invoice->outstanding > 0)
            ->groupBy(fn (Invoice $invoice) => $invoice->tenant_id ?? 0)
            ->map(function ($invoices) {
                $first = $invoices->first();
                $oldest = $invoices->sortBy(fn (Invoice $invoice) => $invoice->due_date?->timestamp ?? PHP_INT_MAX)->first();

                return [
                    "tenant" => $first->tenant?->name ?? "Unknown tenant",
                    "invoices" => $invoices->count(),
                    "outstanding" => (float) $invoices->sum("outstanding"),
                    "oldest_due" => $oldest?->due_date,
                ];
            })
            ->sortByDesc("outstanding")
            ->values();
        $arrearsTotal = (float) $arrears->sum("outstanding");

        // Vacancy — units with no active lease
        $occupiedUnitIds = Tenancy::where("status", "active")
            ->whereNotNull("property_unit_id")
            ->pluck("property_unit_id")
            ->all();
        $vacantUnits = PropertyUnit::whereNotIn("id", $occupiedUnitIds)->get();
        $propertyTitles = Property::pluck("title", "id");

        // Maintenance summary
        $maintByStatus = MaintenanceTicket::selectRaw("status, count(*) as c")
            ->groupBy("status")
            ->pluck("c", "status");
        $maintTotalCost = (float) MaintenanceTicket::sum("cost");

        return [
            "rentRoll" => $rentRoll,
            "rentRollTotal" => $rentRollTotal,
            "arrears" => $arrears,
            "arrearsTotal" => $arrearsTotal,
            "vacantUnits" => $vacantUnits,
            "propertyTitles" => $propertyTitles,
            "maintByStatus" => $maintByStatus,
            "maintTotalCost" => $maintTotalCost,
        ];
    }
}
