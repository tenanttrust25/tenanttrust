<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\Tenancy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerStatsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = "60s";

    protected function getStats(): array
    {
        $totalProperties = Property::count();
        $occupied = Property::where("status", "occupied")->count();
        $vacant = Property::where("status", "vacant")->count();
        $occupancyRate = $totalProperties > 0 ? round(($occupied / $totalProperties) * 100) : 0;

        $openTickets = MaintenanceTicket::whereIn("status", ["open", "in_progress"])->count();
        $overdueInvoices = Invoice::where("status", "overdue")->count();
        $overdueAmount = Invoice::where("status", "overdue")->sum("amount");

        $activeTenancies = Tenancy::where("status", "active")->count();
        $upcomingRenewals = Tenancy::expiringSoon(60)->count();

        $monthlyRevenue = Invoice::where("status", "paid")
            ->whereMonth("created_at", now()->month)
            ->whereYear("created_at", now()->year)
            ->sum("amount");

        return [
            Stat::make("Properties", $totalProperties)
                ->description($occupied . " occupied · " . $vacant . " vacant")
                ->descriptionIcon("heroicon-o-building-office-2")
                ->color("info"),

            Stat::make("Occupancy Rate", $occupancyRate . "%")
                ->description($activeTenancies . " active leases")
                ->descriptionIcon("heroicon-o-user-group")
                ->color($occupancyRate > 80 ? "success" : "warning"),

            Stat::make("Monthly Revenue", "$" . number_format($monthlyRevenue))
                ->description($overdueInvoices . " overdue invoices · $" . number_format($overdueAmount))
                ->descriptionIcon("heroicon-o-currency-dollar")
                ->color("success"),

            Stat::make("Open Tickets", $openTickets)
                ->description($upcomingRenewals . " renewals due within 60 days")
                ->descriptionIcon("heroicon-o-wrench-screwdriver")
                ->color($openTickets > 0 ? "warning" : "success"),
        ];
    }
}
