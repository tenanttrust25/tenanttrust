<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\User;
use App\Models\Tenancy;
use App\Models\MaintenanceTicket;
use Filament\Widgets\Widget;

class AdminStatsOverview extends Widget
{
    protected static string $view = 'filament.admin.widgets.admin-stats-overview';
    protected static ?string $heading = 'Site Overview';
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $totalProperties = Property::count();
        $occupiedUnits = Tenancy::where('status', 'active')->count();
        $totalTenants = User::where('role', 'tenant')->count();
        $totalOwners = User::where('role', 'owner')->count();

        $monthlyRevenue = Invoice::where('status', 'paid')
            ->whereMonth('due_date', now()->month)
            ->whereYear('due_date', now()->year)
            ->sum('amount');

        $pendingInvoices = Invoice::where('status', 'pending')->count();
        $overdueInvoices = Invoice::where('status', 'overdue')->count();
        $overdueAmount = Invoice::where('status', 'overdue')->sum('amount');

        $openTickets = MaintenanceTicket::whereIn('status', ['open', 'in_progress'])->count();

        // Management Fees
        $monthsYtd = now()->month;
        $unitsWithFee = PropertyUnit::whereNotNull('monthly_management_fee')
            ->where('monthly_management_fee', '>', 0)
            ->where('status', 'occupied')
            ->get();

        $ytdManagementFees = $unitsWithFee->sum(function ($unit) use ($monthsYtd) {
            return $unit->monthly_management_fee * $monthsYtd;
        });

        $monthlyManagementFees = $unitsWithFee->sum('monthly_management_fee');

        $recentInvoices = Invoice::with(['tenant', 'property'])
            ->latest()
            ->take(5)
            ->get();

        return [
            'totalProperties' => $totalProperties,
            'occupiedUnits' => $occupiedUnits,
            'totalTenants' => $totalTenants,
            'totalOwners' => $totalOwners,
            'monthlyRevenue' => $monthlyRevenue,
            'pendingInvoices' => $pendingInvoices,
            'overdueInvoices' => $overdueInvoices,
            'overdueAmount' => $overdueAmount,
            'openTickets' => $openTickets,
            'ytdManagementFees' => $ytdManagementFees,
            'monthlyManagementFees' => $monthlyManagementFees,
            'recentInvoices' => $recentInvoices,
        ];
    }
}
