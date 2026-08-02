<?php

namespace App\Filament\Admin\Pages;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use App\Models\Tenancy;
use App\Models\MaintenanceTicket;
use Filament\Pages\Dashboard as BaseDashboard;

class AdminDashboard extends BaseDashboard
{
    protected static ?string $title = "Dashboard";
    protected static ?string $navigationLabel = "Dashboard";
    protected static ?string $navigationIcon = "heroicon-o-home";
    protected static ?string $slug = "/";

    public function getWidgets(): array
    {
        return [
            \App\Filament\Admin\Widgets\AdminStatsOverview::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
