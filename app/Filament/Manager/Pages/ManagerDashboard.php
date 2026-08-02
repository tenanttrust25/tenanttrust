<?php

namespace App\Filament\Manager\Pages;

use App\Filament\Manager\Widgets\PortfolioOverviewWidget;
use App\Filament\Manager\Widgets\FinancialTrackingWidget;
use App\Filament\Manager\Widgets\MaintenanceWorkflowsWidget;
use App\Filament\Manager\Widgets\LeaseInsightsWidget;
use App\Filament\Manager\Widgets\ManagerStatsOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class ManagerDashboard extends BaseDashboard
{
    protected static ?string $title = "Property Manager Dashboard";
    protected static ?string $navigationLabel = "Dashboard";
    protected static ?string $navigationIcon = "heroicon-o-home";
    protected static ?string $slug = "/";

    public function getWidgets(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            ManagerStatsOverview::class,
            PortfolioOverviewWidget::class,
            FinancialTrackingWidget::class,
            MaintenanceWorkflowsWidget::class,
            LeaseInsightsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
