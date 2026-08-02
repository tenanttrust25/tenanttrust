<?php

namespace App\Filament\Manager\Widgets;

use App\Models\Property;
use App\Models\Tenancy;
use Filament\Widgets\Widget;

class PortfolioOverviewWidget extends Widget
{
    protected static string $view = "filament.manager.widgets.portfolio-overview";
    protected static ?string $heading = "Section 1 · Portfolio Overview";
    protected int | string | array $columnSpan = "full";
    protected static ?string $pollingInterval = "120s";

    public ?string $filterPropertyType = null;
    public ?string $filterCity = null;
    public ?string $filterStatus = null;

    public function getViewData(): array
    {
        $query = Property::query();

        if ($this->filterPropertyType) {
            $query->where("property_type", $this->filterPropertyType);
        }
        if ($this->filterCity) {
            $query->where("city", $this->filterCity);
        }
        if ($this->filterStatus) {
            $query->where("status", $this->filterStatus);
        }

        $totalProperties = (clone $query)->count();
        $totalUnits = Property::sum("unit_count");
        $occupiedProps = Property::where("status", "occupied")->count();
        $vacantProps = Property::where("status", "vacant")->count();
        $occupancyRate = $totalProperties > 0 ? round(($occupiedProps / $totalProperties) * 100) : 0;

        $totalValue = Property::sum("market_value") ?: Property::sum("price");
        $monthlyRentRoll = Tenancy::where("status", "active")->sum("rent_amount");
        $activeLeasesCount = Tenancy::where("status", "active")->count();

        $cities = Property::select("city")->distinct()->pluck("city")->toArray();
        $propertyTypes = Property::select("property_type")->distinct()->pluck("property_type")->toArray();
        $statuses = ["active", "occupied", "vacant", "sold"];

        $avgDaysToFill = Tenancy::whereNotNull("created_at")
            ->selectRaw("AVG(DATEDIFF(lease_start, created_at)) as avg_days")
            ->value("avg_days") ?? 0;

        // One row per property — group tenancies by property_id
        $properties = (clone $query)
            ->get()
            ->map(function ($p) {
                $activeTenancies = Tenancy::where("property_id", $p->id)
                    ->where("status", "active")
                    ->get();

                return [
                    "id" => $p->id,
                    "title" => $p->title,
                    "city" => $p->city,
                    "unit_count" => $p->unit_count,
                    "total_rent" => $activeTenancies->sum("rent_amount"),
                    "leased_units" => $activeTenancies->count(),
                    "status" => $p->status,
                ];
            });

        return [
            "totalProperties" => $totalProperties,
            "totalUnits" => $totalUnits,
            "occupiedProps" => $occupiedProps,
            "vacantProps" => $vacantProps,
            "occupancyRate" => $occupancyRate,
            "totalValue" => $totalValue,
            "monthlyRentRoll" => $monthlyRentRoll,
            "activeLeasesCount" => $activeLeasesCount,
            "avgDaysToFill" => round($avgDaysToFill),
            "cities" => $cities,
            "propertyTypes" => $propertyTypes,
            "statuses" => $statuses,
            "properties" => $properties,
        ];
    }
}
