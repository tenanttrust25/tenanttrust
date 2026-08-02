<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Tenancy;
use Filament\Widgets\Widget;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class LeaseInsightsWidget extends Widget
{
    use WithPagination;

    protected static string $view = "filament.manager.widgets.lease-insights";
    protected static ?string $heading = "📋 Tenant & Lease Insights";
    protected int | string | array $columnSpan = "full";
    protected static ?string $pollingInterval = "300s";

    public function getViewData(): array
    {
        // Active leases - paginated 10 per page
        $activeTenancies = Tenancy::where("status", "active")
            ->with(["tenant", "property"])
            ->orderBy("property_id")
            ->orderBy("unit")
            ->paginate(10);

        $activeTenancies->getCollection()->transform(function ($t) {
            $daysUntilExpiry = $t->lease_end ? now()->diffInDays($t->lease_end, false) : null;
            return [
                "id" => $t->id,
                "tenant" => $t->tenant?->name ?? "N/A",
                "tenant_email" => $t->tenant?->email ?? "",
                "property" => $t->property?->title ?? "N/A",
                "unit" => $t->unit,
                "rent" => "$" . number_format($t->rent_amount),
                "lease_start" => $t->lease_start->format("M d, Y"),
                "lease_end" => $t->lease_end?->format("M d, Y") ?? "Ongoing",
                "days_remaining" => $daysUntilExpiry !== null ? (int) $daysUntilExpiry : null,
                "status" => $t->status,
            ];
        });

        $expiringSoon = Tenancy::where("status", "active")
            ->whereNotNull("lease_end")
            ->where("lease_end", ">=", now())
            ->where("lease_end", "<=", now()->addDays(60))
            ->with(["tenant", "property"])
            ->get()
            ->map(fn($t) => [
                "tenant" => $t->tenant?->name ?? "N/A",
                "property" => $t->property?->title ?? "N/A",
                "date" => $t->lease_end->format("M d, Y"),
                "days" => now()->diffInDays($t->lease_end, false),
            ]);

        $upcomingMoveIns = Tenancy::where("status", "pending")
            ->where("lease_start", ">=", now())
            ->with(["tenant", "property"])
            ->orderBy("lease_start")
            ->get()
            ->map(fn($t) => [
                "tenant" => $t->tenant?->name ?? "N/A",
                "property" => $t->property?->title ?? "N/A",
                "date" => $t->lease_start->format("M d, Y"),
            ]);

        $totalActive = Tenancy::where("status", "active")->count();

        return [
            "activeTenancies" => $activeTenancies,
            "expiringSoon" => $expiringSoon,
            "expiringCount" => $expiringSoon->count(),
            "upcomingMoveIns" => $upcomingMoveIns,
            "totalActive" => $totalActive,
        ];
    }

    public function paginateActiveTenancies()
    {
        return $this->getViewData()["activeTenancies"];
    }
}
