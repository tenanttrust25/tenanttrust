<?php

namespace App\Filament\Manager\Widgets;

use App\Models\MaintenanceTicket;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class MaintenanceWorkflowsWidget extends Widget
{
    protected static string $view = "filament.manager.widgets.maintenance-workflows";
    protected static ?string $heading = "🔧 Maintenance Workflows";
    protected int | string | array $columnSpan = "full";
    protected static ?string $pollingInterval = "60s";

    public function getViewData(): array
    {
        // Open work orders
        $openTickets = MaintenanceTicket::whereIn("status", ["open", "in_progress"])
            ->with(["tenant", "property"])
            ->orderByRaw("FIELD(priority, \"emergency\", \"high\", \"medium\", \"low\")")
            ->orderBy("created_at", "desc")
            ->get()
            ->map(function ($t) {
                return [
                    "id" => $t->id,
                    "ticket_number" => $t->ticket_number,
                    "title" => $t->title,
                    "priority" => $t->priority,
                    "status" => $t->status,
                    "property" => $t->property?->title ?? "N/A",
                    "tenant" => $t->tenant?->name ?? "N/A",
                    "created_at" => $t->created_at->diffForHumans(),
                    "days_open" => $t->created_at->diffInDays(now()),
                    "is_repeat" => MaintenanceTicket::where("tenant_id", $t->tenant_id)
                        ->where("property_id", $t->property_id)
                        ->where("title", "like", "%" . substr($t->title, 0, 20) . "%")
                        ->count() > 1,
                ];
            });

        // Resolution time stats
        $resolvedTickets = MaintenanceTicket::whereIn("status", ["resolved", "closed"])
            ->whereNotNull("resolved_at");

        $avgResolutionHours = (clone $resolvedTickets)
            ->selectRaw("AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours")
            ->value("avg_hours") ?? 0;

        $totalRepairCost = (clone $resolvedTickets)->sum("cost") ?? 0;
        $openCount = MaintenanceTicket::whereIn("status", ["open", "in_progress"])->count();
        $resolvedCount = (clone $resolvedTickets)->count();
        $emergencyCount = MaintenanceTicket::where("priority", "emergency")
            ->whereIn("status", ["open", "in_progress"])->count();

        // Priority breakdown
        $priorityCounts = MaintenanceTicket::whereIn("status", ["open", "in_progress"])
            ->select("priority", DB::raw("COUNT(*) as count"))
            ->groupBy("priority")
            ->pluck("count", "priority")
            ->toArray();

        return [
            "openTickets" => $openTickets,
            "avgResolutionHours" => round($avgResolutionHours, 1),
            "totalRepairCost" => $totalRepairCost,
            "openCount" => $openCount,
            "resolvedCount" => $resolvedCount,
            "emergencyCount" => $emergencyCount,
            "priorityCounts" => $priorityCounts,
        ];
    }
}
