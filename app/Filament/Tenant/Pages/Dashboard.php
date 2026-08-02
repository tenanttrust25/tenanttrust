<?php

namespace App\Filament\Tenant\Pages;

use App\Models\Invoice;
use App\Models\MaintenanceTicket;
use App\Models\TenantFile;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-home";
    protected static ?string $title = "Dashboard";
    protected static ?string $navigationLabel = "Dashboard";
    protected static ?int $navigationSort = 1;

    protected static string $view = "filament.tenant.dashboard";

    public function getViewData(): array
    {
        $user = Auth::user();

        $invoicesDue = Invoice::where("tenant_id", $user->id)
            ->whereIn("status", ["pending", "overdue"])
            ->count();

        $openTickets = MaintenanceTicket::where("tenant_id", $user->id)
            ->whereIn("status", ["open", "in_progress"])
            ->count();

        $availableFiles = TenantFile::where("tenant_id", $user->id)
            ->count();

        return [
            "user" => $user,
            "invoicesDue" => $invoicesDue,
            "openTickets" => $openTickets,
            "availableFiles" => $availableFiles,
        ];
    }
}
