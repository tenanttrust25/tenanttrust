<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenancy;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class FinancialTrackingWidget extends Widget
{
    protected static string $view = "filament.manager.widgets.financial-tracking";
    protected static ?string $heading = "💰 Financial Tracking";
    protected int | string | array $columnSpan = "full";
    protected static ?string $pollingInterval = "120s";

    public function getViewData(): array
    {
        $now = now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // --- Total Monthly Rent Roll from all active tenancies ---
        $monthlyRentRoll = Tenancy::where("status", "active")->sum("rent_amount");
        $activeLeases = Tenancy::where("status", "active")->count();

        // --- Actual collected payments this month ---
        $collectedRent = Payment::where("status", "completed")
            ->whereMonth("paid_at", $currentMonth)
            ->whereYear("paid_at", $currentYear)
            ->sum("amount");

        $collectionRate = $monthlyRentRoll > 0 ? round(($collectedRent / $monthlyRentRoll) * 100) : 0;

        // Invoices that exist (from the generate invoices feature)
        $overdueAmount = Invoice::where("status", "overdue")->sum("amount");
        $overdueCount = Invoice::where("status", "overdue")->count();

        $pendingAmount = Invoice::where("status", "pending")->sum("amount");
        $pendingCount = Invoice::where("status", "pending")->count();

        // --- Monthly Revenue & Expenses (6-month chart) ---
        $monthlyRevenue = [];
        $monthlyExpenses = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = $now->copy()->subMonths($i);
            $labels[] = $date->format("M");

            $revenue = Payment::where("status", "completed")
                ->whereMonth("paid_at", $date->month)
                ->whereYear("paid_at", $date->year)
                ->sum("amount");
            $monthlyRevenue[] = (float) $revenue;

            $costs = \App\Models\MaintenanceTicket::whereNotNull("cost")
                ->whereMonth("created_at", $date->month)
                ->whereYear("created_at", $date->year)
                ->sum("cost");
            $monthlyExpenses[] = (float) $costs;
        }

        // --- Vacant units and lost revenue ---
        $totalUnitsAcrossProperties = Property::sum("unit_count");
        $totalLeasedUnits = Tenancy::where("status", "active")->count();
        $vacantUnits = max(0, $totalUnitsAcrossProperties - $totalLeasedUnits);

        $averageRent = $activeLeases > 0 ? $monthlyRentRoll / $activeLeases : 0;
        $vacantLostRevenue = $vacantUnits * $averageRent;

        // --- Year-to-Date ---
        $yearlyRevenue = Payment::where("status", "completed")
            ->whereYear("paid_at", $currentYear)
            ->sum("amount");

        $yearlyExpenses = \App\Models\MaintenanceTicket::whereNotNull("cost")
            ->whereYear("created_at", $currentYear)
            ->sum("cost");

        $netIncome = $yearlyRevenue - $yearlyExpenses;

        return [
            "monthlyRentRoll" => $monthlyRentRoll,
            "activeLeases" => $activeLeases,
            "collectedRent" => $collectedRent,
            "collectionRate" => $collectionRate,
            "overdueAmount" => $overdueAmount,
            "overdueCount" => $overdueCount,
            "pendingAmount" => $pendingAmount,
            "pendingCount" => $pendingCount,
            "labels" => json_encode($labels),
            "monthlyRevenue" => json_encode($monthlyRevenue),
            "monthlyExpenses" => json_encode($monthlyExpenses),
            "totalUnits" => $totalUnitsAcrossProperties,
            "vacantUnits" => $vacantUnits,
            "vacantLostRevenue" => round($vacantLostRevenue),
            "yearlyRevenue" => $yearlyRevenue,
            "yearlyExpenses" => $yearlyExpenses,
            "netIncome" => $netIncome,
        ];
    }
}
